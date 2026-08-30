<?php

namespace App\Service;

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
        return 'Pipeline (Sales Activity Tracker)';
    }

    public function defaultSheetName(): ?string
    {
        return 'Pipeline';
    }

    public function aliases(): array
    {
        return [
            // "Campagin Name" — salah ketik di sheet, sengaja ikut diterima.
            'event_name' => ['campagin name', 'campaign name', 'nama campaign', 'event name'],
            'company_name' => ['client brand', 'client', 'brand', 'nama brand'],
            'related_client_name' => ['brand agency', 'agency'],
            'sales' => ['pic'],
            'status' => ['stage status', 'stage', 'status'],
            'budget_propose' => ['amount idr', 'amount', 'budget'],
            'deal_value' => ['amount deals', 'deal value'],
            'close_date' => ['deadline date', 'deadline', 'close date'],
            'bulan' => ['months', 'month', 'bulan'],
        ];
    }

    public function previewColumns(): array
    {
        return ['event_name', 'company_name', 'related_client_name', 'sales', 'status', 'budget_propose', 'deal_value', 'close_date'];
    }

    protected function requiredField(): string
    {
        return 'event_name';
    }

    protected function refine(array $item): array
    {
        $item['related_client_name'] = self::normalizeAgency($item['related_client_name'] ?? null);
        $item['status'] = self::normalizeStatus($item['status'] ?? null);
        $item['budget_propose'] = self::toNumber($item['budget_propose'] ?? null);
        $item['deal_value'] = self::toNumber($item['deal_value'] ?? null);
        $item['close_date'] = self::toDate($item['close_date'] ?? null);

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

        foreach (['related_client_name', 'budget_propose', 'deal_value', 'close_date',
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
            $orang = BvSalesList::firstOrCreate(['nama_sales' => $nama]);

            if ($orang->wasRecentlyCreated) {
                $hasil['notes'][] = "Sales baru dibuat di master: \"{$nama}\".";
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
    private static function normalizeStatus(mixed $nilai): ?SalesStatus
    {
        $teks = self::normalize((string) $nilai);

        return match ($teks) {
            'finish paid', 'paid' => SalesStatus::PAID,
            'lost', 'close lost' => SalesStatus::CLOSE_LOSE,
            'invoicing' => SalesStatus::INVOICING,
            'won on going', 'on going', 'ongoing' => SalesStatus::CAMPAIGN_LIVE,
            'awaiting feedback' => SalesStatus::NEGOTIATION,
            'revision' => SalesStatus::PROPOSAL_BUILDING,
            'media plan' => SalesStatus::PROPOSAL_BUILDING,
            'report', 'reporting' => SalesStatus::REPORTING,
            default => null,
        };
    }
}
