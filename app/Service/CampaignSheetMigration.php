<?php

namespace App\Service;

use App\Models\BvCampign;
use App\Models\DataClient;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Migrasi tab Campaigns → BvCampign (menu Campaign Ongoing Internal).
 *
 * Campaign WAJIB menempel ke baris DataClient yang sudah ada — itu yang membuat
 * "client ini punya campaign apa saja" bisa dibaca sistem. Client yang belum ada
 * dibuat, tapi kalau namanya cuma beda tipis dari client yang sudah ada
 * (mis. "ITDC - Injouney" vs "ITDC - Injourney") itu dilaporkan sebagai
 * kemungkinan salah ketik — bukan digabung diam-diam.
 */
class CampaignSheetMigration extends SheetMigration
{
    public function label(): string
    {
        return 'Campaign (Campaign Ongoing Internal)';
    }

    public function defaultSheetName(): ?string
    {
        return 'Campaigns';
    }

    public function aliases(): array
    {
        return [
            'campaign_name' => ['campaign name', 'nama campaign', 'campaign'],
            'client' => ['client', 'client brand', 'nama brand', 'brand'],
            'brand_agency' => ['brand agency', 'agency', 'brand agency company'],
            'pic' => ['pic', 'pic am'],
            'pic_media_plan' => ['pic kol on going', 'pic kol'],
            'start_date' => ['start date', 'tanggal mulai'],
            'end_date' => ['end date', 'tanggal selesai'],
            'close_date' => ['deal date'],
            'deal_value' => ['budget deals idr', 'budget deals', 'deal value', 'revenue deals'],
            'total_cost' => ['real cost idr', 'real cost', 'total cost', 'actual real cost tax kol'],
            'status' => ['status campaign', 'status', 'project status'],
            'report_link' => ['ext link', 'link report'],
        ];
    }

    public function previewColumns(): array
    {
        return ['campaign_name', 'client', 'brand_agency', 'pic', 'pic_media_plan', 'start_date', 'end_date', 'deal_value', 'total_cost', 'status'];
    }

    protected function requiredField(): string
    {
        return 'campaign_name';
    }

    protected function refine(array $item): array
    {
        $item['brand_agency'] = self::normalizeAgency($item['brand_agency'] ?? null);
        $item['start_date'] = self::toDate($item['start_date'] ?? null);
        $item['close_date'] = self::toDate($item['close_date'] ?? null);
        $item['end_date'] = self::toDate($item['end_date'] ?? null);
        $item['deal_value'] = self::toNumber($item['deal_value'] ?? null);
        $item['total_cost'] = self::toNumber($item['total_cost'] ?? null);
        $item['status'] = self::normalizeStatus($item['status'] ?? null);

        return $item;
    }

    public function persist(array $items): array
    {
        $hasil = ['success' => 0, 'skipped' => 0, 'failed' => 0, 'notes' => []];

        DB::transaction(function () use ($items, &$hasil) {
            foreach ($items as $item) {
                if (blank($item['campaign_name'] ?? null)) {
                    $hasil['skipped']++;
                    $hasil['notes'][] = "Baris {$item['_row']}: nama campaign kosong, dilewati.";

                    continue;
                }

                try {
                    $this->simpanSatu($item, $hasil);
                    $hasil['success']++;
                } catch (\Throwable $e) {
                    $hasil['failed']++;
                    $hasil['notes'][] = "Baris {$item['_row']} ({$item['campaign_name']}): {$e->getMessage()}";
                }
            }
        });

        return $hasil;
    }

    private function simpanSatu(array $item, array &$hasil): void
    {
        $client = filled($item['client'] ?? null)
            ? $this->clientUntuk(trim((string) $item['client']), $hasil, $item['_row'])
            : null;

        if (! $client) {
            $hasil['notes'][] = "Baris {$item['_row']}: kolom Client kosong, campaign tidak tertaut ke client mana pun.";
        }

        $campaign = BvCampign::firstOrNew([
            'campaign_name' => $item['campaign_name'],
            'client_id' => $client?->id,
        ]);

        $campaign->campaign_type = BvCampign::TYPE_INTERNAL;

        foreach (['start_date', 'end_date', 'close_date', 'deal_value', 'total_cost', 'status', 'report_link'] as $field) {
            if (($item[$field] ?? null) !== null && $item[$field] !== '') {
                $campaign->{$field} = $item[$field];
            }
        }

        if ($client) {
            $campaign->client_type = $client->type;
        }

        // Kolom "Brand/Agency" di sheet: "Direct" atau nama agency yang menangani.
        if (filled($item['brand_agency'] ?? null)) {
            $campaign->agency_name = $item['brand_agency'];
        }

        if (filled($item['pic'] ?? null)) {
            $campaign->pic_internal = trim((string) $item['pic']);
        }

        if (filled($item['pic_media_plan'] ?? null)) {
            $campaign->pic_media_plan = trim((string) $item['pic_media_plan']);
        }

        $campaign->save();
    }

    /**
     * Baris DataClient untuk nama ini. Dibuat kalau belum ada, tapi nama yang
     * cuma beda tipis dari yang sudah ada diperingatkan lebih dulu.
     */
    private function clientUntuk(string $nama, array &$hasil, int $baris): DataClient
    {
        $ada = DataClient::where('nama_brand', $nama)->orderByRaw("type = 'direct' desc")->first();

        if ($ada) {
            return $ada;
        }

        $mirip = self::miripDengan($nama, DataClient::pluck('nama_brand'));

        if ($mirip) {
            $hasil['notes'][] = "Baris {$baris}: \"{$nama}\" mirip sekali dengan client yang sudah ada "
                . "\"{$mirip}\" — kemungkinan salah ketik di sheet. Baris baru tetap dibuat, "
                . 'periksa dan gabungkan bila perlu.';
        }

        $hasil['notes'][] = "Client baru dibuat: \"{$nama}\".";

        return DataClient::create(['nama_brand' => $nama, 'type' => 'direct']);
    }

    private static function normalizeAgency(mixed $nilai): ?string
    {
        $teks = trim((string) $nilai);

        return ($teks === '' || Str::lower($teks) === 'direct') ? null : $teks;
    }

    private static function normalizeStatus(mixed $nilai): ?string
    {
        return match (self::normalize((string) $nilai)) {
            'finish paid', 'paid', 'selesai', 'finish' => 'completed',
            'on going', 'ongoing', 'won on going' => 'ongoing',
            'report', 'reporting', 'waiting payment', 'invoicing', 'invoice' => 'ongoing',
            'cancelled', 'batal' => 'cancelled',
            'upcoming' => 'upcoming',
            default => null,
        };
    }
}
