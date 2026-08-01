<?php

use App\Service\KolProfileImporter;
use App\Service\TiktokService;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;

/**
 * Yang dijaga: TikTok benar-benar menghitung engagement dari data per-video.
 *
 * Sebelumnya tidak pernah bisa — path-nya salah ("profile-videos", 404 terus sejak
 * April 2026) dan respons diperlakukan sebagai array video telanjang padahal objek
 * ber-`aweme_list`. Akibatnya SEMUA ER & engagement TikTok cuma estimasi dari total
 * likes profil. Dua kesalahan itu tak terlihat dari luar: tidak ada error, cuma
 * angka yang diam-diam salah.
 */
function fakeVideo(int $likes, int $comments, int $shares, int $saves, int $views): array
{
    return [
        'aweme_id' => (string) random_int(1, 999999),
        'create_time' => now()->subDays(7)->timestamp,
        'statistics' => [
            'digg_count' => $likes,
            'comment_count' => $comments,
            'share_count' => $shares,
            'collect_count' => $saves,
            'play_count' => $views,
        ],
    ];
}

function fakeTiktokApi(array $awemeList, array $stats = []): void
{
    Http::fake([
        '*/v1/tiktok/profile*' => Http::response([
            'success' => true,
            'user' => ['id' => '123', 'uniqueId' => 'stoolpresidente', 'nickname' => 'Dave', 'verified' => true],
            'stats' => array_merge([
                'followerCount' => 1_000_000,
                'followingCount' => 100,
                'heart' => 50_000_000,
                'heartCount' => 50_000_000,
                'videoCount' => 500,
            ], $stats),
        ]),
        '*/v3/tiktok/profile/videos*' => Http::response([
            'success' => true,
            'aweme_list' => $awemeList,
            'has_more' => 1,
            'max_cursor' => '123',
        ]),
    ]);
}

it('memanggil endpoint video dengan path yang benar', function () {
    fakeTiktokApi([fakeVideo(1000, 100, 50, 25, 20_000)]);

    (new TiktokService())->getProfile('https://www.tiktok.com/@stoolpresidente');

    // Path lama "profile-videos" balas 404 → estimasi. Kalau ada yang mengembalikannya,
    // test ini merah sebelum angkanya sempat masuk ke database KOL.
    Http::assertSent(fn(Request $r) => str_contains($r->url(), '/v3/tiktok/profile/videos')
        && str_contains($r->url(), 'handle=stoolpresidente'));

    Http::assertNotSent(fn(Request $r) => str_contains($r->url(), 'profile-videos'));
});

it('menghitung engagement dari aweme_list, bukan dari estimasi', function () {
    // 2 video: (1000+100+50+25) + (2000+200+100+50) = 1175 + 2350 = 3525
    fakeTiktokApi([
        fakeVideo(1000, 100, 50, 25, 20_000),
        fakeVideo(2000, 200, 100, 50, 40_000),
    ]);

    $profil = (new TiktokService())->getProfile('stoolpresidente');

    expect((int) $profil['total_engagements'])->toBe(3525)
        ->and((int) $profil['average_likes'])->toBe(1500)
        ->and((int) $profil['average_comments'])->toBe(150)
        // Rata-rata views = (20.000 + 40.000) / 2, bukan followers × 0,075.
        ->and((int) $profil['average_impressions'])->toBe(30_000)
        ->and((float) $profil['engagement_rate'])->toBeGreaterThan(0.0);
});

it('jatuh ke estimasi hanya ketika endpoint video benar-benar gagal', function () {
    Http::fake([
        '*/v1/tiktok/profile*' => Http::response([
            'success' => true,
            'user' => ['uniqueId' => 'stoolpresidente'],
            'stats' => ['followerCount' => 1_000_000, 'heart' => 50_000_000, 'videoCount' => 500],
        ]),
        '*/v3/tiktok/profile/videos*' => Http::response(['message' => 'Not found'], 404),
    ]);

    $profil = (new TiktokService())->getProfile('stoolpresidente');

    // 50jt likes / 500 video × 9 × 1,18 — angka estimasi, bukan hasil ukur.
    expect((int) $profil['total_engagements'])->toBe(1_062_000)
        ->and((int) $profil['average_impressions'])->toBe(75_000);   // 1jt × 0,075
});

it('memberi batas waktu default pada setiap panggilan HTTP keluar', function () {
    // Tanpa timeout, Guzzle menunggu selamanya sampai max_execution_time PHP habis —
    // FatalError yang TIDAK bisa ditangkap, jadi layar 500 alih-alih notifikasi
    // "gagal fetch".
    $opsi = Http::getFacadeRoot()->createPendingRequest()->getOptions();

    expect($opsi['timeout'] ?? null)->not->toBeNull()
        ->and($opsi['connect_timeout'] ?? null)->not->toBeNull()
        // Profil + video berurutan harus selesai sebelum jatah PHP per baris habis,
        // supaya yang menghentikan tetap timeout HTTP (rapi), bukan FatalError.
        ->and($opsi['timeout'] * 2)->toBeLessThan(KolProfileImporter::BATAS_WAKTU_PER_BARIS)
        // Respons profil Instagram ~640 KB; di bawah ~20 detik pasti kepotong.
        ->and($opsi['timeout'])->toBeGreaterThanOrEqual(25);
});

it('tidak salah membaca respons objek sebagai daftar video', function () {
    // Bug lama: $response->json() dikembalikan apa adanya, jadi kunci objek
    // (success, has_more, max_cursor) ikut di-iterasi sebagai "video" berisi nol.
    fakeTiktokApi([]);

    $profil = (new TiktokService())->getProfile('stoolpresidente');

    // aweme_list kosong → estimasi, BUKAN nol palsu dari mengiterasi kunci objek.
    expect((int) $profil['average_impressions'])->toBe(75_000);
});
