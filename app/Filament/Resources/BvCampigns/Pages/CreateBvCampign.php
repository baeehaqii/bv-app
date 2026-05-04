<?php

namespace App\Filament\Resources\BvCampigns\Pages;

use App\Filament\Resources\BvCampigns\BvCampignResource;
use App\Jobs\ScrapeKolMetricsJob;
use App\Models\BvCampaignKol;
use App\Service\CampaignNotificationService;
use Filament\Resources\Pages\CreateRecord;

class CreateBvCampign extends CreateRecord
{
    protected static string $resource = BvCampignResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $entries = $data['kol_entries'] ?? [];

        // Derive media_platforms from channel values
        $data['media_platforms'] = collect($entries)
            ->pluck('channel')
            ->filter()
            ->map(fn($ch) => explode('_', $ch, 2)[0])
            ->unique()
            ->values()
            ->toArray();

        // Calculate total cost from entries
        $totalCost = collect($entries)->sum(
            fn($e) => (float) str_replace(['.', ','], '', $e['price'] ?? '0')
        );

        if ($totalCost > 0 && empty($data['total_cost'])) {
            $data['total_cost'] = $totalCost;
        }

        unset($data['kol_entries']);

        return $data;
    }

    protected function afterCreate(): void
    {
        $record = $this->record;
        $entries = $this->data['kol_entries'] ?? [];

        foreach ($entries as $entry) {
            $channel = $entry['channel'] ?? '';
            [$platform, $contentType] = array_pad(explode('_', $channel, 2), 2, '');

            if (!$platform || !$contentType) {
                continue;
            }

            BvCampaignKol::create([
                'campaign_id' => $record->id,
                'creator_name' => $entry['creator_name'] ?? '',
                'post_url' => $entry['url'] ?? null,
                'price' => (float) str_replace(['.', ','], '', $entry['price'] ?? '0'),
                'platform' => $platform,
                'content_type' => $contentType,
                'status' => 'pending',
            ]);
        }

        // Antrekan scraping metrics untuk setiap KOL yang memiliki URL
        $record->kols()
            ->whereNotNull('post_url')
            ->where('post_url', '!=', '')
            ->get()
            ->each(fn($kol) => ScrapeKolMetricsJob::dispatch($kol->id)->onQueue('scraping'));

        // CP-07: Kirim notifikasi Email & WhatsApp setelah campaign dibuat
        try {
            CampaignNotificationService::notify($record);
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::warning('[CreateBvCampign] Notifikasi gagal: ' . $e->getMessage());
        }

        // MP-18: Auto Create Media Plan (Internal)
        try {
            if (!\App\Models\MediaPlan::where('campaign_name', $record->campaign_name)->exists()) {
                $client = $record->client;
                $salesActivity = $record->salesActivity;

                $mediaPlan = \App\Models\MediaPlan::create([
                    'brand' => $client?->nama_brand ?? '-',
                    'pic_client' => collect($client?->pic_clients ?? [])->first()['name'] ?? '-',
                    'quotation_number' => \App\Helpers\QuotationNumberGenerator::generate(),
                    'campaign_type' => 'Content Creation', // Default
                    'campaign_name' => $record->campaign_name,
                    'campaign_period_start' => $record->start_date ? $record->start_date->format('d/m/Y') : now()->format('d/m/Y'),
                    'campaign_period_end' => $record->end_date ? $record->end_date->format('d/m/Y') : now()->addMonths(1)->format('d/m/Y'),
                    'platform' => implode(', ', $record->media_platforms ?? ['Digital']),
                    'domisili' => '-',
                    'pic_campaign_id' => $salesActivity?->bv_sales_list_id ?? null,
                    'margin_type' => 'custom',
                    'use_global_margin' => true,
                ]);

                // Auto create Internal Budget (Draft)
                $mediaPlan->internalBudget()->create([
                    'status' => 'draft',
                ]);
            }
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::error('[CreateBvCampign] Gagal auto create Media Plan: ' . $e->getMessage());
        }
    }

    protected static bool $canCreateAnother = false;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
