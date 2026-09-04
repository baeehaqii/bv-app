<?php

namespace App\Service;

use App\Enums\ClientStatus;
use App\Enums\SalesStatus;
use App\Models\BvSales;
use App\Models\BvSalesList;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Migrasi tab Pipeline → BvSales (menu Sales Activity Tracker).
 *
 * Satu baris sheet = satu deal/campaign yang sedang digarap, bukan satu client.
 * Client-nya sendiri dimigrasikan terpisah lewat ClientSheetMigration; di sini
 * `company_name` cukup diisi nama brand-nya, karena relasi BvSales::client()
 * memang mencocokkan nama, bukan id.
 */
class PipelineSheetMigration extends SheetMigration
{
    public function label(): string
    {
        return 'Sales Activity Tracker (Pipeline / KOL Planning)';
    }

    public function defaultSheetName(): ?string
    {
        return 'Pipeline';
    }

    public function aliases(): array
    {
        return [
            // "Campagin Name" — salah ketik di sheet, sengaja ikut diterima.
            'event_name' => ['campagin name', 'campaign name', 'nama campaign', 'event name', 'campaign'],
            'company_name' => ['client brand', 'client', 'brand', 'nama brand'],
            'related_client_name' => ['brand agency', 'agency', 'brand agency company'],
            'sales' => ['pic', 'pic bd'],
            'status' => ['stage status', 'stage', 'status', 'status kol teams'],
            'budget_propose' => ['amount idr', 'amount', 'budget', 'budget plan from client', 'budget plan from clients'],
            'deal_value' => ['amount deals', 'deal value'],
            'plan_cogs' => ['plan cogs', 'cogs', 'plan cost'],
            // Kolom rupiahnya. Yang berakhiran "%" jatuh ke field margin di bawah
            // — normalize() mengubah "%" jadi kata "persen", jadi dua judul yang
            // nyaris sama ini tetap bisa dibedakan.
            'projected_nett_margin' => ['projected nett margin', 'nett margin', 'net margin'],
            // BvSales.margin itu PERSEN (lihat getFormattedMarginAttribute), jadi
            // yang diambil kolom persen — bukan kolom rupiah di sebelahnya.
            'margin' => ['projected nett margin persen', 'margin persen', 'margin'],
            'close_date' => ['deadline date', 'deadline', 'close date', 'deadline submit'],
            'brief_submit_date' => ['brief dates', 'brief date'],
            'bulan' => ['months', 'month', 'bulan'],
        ];
    }

    public function previewColumns(): array
    {
        return [
            'event_name', 'company_name', 'related_client_name', 'sales', 'status',
            'budget_propose', 'plan_cogs', 'projected_nett_margin', 'margin',
            'deal_value', 'close_date',
        ];
    }

    /** Nilai status yang tidak dikenali, untuk dilaporkan sekali di akhir chunk. */
    private array $statusTakDikenal = [];

    public function statusTakDikenal(): array
    {
        return array_values(array_unique($this->statusTakDikenal));
    }

    protected function requiredField(): string
    {
        return 'event_name';
    }

    protected function refine(array $item): array
    {
        $item['related_client_name'] = self::normalizeAgency($item['related_client_name'] ?? null);
        $item['status'] = $this->normalizeStatus($item['status'] ?? null);
        $item['budget_propose'] = self::toNumber($item['budget_propose'] ?? null);
        $item['deal_value'] = self::toNumber($item['deal_value'] ?? null);
        $item['plan_cogs'] = self::toNumber($item['plan_cogs'] ?? null);
        $item['projected_nett_margin'] = self::toNumber($item['projected_nett_margin'] ?? null);
        $item['close_date'] = self::toDate($item['close_date'] ?? null);
        $item['brief_submit_date'] = self::toDate($item['brief_submit_date'] ?? null);
        // Sheet menulis margin sebagai pecahan (0,6533); kolomnya menyimpan persen.
        $margin = self::toNumber($item['margin'] ?? null);
        $item['margin'] = ($margin !== null && $margin <= 1) ? round($margin * 100, 2) : $margin;

        // "July 2025" / serial tanggal → bulan + tahun campaign.
        $bulan = self::toDate($item['bulan'] ?? null);
        $item['campaign_month'] = $bulan ? (int) CarbonImmutable::parse($bulan)->month : null;
        $item['campaign_year'] = $bulan ? (int) CarbonImmutable::parse($bulan)->year : null;

        return $item;
    }

    public function persist(array $items): array
    {
        $hasil = ['success' => 0, 'skipped' => 0, 'failed' => 0, 'notes' => []];

        DB::transaction(function () use ($items, &$hasil) {
            foreach ($items as $item) {
                if (blank($item['event_name'] ?? null)) {
                    $hasil['skipped']++;
                    $hasil['notes'][] = "Baris {$item['_row']}: nama campaign kosong, dilewati.";

                    continue;
                }

                try {
                    $this->simpanSatu($item, $hasil);
                    $hasil['success']++;
                } catch (\Throwable $e) {
                    $hasil['failed']++;
                    $hasil['notes'][] = "Baris {$item['_row']} ({$item['event_name']}): {$e->getMessage()}";
                }
            }
        });

        return $hasil;
    }

    private function simpanSatu(array $item, array &$hasil): void
    {
        // Satu deal dikenali dari nama campaign + client-nya: nama campaign saja
        // bisa dipakai dua client berbeda.
        $sales = BvSales::firstOrNew([
            'event_name' => $item['event_name'],
            'company_name' => $item['company_name'] ?: null,
        ]);

        foreach (['related_client_name', 'budget_propose', 'deal_value', 'plan_cogs',
            'projected_nett_margin', 'close_date', 'brief_submit_date', 'margin',
            'campaign_month', 'campaign_year'] as $field) {
            if (($item[$field] ?? null) !== null && $item[$field] !== '') {
                $sales->{$field} = $item[$field];
            }
        }

        if ($item['status'] ?? null) {
            $sales->status = $item['status'];
        }

        if (filled($item['sales'] ?? null)) {
            $nama = trim((string) $item['sales']);
            $orang = BvSalesList::untuk($nama);

            if ($orang->wasRecentlyCreated) {
                $hasil['notes'][] = "Sales baru dibuat di master: \"{$orang->nama_sales}\".";
            } elseif ($orang->nama_sales !== $nama) {
                $hasil['notes'][] = "Baris {$item['_row']}: \"{$nama}\" diarahkan ke \"{$orang->nama_sales}\".";
            }

            $sales->bv_sales_list_id = $orang->id;
        }

        $sales->save();
    }

    /** Kolom agency berisi "Direct" untuk menandai TANPA agency. */
    private static function normalizeAgency(mixed $nilai): ?string
    {
        $teks = trim((string) $nilai);

        return ($teks === '' || Str::lower($teks) === 'direct') ? null : $teks;
    }

    /**
     * Stage/Status di sheet → SalesStatus. Nilai yang tidak dikenali dibiarkan
     * null supaya tidak diam-diam mendarat di kolom kanban yang salah.
     */
    private function normalizeStatus(mixed $nilai): ?SalesStatus
    {
        $teks = self::normalize((string) $nilai);

        if ($teks === '') {
            return null;
        }

        $status = self::petaStatus($teks);

        if (! $status) {
            $this->statusTakDikenal[] = trim((string) $nilai);
        }

        return $status;
    }

    private static function petaStatus(string $teks): ?SalesStatus
    {
        // Istilah yang hanya ada di sheet lama / tab KOL Planning.
        $khusus = match ($teks) {
            'finish paid', 'paid' => SalesStatus::PAID,
            'invoicing' => SalesStatus::INVOICING,
            'report', 'reporting' => SalesStatus::REPORTING,
            'media plan' => SalesStatus::PROPOSAL_BUILDING,
            'close lost' => SalesStatus::CLOSE_LOSE,
            'on going', 'ongoing' => SalesStatus::CAMPAIGN_LIVE,
            default => null,
        };

        if ($khusus) {
            return $khusus;
        }

        // Sisanya kosakata dropdown STATUS di sheet BD. Pengenalan tulisannya
        // (en dash, huruf besar-kecil, "WON" saja) diurus ClientStatus supaya
        // tidak ada dua daftar ejaan yang harus dijaga bersamaan.
        return match (ClientStatus::fromSheet($teks)) {
            ClientStatus::LOST => SalesStatus::CLOSE_LOSE,
            ClientStatus::WON_ON_GOING => SalesStatus::CAMPAIGN_LIVE,
            // Campaign-nya sudah kelar; apakah sudah dibayar tidak dinyatakan
            // sheet, jadi berhenti di Reporting — bukan Paid.
            ClientStatus::FINISH => SalesStatus::REPORTING,
            ClientStatus::ON_PROGRESS, ClientStatus::REVISION => SalesStatus::PROPOSAL_BUILDING,
            // Empat-empatnya deal yang sudah di tangan client dan menunggu
            // keputusannya — satu kolom kanban yang sama.
            ClientStatus::SENT_PARALLEL,
            ClientStatus::COMPLETE_SENT_TO_CLIENT,
            ClientStatus::AWAITING_FEEDBACK,
            ClientStatus::HOLD => SalesStatus::NEGOTIATION,
            default => null,
        };
    }
}
