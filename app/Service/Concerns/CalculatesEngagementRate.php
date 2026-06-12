<?php

namespace App\Service\Concerns;

/**
 * Perhitungan Engagement Rate standar industri.
 *
 * Anomali sebelumnya: ER selalu dibagi followers. Untuk konten video yang
 * jangkauannya melampaui followers (FYP/Explore/recommended), engagement bisa
 * melebihi followers sehingga ER > 100% (tidak valid).
 *
 * Standar yang dipakai di sini:
 * - Konten video (views tersedia): ER = engagement / views × 100  (ER by views/reach)
 * - Konten foto/feed (views = 0)  : ER = engagement / followers × 100 (ER by followers)
 * - ER dihitung per-post lalu dirata-rata, kemudian di-clamp 0–100%.
 */
trait CalculatesEngagementRate
{
    /**
     * Hitung ER rata-rata dari sekumpulan post.
     *
     * @param  array<int, array{engagement: float|int, views?: float|int|null}>  $posts
     */
    protected function averageEngagementRate(array $posts, int $followersCount): float
    {
        if (empty($posts) || $followersCount <= 0) {
            return 0.0;
        }

        $rates = [];

        foreach ($posts as $post) {
            $engagement = (float) ($post['engagement'] ?? 0);
            $views = (float) ($post['views'] ?? 0);

            $rate = $this->engagementRateForPost($engagement, $views, $followersCount);

            if ($rate !== null) {
                $rates[] = $rate;
            }
        }

        if (empty($rates)) {
            return 0.0;
        }

        return round(array_sum($rates) / count($rates), 2);
    }

    /**
     * ER satu post: basis views jika konten video, selain itu basis followers.
     * Hasil di-clamp ke 0–100% agar tidak ada anomali.
     */
    protected function engagementRateForPost(float $engagement, float $views, int $followersCount): ?float
    {
        $basis = $views > 0 ? $views : (float) $followersCount;

        if ($basis <= 0) {
            return null;
        }

        $rate = ($engagement / $basis) * 100;

        return max(0.0, min(100.0, $rate));
    }
}
