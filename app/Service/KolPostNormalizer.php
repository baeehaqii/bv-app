<?php

namespace App\Service;

/**
 * Menyamakan bentuk `recent_media` yang berbeda-beda di tiap service scraping
 * menjadi satu bentuk yang dipakai KOL Analyzer.
 *
 * Tiap service sudah punya parseRecentMedia()-nya sendiri dengan nama field yang
 * beda (Instagram pakai likes_count, Threads pakai likes, YouTube pakai
 * likeCountInt yang sudah dipetakan ke likes_count, dst). Pengetahuan pemetaan
 * itu dikumpulkan DI SINI supaya tidak tersebar ke halaman/blade.
 *
 * Bentuk hasil:
 *   ['id','url','thumbnail','caption','likes','comments','views','is_video','posted_at']
 */
class KolPostNormalizer
{
    /** Analyzer menampilkan 10 postingan terakhir. */
    public const LIMIT = 10;

    /**
     * @param  array<int, array<string, mixed>>  $recentMedia
     * @return array<int, array<string, mixed>>
     */
    public static function normalize(string $channel, array $recentMedia, ?string $username = null): array
    {
        $posts = array_map(
            fn(array $media) => match ($channel) {
                'Tiktok' => self::fromTiktok($media, $username),
                'Threads' => self::fromThreads($media),
                'Youtube Channels', 'Youtube Shorts' => self::fromYoutube($media),
                default => self::fromInstagram($media, $username),
            },
            array_slice(array_values($recentMedia), 0, self::LIMIT),
        );

        // Postingan tanpa identitas apa pun tidak berguna di UI.
        return array_values(array_filter($posts, fn(array $p) => filled($p['id']) || filled($p['url'])));
    }

    /** Hashtag terbanyak dari caption sekumpulan post — satu-satunya sumber "Top Hashtag" yang kita punya. */
    public static function topHashtags(array $posts, int $limit = 10): array
    {
        $counts = [];

        foreach ($posts as $post) {
            preg_match_all('/#([\p{L}\p{N}_]+)/u', (string) ($post['caption'] ?? ''), $cocok);

            // Satu hashtag dihitung sekali per post, bukan per kemunculan.
            foreach (array_unique(array_map('mb_strtolower', $cocok[1])) as $tag) {
                $counts[$tag] = ($counts[$tag] ?? 0) + 1;
            }
        }

        arsort($counts);

        return array_slice($counts, 0, $limit, preserve_keys: true);
    }

    private static function fromInstagram(array $m, ?string $username): array
    {
        $code = $m['shortcode'] ?? $m['code'] ?? null;

        return [
            'id' => $m['id'] ?? $code,
            'url' => $code ? "https://www.instagram.com/p/{$code}/" : null,
            'thumbnail' => $m['thumbnail_src'] ?? $m['display_url'] ?? null,
            'caption' => $m['caption'] ?? null,
            'likes' => (int) ($m['likes_count'] ?? 0),
            'comments' => (int) ($m['comments_count'] ?? 0),
            'views' => (int) ($m['video_view_count'] ?? 0),
            'is_video' => (bool) ($m['is_video'] ?? false),
            'posted_at' => self::toDate($m['taken_at_timestamp'] ?? $m['taken_at'] ?? null),
        ];
    }

    private static function fromTiktok(array $m, ?string $username): array
    {
        $id = $m['id'] ?? null;

        return [
            'id' => $id,
            // video_url dari service itu play_addr — file .mp4 di CDN yang cepat
            // kedaluwarsa, bukan halaman postingannya. Rakit URL kanonik TikTok.
            'url' => ($username && $id) ? "https://www.tiktok.com/@{$username}/video/{$id}" : ($m['video_url'] ?? null),
            'thumbnail' => $m['thumbnail_src'] ?? $m['display_url'] ?? null,
            // TikTok menaruh caption di 'desc'; tanpa ini Top Hashtag TikTok selalu kosong.
            'caption' => $m['desc'] ?? $m['caption'] ?? $m['description'] ?? null,
            'likes' => (int) ($m['likes_count'] ?? 0),
            'comments' => (int) ($m['comments_count'] ?? 0),
            'views' => (int) ($m['video_view_count'] ?? 0),
            'is_video' => true,
            'posted_at' => self::toDate($m['taken_at'] ?? $m['create_time'] ?? null),
        ];
    }

    private static function fromYoutube(array $m): array
    {
        return [
            'id' => $m['id'] ?? null,
            'url' => $m['video_url'] ?? ($m['id'] ? "https://www.youtube.com/watch?v={$m['id']}" : null),
            'thumbnail' => $m['thumbnail_src'] ?? $m['display_url'] ?? null,
            // YouTube tidak punya caption; judul yang paling mendekati.
            'caption' => $m['title'] ?? null,
            'likes' => (int) ($m['likes_count'] ?? 0),
            'comments' => (int) ($m['comments_count'] ?? 0),
            'views' => (int) ($m['video_view_count'] ?? 0),
            'is_video' => true,
            'posted_at' => self::toDate($m['published_time'] ?? null),
        ];
    }

    private static function fromThreads(array $m): array
    {
        return [
            'id' => $m['id'] ?? null,
            'url' => $m['url'] ?? null,
            'thumbnail' => null, // Threads berbasis teks; tidak ada thumbnail seragam.
            'caption' => $m['caption'] ?? null,
            'likes' => (int) ($m['likes'] ?? 0),
            'comments' => (int) ($m['comments'] ?? 0),
            'views' => 0,
            'is_video' => false,
            'posted_at' => self::toDate($m['taken_at'] ?? null),
        ];
    }

    /** Unix timestamp, string tanggal, atau null → 'Y-m-d H:i:s'. */
    private static function toDate(mixed $value): ?string
    {
        if (blank($value)) {
            return null;
        }

        if (is_numeric($value)) {
            return date('Y-m-d H:i:s', (int) $value);
        }

        $waktu = strtotime((string) $value);

        return $waktu ? date('Y-m-d H:i:s', $waktu) : null;
    }
}
