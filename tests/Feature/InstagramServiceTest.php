<?php

use App\Service\InstagramService;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;

/**
 * Yang dijaga: panggilan post Instagram tetap ramping.
 *
 * Tanpa trim=true respons /v2/instagram/user/posts untuk akun besar ~1 MB dan
 * kena timeout — engagement lalu diam-diam jatuh ke edges yang tertanam di
 * profil. Parameter `count` yang dulu dikirim tidak ada di dokumentasi, jadi
 * diabaikan server dan seluruh halaman tetap terunduh.
 */
function fakePost(int $likes, int $comments, int $plays): array
{
    return [
        'id' => (string) random_int(1, 999999),
        'code' => 'ABC' . random_int(100, 999),
        'taken_at' => now()->subDays(7)->timestamp,
        'media_type' => 2,
        'is_video' => true,
        'like_count' => $likes,
        'comment_count' => $comments,
        'play_count' => $plays,
    ];
}

function fakeInstagramApi(array $posts): void
{
    Http::fake([
        '*/v1/instagram/profile*' => Http::response([
            'success' => true,
            'data' => ['user' => [
                'username' => 'radenrauf',
                'full_name' => 'Raden Rauf',
                'edge_followed_by' => ['count' => 2_290_184],
                'edge_follow' => ['count' => 500],
                'edge_owner_to_timeline_media' => ['count' => 2178, 'edges' => []],
            ]],
        ]),
        '*/v2/instagram/user/posts*' => Http::response([
            'success' => true,
            'items' => $posts,
        ]),
    ]);
}

it('meminta respons post yang sudah di-trim', function () {
    fakeInstagramApi([fakePost(10_000, 500, 200_000)]);

    (new InstagramService())->getProfile('https://www.instagram.com/radenrauf/');

    Http::assertSent(fn(Request $r) => str_contains($r->url(), '/v2/instagram/user/posts')
        && str_contains($r->url(), 'trim=true'));

    // `count` bukan parameter yang didukung — mengirimnya cuma bikin URL kotor
    // dan menyesatkan pembaca berikutnya, karena server tetap kirim satu halaman penuh.
    Http::assertNotSent(fn(Request $r) => str_contains($r->url(), '/v2/instagram/user/posts')
        && str_contains($r->url(), 'count='));
});

it('membatasi jumlah post di sisi PHP, bukan mengandalkan server', function () {
    // Server mengabaikan `count`, jadi kirim 20 post; hanya 9 yang boleh dipakai.
    fakeInstagramApi(array_map(fn() => fakePost(1_000, 100, 50_000), range(1, 20)));

    $posts = (new InstagramService())->getUserPosts('radenrauf');

    expect($posts)->toHaveCount(9);
});

it('tetap menghitung engagement dari field yang selamat dari trim', function () {
    fakeInstagramApi([
        fakePost(10_000, 500, 200_000),
        fakePost(20_000, 1_000, 400_000),
    ]);

    $profil = (new InstagramService())->getProfile('radenrauf');

    // like_count + comment_count bertahan setelah trim; kalau suatu saat tidak,
    // angkanya jatuh ke nol dan test ini yang lebih dulu merah.
    expect((int) $profil['total_engagements'])->toBe(31_500)
        ->and((int) $profil['average_likes'])->toBe(15_000)
        ->and((int) $profil['average_comments'])->toBe(750);
});
