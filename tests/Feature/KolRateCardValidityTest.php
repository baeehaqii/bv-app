<?php

use App\Models\KolRateCard;

/**
 * Masa berlaku rate card KOL — dasar reminder "wajib perbarui SOW" di Media Plan.
 * valid_until eksplisit menang; bila kosong fallback ke valid_from + DEFAULT_VALIDITY_DAYS.
 */

it('memakai default 90 hari saat valid_until kosong', function () {
    $card = new KolRateCard(['valid_from' => now()->subDays(10)]);

    expect($card->effective_valid_until?->toDateString())
        ->toBe(now()->subDays(10)->addDays(KolRateCard::DEFAULT_VALIDITY_DAYS)->toDateString());
});

it('mengutamakan valid_until eksplisit di atas default', function () {
    $explicit = now()->addDays(5)->startOfDay();
    $card = new KolRateCard([
        'valid_from' => now()->subDays(10),
        'valid_until' => $explicit,
    ]);

    expect($card->effective_valid_until?->toDateString())->toBe($explicit->toDateString());
});

it('menandai expired saat melewati default 90 hari', function () {
    $card = new KolRateCard(['valid_from' => now()->subDays(100)]);

    expect($card->isExpired())->toBeTrue()
        ->and($card->daysUntilExpiry())->toBeLessThan(0);
});

it('tidak expired saat masih dalam masa berlaku default', function () {
    $card = new KolRateCard(['valid_from' => now()->subDays(10)]);

    expect($card->isExpired())->toBeFalse()
        ->and($card->daysUntilExpiry())->toBeGreaterThan(0);
});

it('menghormati valid_until untuk status expired', function () {
    $expired = new KolRateCard([
        'valid_from' => now()->subDays(10),
        'valid_until' => now()->subDay(),
    ]);
    $valid = new KolRateCard([
        'valid_from' => now()->subDays(10),
        'valid_until' => now()->addDay(),
    ]);

    expect($expired->isExpired())->toBeTrue()
        ->and($valid->isExpired())->toBeFalse();
});

it('mengembalikan null tanpa tanggal acuan', function () {
    $card = new KolRateCard();

    expect($card->effective_valid_until)->toBeNull()
        ->and($card->daysUntilExpiry())->toBeNull()
        ->and($card->isExpired())->toBeFalse();
});
