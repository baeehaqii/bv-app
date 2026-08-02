<?php

namespace App\Service;

use Exception;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Mengambil teks komentar sebuah postingan untuk analisis sentimen.
 *
 * Terpisah dari PostPerformanceService karena berbeda sifat: performa dipanggil
 * rutin dan murah, komentar dipanggil on-demand dan berbayar per postingan.
 *
 * Yang dikembalikan hanya TEKS komentar — identitas pengomentar tidak disimpan;
 * yang dibutuhkan campaign summary cuma sentimen dan buzz word.
 */
class PostCommentsFetcher
{
    private const ENDPOINTS = [
        'instagram' => ['url' => 'https://api.scrapecreators.com/v2/instagram/post/comments', 'param' => 'url'],
        'tiktok' => ['url' => 'https://api.scrapecreators.com/v1/tiktok/video/comments', 'param' => 'url'],
        'youtube' => ['url' => 'https://api.scrapecreators.com/v1/youtube/video/comments', 'param' => 'url'],
    ];

    private string $apiKey;

    public function __construct()
    {
        $apiKey = config('services.scrapecreators.api_key') ?? env('SCRAPECREATORS_API_KEY');

        if (empty($apiKey)) {
            throw new Exception('ScrapeCreators API key is not configured');
        }

        $this->apiKey = $apiKey;
    }

    /** Platform yang punya endpoint komentar. Threads belum ada. */
    public static function supports(?string $platform): bool
    {
        return isset(self::ENDPOINTS[(string) $platform]);
    }

    /**
     * @return array<int, string> teks komentar, sudah dibuang yang kosong
     */
    public function fetch(string $postUrl, string $platform): array
    {
        $endpoint = self::ENDPOINTS[$platform] ?? null;

        if (! $endpoint) {
            return [];
        }

        $response = Http::withHeaders(['x-api-key' => $this->apiKey])
            ->get($endpoint['url'], [$endpoint['param'] => $postUrl]);

        if (! $response->successful()) {
            Log::warning('Gagal mengambil komentar', [
                'url' => $postUrl,
                'platform' => $platform,
                'status' => $response->status(),
            ]);

            return [];
        }

        $teks = array_map(
            self::textExtractor($platform),
            self::rawComments($response->json() ?? []),
        );

        return array_values(array_filter(
            array_map('trim', $teks),
            fn(string $t) => $t !== '',
        ));
    }

    /**
     * Daftar komentar bisa berada di beberapa kunci tergantung platform & versi
     * endpoint — dicari yang pertama ada, bukan diasumsikan satu bentuk.
     */
    private static function rawComments(array $payload): array
    {
        foreach (['comments', 'data.comments', 'items', 'data'] as $kunci) {
            $nilai = data_get($payload, $kunci);

            if (is_array($nilai) && $nilai !== [] && is_array(reset($nilai))) {
                return array_slice($nilai, 0, (int) config('sentiment.comments_per_post', 100));
            }
        }

        return [];
    }

    private static function textExtractor(string $platform): callable
    {
        return fn(array $comment) => (string) match ($platform) {
            'tiktok' => $comment['text'] ?? $comment['comment'] ?? '',
            'youtube' => $comment['text'] ?? $comment['contentText'] ?? $comment['snippet']['textDisplay'] ?? '',
            default => $comment['text'] ?? $comment['comment_text'] ?? $comment['content'] ?? '',
        };
    }
}
