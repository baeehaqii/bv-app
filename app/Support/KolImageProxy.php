<?php

namespace App\Support;

use Illuminate\Support\Facades\URL;

/**
 * Proxy gambar CDN media sosial.
 *
 * CDN Instagram/TikTok memblokir hotlink: <img src> langsung dari halaman kita
 * dijawab 403, jadi thumbnail tampil rusak. Gambarnya harus diambil server-side
 * dengan Referer yang benar, lalu disajikan dari domain sendiri.
 *
 * Dua pengaman, karena endpoint "ambilkan URL ini" adalah open proxy kalau lengah:
 *  1. URL-nya DITANDATANGANI Laravel — hanya link yang kita buat sendiri yang dilayani.
 *  2. Host-nya harus ada di ALLOWED_HOSTS — sekalipun tanda tangan bocor, ia tidak
 *     bisa dipakai menembak host internal (SSRF).
 */
class KolImageProxy
{
    /** Cache di browser: URL CDN-nya sendiri sudah ber-hash, jadi aman disimpan lama. */
    public const BROWSER_TTL = 60 * 60 * 24 * 30;

    /** Batas ukuran unduhan — thumbnail wajar jauh di bawah ini. */
    public const MAX_BYTES = 8 * 1024 * 1024;

    /** Sufiks host CDN yang boleh diambil. */
    public const ALLOWED_HOSTS = [
        // Instagram / Threads
        'cdninstagram.com', 'fbcdn.net',
        // TikTok
        'tiktokcdn.com', 'tiktokcdn-us.com', 'tiktokcdn-eu.com', 'ibyteimg.com', 'byteoversea.com',
        // YouTube
        'ytimg.com', 'ggpht.com', 'googleusercontent.com',
    ];

    /** URL proxy bertanda tangan; null bila sumbernya kosong atau bukan CDN yang diizinkan. */
    public static function url(?string $source): ?string
    {
        if (! self::isAllowed($source)) {
            return null;
        }

        return URL::signedRoute('kol-image', ['src' => $source]);
    }

    public static function isAllowed(?string $source): bool
    {
        $host = parse_url((string) $source, PHP_URL_HOST);
        $scheme = parse_url((string) $source, PHP_URL_SCHEME);

        if (! $host || $scheme !== 'https') {
            return false;
        }

        foreach (self::ALLOWED_HOSTS as $izin) {
            // str_ends_with dengan titik di depan: "evil-cdninstagram.com" tidak lolos.
            if ($host === $izin || str_ends_with($host, '.' . $izin)) {
                return true;
            }
        }

        return false;
    }

    /**
     * CDN memeriksa Referer, bukan sekadar User-Agent. Kirim halaman asal yang
     * sesuai host-nya — inilah yang membedakan 200 dari 403.
     */
    public static function refererFor(string $source): string
    {
        $host = (string) parse_url($source, PHP_URL_HOST);

        return match (true) {
            str_contains($host, 'tiktokcdn'), str_contains($host, 'ibyteimg'),
            str_contains($host, 'byteoversea') => 'https://www.tiktok.com/',
            str_contains($host, 'ytimg'), str_contains($host, 'ggpht'),
            str_contains($host, 'googleusercontent') => 'https://www.youtube.com/',
            default => 'https://www.instagram.com/',
        };
    }

    /** Nama berkas cache — sha1 URL sumber, jadi otomatis berganti saat URL berganti. */
    public static function cachePath(string $source): string
    {
        return 'kol-thumbs/' . sha1($source) . '.img';
    }
}
