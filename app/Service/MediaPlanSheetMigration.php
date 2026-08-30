<?php

namespace App\Service;

use App\Models\MediaPlan;
use App\Models\MediaPlanKol;
use Illuminate\Support\Facades\DB;

/**
 * Migrasi sheet "KOL List" → daftar KOL sebuah Media Plan Internal.
 *
 * Bentuk sheet-nya BERBEDA dari profil lain: satu KOL menempati beberapa baris.
 * Baris pertama berisi identitas KOL (username, channel, followers, …) plus
 * scope of work pertamanya; baris-baris di bawahnya hanya berisi SOW tambahan
 * dengan kolom identitas kosong. Jadi baris tanpa username BUKAN baris kosong —
 * ia milik KOL di atasnya, dan digabung jadi satu item.
 *
 * Acuan kolomnya berkas `[INT] Bir Kawan Senja - KOL List.xlsx`; tiap tier
 * (Nano/Micro/Macro/Homeless Media) satu tab dengan susunan kolom yang sama.
 */
class MediaPlanSheetMigration extends SheetMigration
{
    /** Media Plan tujuan — WAJIB dipilih user sebelum migrasi dijalankan. */
    private ?int $mediaPlanId = null;

    public function untukMediaPlan(?int $id): static
    {
        $this->mediaPlanId = $id;

        return $this;
    }

    public function label(): string
    {
        return 'KOL List → Media Plan Internal';
    }

    public function defaultSheetName(): ?string
    {
        // Tab per tier; tidak ada yang lebih benar dari yang lain, jadi user pilih.
        return null;
    }

    public function aliases(): array
    {
        return [
            'name' => ['username', 'nama kol', 'kol'],
            'pic' => ['pic'],
            'status' => ['status'],
            'links' => ['link', 'link profil'],
            'channel' => ['channel', 'platform'],
            'categories' => ['categories', 'kategori'],
            'followers' => ['followers'],
            'tier' => ['tier'],
            'er_percent' => ['er persen', 'er'],
            'impression' => ['avg views', 'impression'],
            'engagement' => ['engagement'],
            'domisili' => ['dom', 'domisili'],
            'notes' => ['notes', 'catatan'],
            // Blok Scope of Work sebelah kanan: qty + item + rate per baris.
            'sow_qty' => ['qty'],
            'sow_item' => ['item'],
            'rate' => ['rate'],
        ];
    }

    public function previewColumns(): array
    {
        return ['name', 'channel', 'pic', 'status', 'tier', 'followers', 'sow_ringkas', 'rate'];
    }

    protected function requiredField(): string
    {
        return 'name';
    }

    /**
     * Gabungkan baris-baris SOW ke KOL di atasnya.
     *
     * @param  array<int, array<int, mixed>>  $rows
     * @return array<int, array<string, mixed>>
     */
    public function parseRows(array $rows): array
    {
        $baris = parent::parseRows($rows);
        $items = [];

        foreach ($baris as $b) {
            $sow = $this->sowDari($b);

            if (blank($b['name'] ?? null)) {
                // Lanjutan KOL sebelumnya. Tanpa induk, baris ini memang yatim.
                if ($items !== [] && $sow) {
                    $items[count($items) - 1]['scope_items'][] = $sow;
                }

                continue;
            }

            $b['scope_items'] = $sow ? [$sow] : [];
            unset($b['sow_qty'], $b['sow_item']);

            $items[] = $b;
        }

        // Ringkasan SOW baru bisa dibuat setelah semua barisnya terkumpul.
        foreach ($items as $i => $item) {
            $items[$i]['sow_ringkas'] = collect($item['scope_items'])->pluck('item')->implode(', ');
            $items[$i]['_note'] = $item['scope_items'] === []
                ? 'KOL ini tidak punya scope of work.'
                : ($item['_note'] ?? null);
        }

        return $items;
    }

    /** @return array{qty: int, item: string, rate: float}|null */
    private function sowDari(array $b): ?array
    {
        $item = trim((string) ($b['sow_item'] ?? ''));

        if ($item === '') {
            return null;
        }

        return [
            'qty' => (int) (self::toNumber($b['sow_qty'] ?? null) ?: 1),
            'item' => $item,
            'rate' => (float) (self::toNumber($b['rate'] ?? null) ?: 0),
        ];
    }

    protected function refine(array $item): array
    {
        $item['followers'] = (int) (self::toNumber($item['followers'] ?? null) ?: 0);
        $item['impression'] = (int) (self::toNumber($item['impression'] ?? null) ?: 0);
        $item['engagement'] = (int) (self::toNumber($item['engagement'] ?? null) ?: 0);
        $item['er_percent'] = self::toNumber($item['er_percent'] ?? null);

        return $item;
    }

    public function persist(array $items): array
    {
        $hasil = ['success' => 0, 'skipped' => 0, 'failed' => 0, 'notes' => []];

        $mediaPlan = $this->mediaPlanId ? MediaPlan::find($this->mediaPlanId) : null;

        if (! $mediaPlan) {
            $hasil['skipped'] = count($items);
            $hasil['notes'][] = 'Media Plan tujuan belum dipilih — tidak ada yang disimpan.';

            return $hasil;
        }

        DB::transaction(function () use ($items, $mediaPlan, &$hasil) {
            foreach ($items as $item) {
                if (blank($item['name'] ?? null)) {
                    $hasil['skipped']++;

                    continue;
                }

                try {
                    $this->simpanSatu($item, $mediaPlan, $hasil);
                    $hasil['success']++;
                } catch (\Throwable $e) {
                    $hasil['failed']++;
                    $hasil['notes'][] = "Baris {$item['_row']} ({$item['name']}): {$e->getMessage()}";
                }
            }
        });

        return $hasil;
    }

    private function simpanSatu(array $item, MediaPlan $mediaPlan, array &$hasil): void
    {
        // Satu KOL bisa punya beberapa channel, jadi kuncinya nama + channel.
        $kol = MediaPlanKol::firstOrNew([
            'media_plan_id' => $mediaPlan->id,
            'name' => $item['name'],
            'channel' => $item['channel'] ?: null,
        ]);

        foreach (['pic', 'status', 'categories', 'followers', 'tier', 'er_percent',
            'impression', 'engagement', 'domisili', 'notes'] as $field) {
            if (($item[$field] ?? null) !== null && $item[$field] !== '') {
                $kol->{$field} = $item[$field];
            }
        }

        if (filled($item['links'] ?? null)) {
            $kol->links = [$item['links']];
        }

        if ($item['scope_items'] ?? []) {
            $kol->scope_items = collect($item['scope_items'])->pluck('item')->all();
            $kol->qty = collect($item['scope_items'])->sum('qty');
            // Rate KOL = jumlah rate seluruh SOW-nya; sheet menaruh angkanya per baris SOW.
            $kol->rate = collect($item['scope_items'])->sum('rate');
        }

        $kol->row_number ??= (int) ($item['_row'] ?? 0);
        $kol->save();
    }
}
