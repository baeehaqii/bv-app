<?php

use App\Service\Concerns\CalculatesEngagementRate;

/**
 * Subjek tes: trait perhitungan ER. Dibungkus kelas anonim agar method
 * protected dapat dipanggil langsung tanpa memanggil API eksternal.
 */
function erCalculator(): object
{
    return new class {
        use CalculatesEngagementRate;

        public function avg(array $posts, int $followers): float
        {
            return $this->averageEngagementRate($posts, $followers);
        }

        public function perPost(float $engagement, float $views, int $followers): ?float
        {
            return $this->engagementRateForPost($engagement, $views, $followers);
        }
    };
}

it('memakai views sebagai basis ER untuk konten video', function () {
    // 50.000 engagement / 1.000.000 views = 5%
    $rate = erCalculator()->perPost(50_000, 1_000_000, 200_000);

    expect($rate)->toBe(5.0);
});

it('memakai followers sebagai basis ER untuk foto (views = 0)', function () {
    // 3.000 engagement / 100.000 followers = 3%
    $rate = erCalculator()->perPost(3_000, 0, 100_000);

    expect($rate)->toBe(3.0);
});

it('mencegah anomali ER di atas 100% (clamp)', function () {
    // engagement jauh melebihi basis → harus di-clamp ke 100, bukan 243%
    $rate = erCalculator()->perPost(2_430_000, 0, 1_000_000);

    expect($rate)->toBe(100.0);
});

it('mengembalikan 0 saat tidak ada post', function () {
    expect(erCalculator()->avg([], 100_000))->toBe(0.0);
});

it('mengembalikan 0 saat followers nol', function () {
    expect(erCalculator()->avg([['engagement' => 100, 'views' => 0]], 0))->toBe(0.0);
});

it('merata-ratakan ER per post dengan basis berbeda (video & foto)', function () {
    $posts = [
        ['engagement' => 100_000, 'views' => 1_000_000], // video: 10%
        ['engagement' => 4_000, 'views' => 0],            // foto: 4% dari 100k followers
    ];

    // rata-rata (10 + 4) / 2 = 7
    expect(erCalculator()->avg($posts, 100_000))->toBe(7.0);
});

it('menghasilkan ER wajar untuk reel viral (anti-anomali)', function () {
    // Reel viral: engagement 300k, views 5jt, followers 1,5jt
    // ER lama (engagement/followers) = 20% (anomali); ER baru (engagement/views) = 6%
    $posts = [['engagement' => 300_000, 'views' => 5_000_000]];

    expect(erCalculator()->avg($posts, 1_500_000))->toBe(6.0);
});
