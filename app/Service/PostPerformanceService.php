<?php

namespace App\Service;

use App\Models\BvCampaignKol;
use Illuminate\Support\Facades\Log;
use Exception;

/**
 * Unified service to fetch post performance from various platforms
 * Supports: Instagram, TikTok, YouTube (regular videos & shorts), Threads
 */
class PostPerformanceService
{
    protected InstagramService $instagramService;
    protected TiktokService $tiktokService;
    protected YoutubeChannelsService $youtubeService;
    protected ThreadsService $threadsService;

    public function __construct()
    {
        $this->instagramService = new InstagramService();
        $this->tiktokService = new TiktokService();
        $this->youtubeService = new YoutubeChannelsService();
        $this->threadsService = new ThreadsService();
    }

    /**
     * Detect platform from URL
     * 
     * @param string $url
     * @return string|null
     */
    public function detectPlatform(string $url): ?string
    {
        $url = strtolower($url);

        if (str_contains($url, 'instagram.com')) {
            return 'instagram';
        }

        if (str_contains($url, 'tiktok.com')) {
            return 'tiktok';
        }

        if (str_contains($url, 'youtube.com') || str_contains($url, 'youtu.be')) {
            return 'youtube';
        }

        if (str_contains($url, 'threads.com') || str_contains($url, 'threads.net')) {
            return 'threads';
        }

        return null;
    }

    /**
     * Fetch post performance from URL
     * Automatically detects platform and calls appropriate service
     * 
     * @param string $postUrl
     * @return array Performance data with keys: views, likes, comments, shares, saves, total_engagement
     * @throws Exception
     */
    public function fetchPerformance(string $postUrl): array
    {
        $platform = $this->detectPlatform($postUrl);

        if (!$platform) {
            throw new Exception("Unable to detect platform from URL: {$postUrl}");
        }

        Log::info('📊 Fetching post performance', [
            'url' => $postUrl,
            'platform' => $platform,
        ]);

        return match ($platform) {
            'instagram' => $this->instagramService->getPostStats($postUrl),
            'tiktok' => $this->tiktokService->getPostStats($postUrl),
            'youtube' => $this->youtubeService->getVideoStats($postUrl),
            'threads' => $this->threadsService->getPostStats($postUrl),
            default => throw new Exception("Unsupported platform: {$platform}"),
        };
    }

    /**
     * Fetch and update a single BvCampaignKol record
     * 
     * ER Calculation Logic:
     * - Instagram Reels/Video (has views): ER = (Like + Comment) / Views × 100
     * - Instagram Photo/Carousel (no views): ER = (Like + Comment) / Followers × 100
     * - TikTok/YouTube: ER = (Like + Comment) / Views × 100
     * 
     * @param BvCampaignKol $kol
     * @return BvCampaignKol Updated record
     * @throws Exception
     */
    /**
     * Metrik yang bisa datang dari scraping. Tidak semua platform menyediakan
     * semuanya — lihat CATATAN di bawah.
     */
    private const METRIK = ['views', 'likes', 'comments', 'shares', 'saves', 'reposts'];

    /**
     * Apa yang benar-benar dikembalikan tiap platform (diuji langsung ke API
     * ScrapeCreators, bukan dari dokumentasi):
     *
     *   TikTok    views, likes, comments, shares, saves  → lengkap
     *   Instagram views, likes, comments                 → TANPA shares/saves/followers
     *   YouTube   views, likes, comments                 → TANPA shares/saves
     *   Threads   views, likes, comments, shares         → TANPA saves
     *
     * Reach, impressions & reposts TIDAK tersedia di platform mana pun: itu
     * angka Insights yang hanya bisa dilihat pemilik akun, tidak lewat halaman
     * publik. Isinya datang dari migrasi sheet dan dijaga di sini.
     *
     * Karena itu metrik yang TIDAK dilaporkan dibiarkan apa adanya, bukan
     * ditulis 0. Menulis 0 berarti menghapus angka yang tadinya benar — hasil
     * migrasi sheet atau isian manual dari IG Insights — dan itu kehilangan
     * data, bukan pembaruan.
     */
    public function fetchAndUpdateKol(BvCampaignKol $kol): BvCampaignKol
    {
        if (empty($kol->post_url)) {
            throw new Exception("KOL {$kol->creator_name} has no post URL");
        }

        // Satu panggilan API bisa makan puluhan detik (terukur 2,8-5,1 dtk, batas
        // timeout-nya 40). Beri tiap postingan jatah waktunya sendiri, bukan satu
        // jatah untuk seluruh perulangan — penjaganya sama dengan jalur scraping
        // lain: jangan memasang batas di CLI yang tadinya tanpa batas.
        KolProfileImporter::perpanjangJatahWaktu();

        $platform = $this->detectPlatform($kol->post_url);
        $stats = $this->fetchPerformance($kol->post_url);

        $updateData = ['last_fetched_at' => now()];
        $dipertahankan = [];

        foreach (self::METRIK as $metrik) {
            $nilai = $stats[$metrik] ?? null;

            if (is_numeric($nilai) && $nilai > 0) {
                $updateData[$metrik] = (int) $nilai;
            } else {
                $dipertahankan[] = $metrik;
            }
        }

        // Nilai final = yang baru di-fetch, atau yang sudah ada bila platformnya
        // tidak melaporkan metrik itu.
        $final = fn (string $metrik): int => (int) ($updateData[$metrik] ?? $kol->{$metrik});

        // Engagement dihitung dari nilai FINAL, bukan cuma yang baru datang —
        // kalau tidak, shares/saves yang dipertahankan hilang dari totalnya.
        $updateData['total_engagement'] = $final('likes') + $final('comments')
            + $final('shares') + $final('saves') + $final('reposts');

        if ($platform) {
            $updateData['platform'] = $platform;
        }

        if (isset($stats['followers_count']) && $stats['followers_count'] > 0) {
            $updateData['followers_count'] = $stats['followers_count'];
        }

        if (isset($stats['content_type'])) {
            $updateData['content_type'] = $stats['content_type'];
        }

        // ER mengikuti definisi sheet KOL Insights: Engagement / Views, dengan
        // Engagement = like + comment + share + save. Dihitung DI SINI, bukan
        // diambil dari masing-masing service: cuma di sini nilai final diketahui
        // (termasuk shares/saves yang dipertahankan karena platformnya tidak
        // melaporkannya), dan cuma di sini rumusnya satu untuk semua platform.
        $views = $final('views');
        $followers = (int) ($updateData['followers_count'] ?? $kol->followers_count ?? 0);

        [$updateData['engagement_rate'], $updateData['er_type']] = match (true) {
            $views > 0 => [round(($updateData['total_engagement'] / $views) * 100, 4), 'views'],
            // Postingan tanpa views (foto/carousel Instagram) jatuh ke followers.
            $followers > 0 => [round(($updateData['total_engagement'] / $followers) * 100, 4), 'followers'],
            default => [(float) $kol->engagement_rate, $kol->er_type ?? 'views'],
        };

        $kol->update($updateData);

        Log::info('✅ KOL performance updated', [
            'kol_id' => $kol->id,
            'creator_name' => $kol->creator_name,
            'platform' => $platform,
            'diperbarui' => array_keys(array_diff_key($updateData, array_flip(['last_fetched_at']))),
            // Metrik yang platformnya tidak sediakan — nilai lamanya sengaja dijaga.
            'dipertahankan' => $dipertahankan,
            'engagement_rate' => $updateData['engagement_rate'],
        ]);

        // Retrieve History dicatat di sini, bukan di halaman — supaya jalur single
        // fetch, bulk fetch, dan pemanggilan dari kode lain semuanya ikut tercatat.
        $kol = $kol->fresh();
        $kol->recordSnapshot();

        return $kol;
    }

    /*
     * bulkFetchAndUpdate() DIHAPUS: itu jalur sekali-jalan yang memproses
     * seluruh postingan dalam satu request — persis penyebab request mati di
     * tengah jalan untuk campaign berisi puluhan postingan. Penggantinya trait
     * App\Filament\Concerns\MenarikPerformaBertahap yang memotongnya per
     * beberapa postingan, dengan progres dan galat per baris.
     */
}
