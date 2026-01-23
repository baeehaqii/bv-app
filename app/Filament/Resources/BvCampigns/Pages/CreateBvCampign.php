<?php

namespace App\Filament\Resources\BvCampigns\Pages;

use App\Filament\Resources\BvCampigns\BvCampignResource;
use App\Models\BvCampaignKol;
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
                    $totalCost += (int) ($creator['price'] ?? 0);
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
                        'price' => (int) ($creator['price'] ?? 0),
                        'platform' => $mapping['platform'],
                        'content_type' => $mapping['content_type'],
                        'status' => 'pending',
                    ]);
                }
            }
        }
    }

    protected static bool $canCreateAnother = false;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
