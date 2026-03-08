<?php

namespace App\Filament\Resources\BvCampigns\Pages;

use App\Filament\Resources\BvCampigns\BvCampignResource;
use App\Models\BvCampaignKol;
use App\Service\CampaignNotificationService;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;

class CreateBvCampign extends CreateRecord
{
    protected static string $resource = BvCampignResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // Collect media platforms
        $platforms = [];

        if (!empty($data['instagram_reels_enabled']) || !empty($data['instagram_feed_enabled'])) {
            $platforms[] = 'instagram';
        }
        if (!empty($data['tiktok_video_enabled']) || !empty($data['tiktok_photos_enabled'])) {
            $platforms[] = 'tiktok';
        }
        if (!empty($data['youtube_short_enabled']) || !empty($data['youtube_video_enabled'])) {
            $platforms[] = 'youtube';
        }

        $data['media_platforms'] = $platforms;

        // Calculate total cost from all creators
        $totalCost = 0;
        $creatorFields = [
            'instagram_reels_creators',
            'instagram_feed_creators',
            'tiktok_video_creators',
            'tiktok_photos_creators',
            'youtube_short_creators',
            'youtube_video_creators',
        ];

        foreach ($creatorFields as $field) {
            if (!empty($data[$field])) {
                foreach ($data[$field] as $creator) {
                    $price = str_replace(['.', ','], '', $creator['price'] ?? '0');
                    $totalCost += (double) $price;
                }
            }
        }

        if ($totalCost > 0 && empty($data['total_cost'])) {
            $data['total_cost'] = $totalCost;
        }

        // Remove fields that should not be saved to bv_campaigns table
        // These are temporary form fields used to collect creator data
        unset(
            $data['instagram_reels_enabled'],
            $data['instagram_feed_enabled'],
            $data['tiktok_video_enabled'],
            $data['tiktok_photos_enabled'],
            $data['youtube_short_enabled'],
            $data['youtube_video_enabled'],
            $data['instagram_reels_creators'],
            $data['instagram_feed_creators'],
            $data['tiktok_video_creators'],
            $data['tiktok_photos_creators'],
            $data['youtube_short_creators'],
            $data['youtube_video_creators']
        );

        return $data;
    }

    protected function afterCreate(): void
    {
        $record = $this->record;
        $data = $this->data;

        // Save KOL creators
        $creatorMappings = [
            'instagram_reels_creators' => ['platform' => 'instagram', 'content_type' => 'reels'],
            'instagram_feed_creators' => ['platform' => 'instagram', 'content_type' => 'feed'],
            'tiktok_video_creators' => ['platform' => 'tiktok', 'content_type' => 'video'],
            'tiktok_photos_creators' => ['platform' => 'tiktok', 'content_type' => 'photos'],
            'youtube_short_creators' => ['platform' => 'youtube', 'content_type' => 'short'],
            'youtube_video_creators' => ['platform' => 'youtube', 'content_type' => 'video'],
        ];

        foreach ($creatorMappings as $field => $mapping) {
            if (!empty($data[$field])) {
                foreach ($data[$field] as $creator) {
                    BvCampaignKol::create([
                        'campaign_id' => $record->id,
                        'creator_name' => $creator['creator_name'] ?? '',
                        'post_url' => $creator['url'] ?? null,
                        'price' => (double) str_replace(['.', ','], '', $creator['price'] ?? '0'),
                        'platform' => $mapping['platform'],
                        'content_type' => $mapping['content_type'],
                        'status' => 'pending',
                    ]);
                }
            }
        }

        // CP-07: Kirim notifikasi Email & WhatsApp setelah campaign dibuat
        try {
            CampaignNotificationService::notify($record);
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::warning('[CreateBvCampign] Notifikasi gagal: ' . $e->getMessage());
        }

        // MP-18: Auto Create Media Plan Internal
        try {
            if (!\App\Models\MediaPlan::where('campaign_name', $record->campaign_name)->exists()) {
                $client = $record->client;
                $salesActivity = $record->salesActivity;

                $mediaPlan = \App\Models\MediaPlan::create([
                    'brand' => $client?->nama_brand ?? '-',
                    'pic_client' => $client?->nama_pic ?? '-',
                    'quotation_number' => \App\Helpers\QuotationNumberGenerator::generate(),
                    'campaign_type' => 'Content Creation', // Default
                    'campaign_name' => $record->campaign_name,
                    'campaign_period_start' => $record->start_date ? $record->start_date->format('d/m/Y') : now()->format('d/m/Y'),
                    'campaign_period_end' => $record->end_date ? $record->end_date->format('d/m/Y') : now()->addMonths(1)->format('d/m/Y'),
                    'platform' => implode(', ', $record->media_platforms ?? ['Digital']),
                    'domisili' => '-',
                    'pic_campaign_id' => $salesActivity?->bv_sales_list_id ?? null,
                    'margin_type' => 'auto',
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
