<?php

namespace App\Service;

use App\Models\BvSales;
use Illuminate\Support\Facades\DB;

/**
 * Migrasi tab "Brief" → FormBrief, yang tampil sebagai tab Brief di Media Plan
 * Internal (MediaPlanForm membacanya lewat $record->bvSales->formBrief).
 *
 * Bentuk sheet-nya BERBEDA dari semua profil lain: bukan tabel dengan baris
 * judul di atas, melainkan daftar vertikal — label di kolom A, isinya di kolom B.
 * Karena itu headerRow() di sini mengembalikan KOLOM A, bukan sebuah baris; sisa
 * mesinnya (alias, preview, kolom tak dikenali) jadi bisa dipakai apa adanya.
 */
class BriefSheetMigration extends SheetMigration
{
    /** Deal tujuan; brief-nya menempel di sana, sama seperti profil KOL List. */
    private ?int $bvSalesId = null;

    public function untukSales(?int $id): static
    {
        $this->bvSalesId = $id;

        return $this;
    }

    public function label(): string
    {
        return 'Brief → Media Plan Internal';
    }

    public function defaultSheetName(): ?string
    {
        return 'Brief';
    }

    public function aliases(): array
    {
        return [
            // "Creiteria" memang salah ketik di sheet; diterima apa adanya
            // supaya sheet lama tidak perlu diubah lebih dulu.
            'campaign_objective' => ['campaign objective brief', 'campaign objective', 'brief', 'objective'],
            'criteria_of_kol' => ['creiteria of kol', 'criteria of kol', 'kriteria kol'],
            'sow' => ['sow', 'scope of work'],
            'budget' => ['budget', 'anggaran'],
            'deadline' => ['deadline', 'tenggat'],
        ];
    }

    public function previewColumns(): array
    {
        return ['campaign_objective', 'criteria_of_kol', 'sow', 'budget', 'deadline'];
    }

    protected function requiredField(): string
    {
        return 'campaign_objective';
    }

    /**
     * Label brief ada di kolom A tiap baris, bukan di satu baris judul. Yang
     * dikembalikan karena itu kolom A — mapHeaders() lalu memetakannya seperti
     * biasa, dan indeks hasilnya menunjuk NOMOR BARIS, bukan nomor kolom.
     *
     * @param  array<int, array<int, mixed>>  $rows
     * @return array<int, mixed>
     */
    public function headerRow(array $rows): array
    {
        return array_map(fn($row) => $row[0] ?? '', $rows);
    }

    public function headerRowIndex(array $rows): int
    {
        return 0;
    }

    /**
     * Seluruh tab jadi SATU item: satu sheet = satu brief.
     *
     * @param  array<int, array<int, mixed>>  $rows
     * @return array<int, array<string, mixed>>
     */
    public function parseRows(array $rows): array
    {
        $item = [];

        foreach ($this->mapHeaders($this->headerRow($rows)) as $baris => $field) {
            $isi = trim((string) ($rows[$baris][1] ?? ''));

            if ($isi !== '') {
                $item[$field] = $isi;
            }
        }

        if ($item === []) {
            return [];
        }

        $item['_row'] = 1;
        $item['_note'] = blank($item[$this->requiredField()] ?? null)
            ? 'Campaign Objective kosong di sheet.'
            : null;

        return [$item];
    }

    public function persist(array $items): array
    {
        $hasil = ['success' => 0, 'skipped' => 0, 'failed' => 0, 'notes' => []];

        $sales = $this->bvSalesId ? BvSales::find($this->bvSalesId) : null;

        if (! $sales) {
            $hasil['skipped'] = count($items);
            $hasil['notes'][] = 'Deal di Sales Activity Tracker belum dipilih — tidak ada yang disimpan.';

            return $hasil;
        }

        DB::transaction(function () use ($items, $sales, &$hasil) {
            foreach ($items as $item) {
                try {
                    // Dibuat lewat method milik model supaya judul, brand, dan
                    // nama campaign-nya sama dengan brief yang lahir dari alur normal.
                    $brief = $sales->ensureFormBriefExists();

                    foreach ($this->previewColumns() as $field) {
                        // Sel kosong tidak menghapus isi brief yang sudah ada.
                        if (blank($item[$field] ?? null)) {
                            continue;
                        }

                        // form_briefs.budget kolom ANGKA, sementara sheet sering
                        // menulis "Open" atau "TBD". Teks begitu dilaporkan, bukan
                        // dipaksa masuk — kalau dipaksa, hasilnya tersimpan 0 dan
                        // terbaca seolah budget-nya nol.
                        if ($field === 'budget' && self::toNumber($item[$field]) === null) {
                            $hasil['notes'][] = 'Budget di sheet berisi teks "' . $item[$field]
                                . '", sedangkan kolomnya angka — dilewati, isi manual bila perlu.';

                            continue;
                        }

                        $brief->{$field} = $field === 'budget'
                            ? self::toNumber($item[$field])
                            : $item[$field];
                    }

                    $brief->save();

                    $hasil['success']++;
                    $hasil['notes'][] = 'Brief tersimpan di deal "' . $sales->event_name . '".';
                } catch (\Throwable $e) {
                    $hasil['failed']++;
                    $hasil['notes'][] = 'Brief gagal disimpan: ' . $e->getMessage();
                }
            }
        });

        return $hasil;
    }
}
