<?php

namespace App\Service;

use App\Models\BvCampign;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

/**
 * Service untuk mengirim notifikasi saat Campaign berhasil dibuat.
 *
 * Logika (CP-07):
 *  - Jika campaign item = "Influencer" → kirim ke Manager KOL & Email Grup
 *  - Jika campaign item = "Social Media" → kirim ke Media Creative
 */
class CampaignNotificationService
{
    /**
     * Konfigurasi penerima notifikasi — sesuaikan email/WA di sini.
     * Ke depan bisa diambil dari tabel settings atau konfigurasi.
     */
    protected static array $recipients = [
        'influencer' => [
            'emails' => [
                // Manager KOL & Email Grup
                // 'manager.kol@beyondviral.id',
                // 'grup-kol@beyondviral.id',
            ],
            'phones' => [
                // WhatsApp Manager KOL
                // '08xxxxxxxxxx',
            ],
            'label' => 'Manager KOL & Email Grup',
        ],
        'social_media' => [
            'emails' => [
                // Media Creative team
                // 'media.creative@beyondviral.id',
            ],
            'phones' => [
                // WhatsApp Media Creative
                // '08xxxxxxxxxx',
            ],
            'label' => 'Media Creative',
        ],
    ];

    /**
     * Kirim notifikasi berdasarkan platform/item campaign.
     */
    public static function notify(BvCampign $campaign): void
    {
        $platforms = $campaign->media_platforms ?? [];
        $campaignName = $campaign->campaign_name;
        $clientName = $campaign->client?->nama_brand ?? '-';

        // Deteksi tipe campaign item dari platform yang dipilih
        $hasInfluencer = static::hasInfluencerPlatforms($platforms);
        $hasSocialMedia = static::hasSocialMediaPlatforms($platforms);

        if ($hasInfluencer) {
            static::sendNotification('influencer', $campaign, $campaignName, $clientName);
        }

        if ($hasSocialMedia) {
            static::sendNotification('social_media', $campaign, $campaignName, $clientName);
        }

        if (!$hasInfluencer && !$hasSocialMedia) {
            Log::info('[CampaignNotification] Campaign dibuat tanpa item Influencer/Social Media', [
                'campaign_id' => $campaign->id,
                'platforms' => $platforms,
            ]);
        }
    }

    /**
     * Kirim email & WhatsApp ke grup penerima.
     */
    protected static function sendNotification(string $type, BvCampign $campaign, string $campaignName, string $clientName): void
    {
        $config = static::$recipients[$type] ?? null;
        if (!$config) {
            return;
        }

        $message = static::buildMessage($type, $campaign, $campaignName, $clientName);

        // Kirim Email
        foreach ($config['emails'] as $email) {
            try {
                Mail::raw($message, function ($mail) use ($email, $campaignName) {
                    $mail->to($email)
                        ->subject("🚀 Campaign Baru: {$campaignName}");
                });
            } catch (\Throwable $e) {
                Log::warning("[CampaignNotification] Gagal kirim email ke {$email}: " . $e->getMessage());
            }
        }

        // Kirim WhatsApp
        WhatsAppService::sendBulk($config['phones'], $message);

        Log::info("[CampaignNotification] Notifikasi '{$type}' dikirim", [
            'campaign_id' => $campaign->id,
            'target' => $config['label'],
            'emails' => count($config['emails']),
            'phones' => count($config['phones']),
        ]);
    }

    /**
     * Bangun isi pesan notifikasi.
     */
    protected static function buildMessage(string $type, BvCampign $campaign, string $campaignName, string $clientName): string
    {
        $label = match ($type) {
            'influencer' => 'Influencer / KOL',
            'social_media' => 'Social Media',
            default => ucfirst($type),
        };

        $dealValue = $campaign->deal_value
            ? 'Rp ' . number_format($campaign->deal_value, 0, ',', '.')
            : '-';

        $period = '';
        if ($campaign->start_date && $campaign->end_date) {
            $period = $campaign->start_date->format('d M Y') . ' – ' . $campaign->end_date->format('d M Y');
        }

        return "🚀 *Campaign Baru Dibuat!*\n\n"
            . "📌 *Nama Campaign:* {$campaignName}\n"
            . "🏢 *Client:* {$clientName}\n"
            . "📋 *Tipe:* {$label}\n"
            . "💰 *Deal Value:* {$dealValue}\n"
            . ($period ? "📅 *Periode:* {$period}\n" : '')
            . "👤 *PIC Internal:* " . ($campaign->pic_internal ?? '-') . "\n"
            . "\nSilakan cek detail di panel admin.";
    }

    /**
     * Cek apakah ada platform yang termasuk kategori Influencer.
     */
    protected static function hasInfluencerPlatforms(array $platforms): bool
    {
        $influencerPlatforms = ['instagram', 'tiktok', 'youtube'];
        return !empty(array_intersect($platforms, $influencerPlatforms));
    }

    /**
     * Cek apakah ada platform yang termasuk kategori Social Media.
     * Logika: jika ada item campaign bertipe "social_media" atau
     * campaign_type mengindikasikan social media management.
     */
    protected static function hasSocialMediaPlatforms(array $platforms): bool
    {
        $socialMediaPlatforms = ['social_media', 'social-media'];
        return !empty(array_intersect($platforms, $socialMediaPlatforms));
    }
}
