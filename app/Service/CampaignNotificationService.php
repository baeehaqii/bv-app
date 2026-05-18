<?php

namespace App\Service;

use App\Models\BvCampign;

/**
 * Adapter tipis untuk event "Campaign Ongoing dibuat".
 * Semua logika notifikasi ada di BvNotificationService.
 */
class CampaignNotificationService
{
    public static function notify(BvCampign $campaign): void
    {
        $platforms = $campaign->media_platforms ?? [];

        $hasInfluencer  = static::hasInfluencerPlatforms($platforms);
        $hasSocialMedia = static::hasSocialMediaPlatforms($platforms);

        $svc = app(BvNotificationService::class);

        if ($hasInfluencer) {
            $svc->campaignCreated($campaign, 'influencer');
        }

        if ($hasSocialMedia) {
            $svc->campaignCreated($campaign, 'social_media');
        }

        if (!$hasInfluencer && !$hasSocialMedia) {
            // Campaign tanpa kategori platform yang dikenal — tetap notif sebagai generic
            $svc->campaignCreated($campaign, 'other');
        }
    }

    private static function hasInfluencerPlatforms(array $platforms): bool
    {
        return !empty(array_intersect($platforms, ['instagram', 'tiktok', 'youtube']));
    }

    private static function hasSocialMediaPlatforms(array $platforms): bool
    {
        return !empty(array_intersect($platforms, ['social_media', 'social-media']));
    }
}
