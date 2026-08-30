<?php

namespace App\Service;

use App\Filament\Resources\MediaPlans\Schemas\MediaPlanForm;
use App\Models\BvSales;
use App\Models\DataKol;
use App\Models\InternalBudget;
use App\Models\KolRateCard;
use App\Models\MasterPph;
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
 *
 * Satu baris sheet menghasilkan EMPAT hal, bukan satu:
 *   1. DataKol      — username, channel, followers, link, tier, ER, avg views.
 *   2. KolRateCard  — satu per scope of work, berisi rate dari sheet.
 *   3. MediaPlanKol — barisnya di KOL List, ditautkan ke DataKol di atas.
 *   4. InternalBudgetItem — satu per SOW, lalu dihitung.
 *
 * Kolom Subtotal Rate, Gross Up PPH Coefficient, Tax, MU PPh*, MU**, Published
 * Rate***, Rounded, dan Margin % di sheet TIDAK diambil angkanya. Semua itu
 * turunan yang aplikasi hitung sendiri dari rate card + tipe pajak lewat
 * MediaPlanForm::computeBudgetFigures() — rumus yang sama dipakai halaman Media
 * Plan. Mengimpor angka sheet berarti menanam ulang hasil koefisien PPh lama
 * yang sudah terbukti salah.
 */
class MediaPlanSheetMigration extends SheetMigration
{
    /**
     * Deal di Sales Activity Tracker yang jadi tujuan — WAJIB dipilih user.
     *
     * Yang dipilih deal-nya, bukan Media Plan-nya langsung: Media Plan memang
     * turunan dari deal (BvSales::ensureMediaPlanExists()), jadi memilih deal
     * berarti KOL-nya pasti mendarat di Media Plan yang benar — dan kalau Media
     * Plan-nya belum ada, dibuatkan dengan cara yang sama seperti alur normal.
     */
    private ?int $bvSalesId = null;

    public function untukSales(?int $id): static
    {
        $this->bvSalesId = $id;

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

        $mediaPlan = $this->mediaPlanTujuan($hasil);

        if (! $mediaPlan) {
            $hasil['skipped'] = count($items);

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

    /** @param array{notes: array<int, string>} $hasil */
    private function mediaPlanTujuan(array &$hasil): ?MediaPlan
    {
        if (! $this->bvSalesId) {
            $hasil['notes'][] = 'Deal di Sales Activity Tracker belum dipilih — tidak ada yang disimpan.';

            return null;
        }

        $sales = BvSales::find($this->bvSalesId);

        if (! $sales) {
            $hasil['notes'][] = 'Deal yang dipilih sudah tidak ada.';

            return null;
        }

        // Deal yang belum sampai tahap briefing belum punya Media Plan; dibuatkan
        // lewat method milik model itu sendiri supaya isinya sama dengan alur normal.
        $sales->ensureMediaPlanExists();

        $mediaPlan = $sales->mediaPlan()->first();

        if (! $mediaPlan) {
            $hasil['notes'][] = 'Media Plan untuk deal ini gagal disiapkan.';
        }

        return $mediaPlan;
    }

    private function simpanSatu(array $item, MediaPlan $mediaPlan, array &$hasil): void
    {
        $dataKol = $this->dataKolUntuk($item, $hasil);
        $this->simpanRateCards($dataKol, $item);

        // Satu KOL bisa punya beberapa channel, jadi kuncinya nama + channel.
        $kol = MediaPlanKol::firstOrNew([
            'media_plan_id' => $mediaPlan->id,
            'name' => $item['name'],
            'channel' => $item['channel'] ?: '',
        ]);

        foreach (['pic', 'status', 'categories', 'followers', 'tier', 'er_percent',
            'impression', 'engagement', 'domisili', 'notes'] as $field) {
            if (($item[$field] ?? null) !== null && $item[$field] !== '') {
                $kol->{$field} = $item[$field];
            }
        }

        $kol->data_kol_id = $dataKol->id;
        $kol->tipe_pajak_kol ??= MasterPph::where('name', 'Pribadi')->value('id');

        if (filled($item['links'] ?? null)) {
            $kol->links = [$item['links']];
        }

        if ($item['scope_items'] ?? []) {
            $kol->scope_items = collect($item['scope_items'])->pluck('item')->all();
            // Satu qty berlaku untuk semua budget item KOL ini — begitu cara
            // EditMediaPlan menyimpannya, jadi diikuti supaya tidak beda sendiri.
            $kol->qty = max(1, (int) collect($item['scope_items'])->max('qty'));
        }

        $kol->row_number ??= (int) ($item['_row'] ?? 0);
        $kol->save();

        $this->generateBudgetItems($mediaPlan, $kol);

        // rate & CPI/CPV/CPE baris KOL diturunkan dari budget item, bukan diisi
        // tangan — method milik model itu sendiri yang menghitungnya.
        $kol->syncRateFromBudget();
    }

    /**
     * Baris KOL Data untuk KOL ini. Nama, channel, followers, dan link dari sheet
     * memang seharusnya mendarat di KOL Data juga — dari situlah rate card,
     * analyzer, dan SPK mengambil datanya.
     */
    private function dataKolUntuk(array $item, array &$hasil): DataKol
    {
        $kol = DataKol::firstOrNew([
            'username' => $item['name'],
            'channel' => $item['channel'] ?: '-',
        ]);

        if (! $kol->exists) {
            $hasil['notes'][] = "KOL baru masuk KOL Data: \"{$item['name']}\" ({$item['channel']}).";
        }

        // link_userprofile NOT NULL; kalau sheet tidak mengisinya, bentuk kanonik
        // dari username dipakai supaya barisnya tetap bisa dibuka & di-scrape.
        $kol->link_userprofile = $item['links']
            ?: ($kol->link_userprofile
                ?: (\App\Service\KolProfileImporter::canonicalUrl((string) $item['channel'], (string) $item['name']) ?? '-'));

        foreach ([
            'followers' => 'followers',
            'tier' => 'tier',
            'er_percent' => 'engagement_rate',
            'impression' => 'average_views',
            'engagement' => 'engagements',
        ] as $dari => $ke) {
            if (filled($item[$dari] ?? null)) {
                $kol->{$ke} = $item[$dari];
            }
        }

        $kol->save();

        return $kol;
    }

    /**
     * Rate tiap SOW dari sheet disimpan sebagai rate card KOL — bukan ditulis
     * langsung ke baris Media Plan. Di app ini rate card yang jadi sumber:
     * MediaPlanForm::computeRateFromSow() membaca dari sana saat budget item
     * dibuat, dan halaman Media Plan menolak menyimpan KOL yang rate card-nya 0.
     */
    private function simpanRateCards(DataKol $dataKol, array $item): void
    {
        foreach ($item['scope_items'] ?? [] as $sow) {
            if ($sow['rate'] <= 0) {
                continue;
            }

            KolRateCard::updateOrCreate(
                [
                    'data_kol_id' => $dataKol->id,
                    'channel' => $dataKol->channel,
                    'sow' => $sow['item'],
                ],
                [
                    'rate' => $sow['rate'],
                    'valid_from' => now()->toDateString(),
                ],
            );
        }
    }

    /**
     * Budget item per SOW + hitungannya.
     *
     * Orkestrasinya meniru EditMediaPlan::afterSave(), tapi RUMUSNYA tidak
     * disalin: rate diambil computeRateFromSow() dan angkanya computeBudgetFigures(),
     * dua helper publik yang sama dipakai halaman Media Plan. Kalau rumusnya
     * ditulis ulang di sini, dua tempat itu pasti berbeda suatu hari.
     */
    private function generateBudgetItems(MediaPlan $mediaPlan, MediaPlanKol $kol): void
    {
        // firstOrCreate lewat relasi, bukan $mediaPlan->internalBudget: properti
        // relasi ter-cache sejak KOL pertama, jadi KOL berikutnya masih melihat
        // null dan mencoba membuat budget kedua — yang ditolak unique index.
        $budget = $mediaPlan->internalBudget()->firstOrCreate([], ['status' => 'draft']);

        // Dibuat ulang, bukan ditambah: migrasi ulang tidak boleh menggandakan item.
        $kol->internalBudgetItems()->delete();

        $sortOrder = $budget->items()->max('sort_order') ?? 0;
        $qty = max(1, (int) ($kol->qty ?: 1));
        $pphId = $kol->tipe_pajak_kol ?? MasterPph::where('name', 'Pribadi')->value('id');

        foreach ($kol->scope_items ?? [] as $scopeItem) {
            $budget->items()->create([
                'media_plan_kol_id' => $kol->id,
                'scope_item' => $scopeItem,
                'qty' => $qty,
                'rate_base' => MediaPlanForm::computeRateFromSow($kol->data_kol_id, $kol->name, $kol->channel, [$scopeItem]),
                'master_pph_id' => $pphId,
                'sort_order' => ++$sortOrder,
            ]);
        }

        foreach ($budget->items()->with(['mediaPlanKol', 'masterPph'])->where('media_plan_kol_id', $kol->id)->get() as $bi) {
            $coeff = $bi->masterPph?->getCalculatedCoefficient() ?? 0.975;
            $marginKol = $bi->mediaPlanKol?->margin_percent;

            $figs = MediaPlanForm::computeBudgetFigures(
                (float) $bi->rate_base * (int) ($bi->qty ?: 1),
                $coeff,
                $marginKol !== null ? (float) $marginKol : null,
            );

            $bi->update([
                'subtotal' => $figs['subtotal'],
                'mu_pph' => $figs['mu_pph'],
                'mu_target' => $figs['mu_target'],
                'published_rate' => $figs['mu_target'],
                'rounded' => $figs['rounded'],
                'actual_margin_percent' => $figs['actual_margin'],
                'use_flexible_margin' => $marginKol !== null,
                'margin_percent_override' => $marginKol !== null ? (float) $marginKol : null,
            ]);
        }

        $budget->refresh();
        $budget->recalculateTotals();
    }
}
