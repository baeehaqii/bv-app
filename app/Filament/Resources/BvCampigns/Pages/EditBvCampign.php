<?php

namespace App\Filament\Resources\BvCampigns\Pages;

use App\Filament\Resources\BvCampigns\BvCampignResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditBvCampign extends EditRecord
{
    protected static string $resource = BvCampignResource::class;

    protected function mutateFormDataBeforeFill(array $data): array
    {
        // Load KOLs and populate toggle + creator fields
        $kols = $this->record->kols;

        // Group KOLs by platform and content_type
        $groupedKols = $kols->groupBy(function ($kol) {
            return $kol->platform . '_' . $kol->content_type;
        });

        // Helper to map DB record to Form Data
        $mapToForm = function ($kols) {
            return $kols->map(function ($kol) {
                return [
                    'creator_name' => $kol->creator_name,
                    'url' => $kol->post_url,
                    'price' => number_format($kol->price, 0, ',', '.'),
                ];
            })->toArray();
        };

        // Instagram Reels
        if ($groupedKols->has('instagram_reels')) {
            $data['instagram_reels_enabled'] = true;
            $data['instagram_reels_creators'] = $mapToForm($groupedKols['instagram_reels']);
        }

        // Instagram Feed
        if ($groupedKols->has('instagram_feed')) {
            $data['instagram_feed_enabled'] = true;
            $data['instagram_feed_creators'] = $mapToForm($groupedKols['instagram_feed']);
        }

        // Instagram Story
        if ($groupedKols->has('instagram_story')) {
            $data['instagram_story_enabled'] = true;
            $data['instagram_story_creators'] = $mapToForm($groupedKols['instagram_story']);
        }

        // TikTok Video
        if ($groupedKols->has('tiktok_video')) {
            $data['tiktok_video_enabled'] = true;
            $data['tiktok_video_creators'] = $mapToForm($groupedKols['tiktok_video']);
        }

        // TikTok Story
        if ($groupedKols->has('tiktok_story')) {
            $data['tiktok_story_enabled'] = true;
            $data['tiktok_story_creators'] = $mapToForm($groupedKols['tiktok_story']);
        }

        // TikTok Photos
        if ($groupedKols->has('tiktok_photos')) {
            $data['tiktok_photos_enabled'] = true;
            $data['tiktok_photos_creators'] = $mapToForm($groupedKols['tiktok_photos']);
        }

        // YouTube Short
        if ($groupedKols->has('youtube_short')) {
            $data['youtube_short_enabled'] = true;
            $data['youtube_short_creators'] = $mapToForm($groupedKols['youtube_short']);
        }

        // YouTube Video
        if ($groupedKols->has('youtube_video')) {
            $data['youtube_video_enabled'] = true;
            $data['youtube_video_creators'] = $mapToForm($groupedKols['youtube_video']);
        }


        return $data;
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        // Process similar to CreateBvCampign
        $platforms = [];

        if (!empty($data['instagram_reels_enabled']) || !empty($data['instagram_feed_enabled']) || !empty($data['instagram_story_enabled'])) {
            $platforms[] = 'instagram';
        }
        if (!empty($data['tiktok_video_enabled']) || !empty($data['tiktok_photos_enabled']) || !empty($data['tiktok_story_enabled'])) {
            $platforms[] = 'tiktok';
        }
        if (!empty($data['youtube_short_enabled']) || !empty($data['youtube_video_enabled'])) {
            $platforms[] = 'youtube';
        }

        $data['media_platforms'] = $platforms;

        // Calculate total cost
        $totalCost = 0;
        $creatorFields = [
            'instagram_reels_creators',
            'instagram_feed_creators',
            'instagram_story_creators',
            'tiktok_video_creators',
            'tiktok_photos_creators',
            'tiktok_story_creators',
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

        if ($totalCost > 0) {
            $data['total_cost'] = $totalCost;
        }

        // Remove temporary fields
        unset(
            $data['instagram_reels_enabled'],
            $data['instagram_feed_enabled'],
            $data['instagram_story_enabled'],
            $data['tiktok_video_enabled'],
            $data['tiktok_photos_enabled'],
            $data['tiktok_story_enabled'],
            $data['youtube_short_enabled'],
            $data['youtube_video_enabled'],
            $data['instagram_reels_creators'],
            $data['instagram_feed_creators'],
            $data['instagram_story_creators'],
            $data['tiktok_video_creators'],
            $data['tiktok_photos_creators'],
            $data['tiktok_story_creators'],
            $data['youtube_short_creators'],
            $data['youtube_video_creators']
        );

        return $data;
    }

    protected function afterSave(): void
    {
        // Delete existing KOLs
        $this->record->kols()->delete();

        // Recreate KOLs from form data
        $data = $this->data;

        $creatorMappings = [
            'instagram_reels_creators' => ['platform' => 'instagram', 'content_type' => 'reels'],
            'instagram_feed_creators' => ['platform' => 'instagram', 'content_type' => 'feed'],
            'instagram_story_creators' => ['platform' => 'instagram', 'content_type' => 'story'],
            'tiktok_video_creators' => ['platform' => 'tiktok', 'content_type' => 'video'],
            'tiktok_photos_creators' => ['platform' => 'tiktok', 'content_type' => 'photos'],
            'tiktok_story_creators' => ['platform' => 'tiktok', 'content_type' => 'story'],
            'youtube_short_creators' => ['platform' => 'youtube', 'content_type' => 'short'],
            'youtube_video_creators' => ['platform' => 'youtube', 'content_type' => 'video'],
        ];

        foreach ($creatorMappings as $field => $mapping) {
            if (!empty($data[$field])) {
                foreach ($data[$field] as $creator) {
                    \App\Models\BvCampaignKol::create([
                        'campaign_id' => $this->record->id,
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
    }

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
