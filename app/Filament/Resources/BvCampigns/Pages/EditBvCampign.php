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

        // Instagram Reels
        if ($groupedKols->has('instagram_reels')) {
            $data['instagram_reels_enabled'] = true;
            $data['instagram_reels_creators'] = $groupedKols['instagram_reels']->map(function ($kol) {
                return [
                    'creator_name' => $kol->creator_name,
                    'url' => $kol->post_url,
                    'price' => (int) $kol->price,
                ];
            })->toArray();
        }

        // Instagram Feed
        if ($groupedKols->has('instagram_feed')) {
            $data['instagram_feed_enabled'] = true;
            $data['instagram_feed_creators'] = $groupedKols['instagram_feed']->map(function ($kol) {
                return [
                    'creator_name' => $kol->creator_name,
                    'url' => $kol->post_url,
                    'price' => (int) $kol->price,
                ];
            })->toArray();
        }

        // TikTok Video
        if ($groupedKols->has('tiktok_video')) {
            $data['tiktok_video_enabled'] = true;
            $data['tiktok_video_creators'] = $groupedKols['tiktok_video']->map(function ($kol) {
                return [
                    'creator_name' => $kol->creator_name,
                    'url' => $kol->post_url,
                    'price' => (int) $kol->price,
                ];
            })->toArray();
        }

        // TikTok Photos
        if ($groupedKols->has('tiktok_photos')) {
            $data['tiktok_photos_enabled'] = true;
            $data['tiktok_photos_creators'] = $groupedKols['tiktok_photos']->map(function ($kol) {
                return [
                    'creator_name' => $kol->creator_name,
                    'url' => $kol->post_url,
                    'price' => (int) $kol->price,
                ];
            })->toArray();
        }

        // YouTube Short
        if ($groupedKols->has('youtube_short')) {
            $data['youtube_short_enabled'] = true;
            $data['youtube_short_creators'] = $groupedKols['youtube_short']->map(function ($kol) {
                return [
                    'creator_name' => $kol->creator_name,
                    'url' => $kol->post_url,
                    'price' => (int) $kol->price,
                ];
            })->toArray();
        }

        // YouTube Video
        if ($groupedKols->has('youtube_video')) {
            $data['youtube_video_enabled'] = true;
            $data['youtube_video_creators'] = $groupedKols['youtube_video']->map(function ($kol) {
                return [
                    'creator_name' => $kol->creator_name,
                    'url' => $kol->post_url,
                    'price' => (int) $kol->price,
                ];
            })->toArray();
        }


        return $data;
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        // Process similar to CreateBvCampign
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

        // Calculate total cost
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

        if ($totalCost > 0) {
            $data['total_cost'] = $totalCost;
        }

        // Remove temporary fields
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

    protected function afterSave(): void
    {
        // Delete existing KOLs
        $this->record->kols()->delete();

        // Recreate KOLs from form data
        $data = $this->data;

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
                    \App\Models\BvCampaignKol::create([
                        'campaign_id' => $this->record->id,
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

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
