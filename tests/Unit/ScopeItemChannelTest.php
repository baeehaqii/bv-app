<?php

use App\Models\InternalBudget;
use App\Models\MediaPlan;

/**
 * Pemetaan label SOW (sheet) → platform/content_type yang dipakai BvCampaignKol.
 * Salah map = KOL masuk platform/jenis konten yang keliru di Campaign Ongoing.
 */
dataset('scopes', [
    // [label SOW, platform, content_type]
    ['TT Video', 'tiktok', 'video'],
    ['1x TikTok Video', 'tiktok', 'video'],
    ['IG Reels', 'instagram', 'reels'],
    ['IG Story', 'instagram', 'story'],
    ['IG Feed', 'instagram', 'feed'],
    ['YouTube Short', 'youtube', 'short'],
    ['YouTube Video', 'youtube', 'video'],
    ['Threads Post', 'threads', 'post'],
]);

it('InternalBudget::parseScopeItemToChannel memetakan SOW dengan benar', function (string $scope, string $platform, string $type) {
    $result = InternalBudget::parseScopeItemToChannel($scope);
    expect($result['platform'])->toBe($platform);
    expect($result['content_type'])->toBe($type);
})->with('scopes');

it('MediaPlan::detectPlatformFromScope konsisten untuk TikTok Video', function () {
    [$platform, $type] = MediaPlan::detectPlatformFromScope('1x TikTok Video');
    expect($platform)->toBe('tiktok');
    expect($type)->toBe('video');
});

it('scope tak dikenal default ke instagram/feed (aman, tidak melempar error)', function () {
    $result = InternalBudget::parseScopeItemToChannel('Sesuatu Yang Aneh');
    expect($result)->toBe(['platform' => 'instagram', 'content_type' => 'feed']);
});
