<?php

use App\Models\BvCampaignKol;
use App\Models\BvCampign;
use App\Models\CampaignKolRevision;

/**
 * Menutup gap sheet "Tracker": revisi konten dinamis (storyline/video/caption tak terbatas
 * ronde + Final), event_attendance, dan status KOL "canceled".
 */

function makeCampaignWithKol(): array
{
    $campaign = BvCampign::create([
        'campaign_name' => 'Masters of the Universe',
        'campaign_type' => BvCampign::TYPE_INTERNAL,
        'status' => 'ongoing',
    ]);

    $kol = BvCampaignKol::create([
        'campaign_id' => $campaign->id,
        'creator_name' => 'adifi_',
        'platform' => 'tiktok',
        'content_type' => 'video',
        'status' => 'pending',
    ]);

    return [$campaign, $kol];
}

it('menampung revisi bertingkat lintas tahap dengan Final Revisi', function () {
    [$campaign, $kol] = makeCampaignWithKol();

    // Storyline: draft + revisi
    $campaign->revisions()->create(['bv_campaign_kol_id' => $kol->id, 'kol_name' => 'adifi_', 'stage' => 'storyline', 'round' => 1]);
    $campaign->revisions()->create(['bv_campaign_kol_id' => $kol->id, 'kol_name' => 'adifi_', 'stage' => 'storyline', 'round' => 2, 'client_feedback' => 'masters of the universe ya, bukan master']);

    // Video: draft → revisi 1 → revisi 2 → final (4 ronde, melebihi batas 2 ronde lama)
    foreach ([1, 2, 3] as $r) {
        $campaign->revisions()->create(['bv_campaign_kol_id' => $kol->id, 'kol_name' => 'adifi_', 'stage' => 'video', 'round' => $r]);
    }
    $campaign->revisions()->create(['bv_campaign_kol_id' => $kol->id, 'kol_name' => 'adifi_', 'stage' => 'video', 'round' => 4, 'is_final' => true, 'status' => 'approved']);

    // Caption
    $campaign->revisions()->create(['bv_campaign_kol_id' => $kol->id, 'kol_name' => 'adifi_', 'stage' => 'caption', 'round' => 1]);

    expect($campaign->revisions()->count())->toBe(7);
    expect($kol->revisions()->where('stage', 'video')->count())->toBe(4); // lebih dari 2 ronde lama
    expect($campaign->revisions()->where('is_final', true)->first()->stage)->toBe('video');
});

it('event_attendance tersimpan sebagai boolean', function () {
    [, $kol] = makeCampaignWithKol();
    $kol->update(['event_attendance' => true]);
    expect($kol->fresh()->event_attendance)->toBeTrue();
});

it('status KOL mendukung canceled (KOL Cancel)', function () {
    expect(BvCampaignKol::STATUSES)->toHaveKey('canceled');

    [, $kol] = makeCampaignWithKol();
    $kol->update(['status' => 'canceled']);
    expect($kol->fresh()->status)->toBe('canceled');
});

it('revisi terhapus saat campaign dihapus (cascade)', function () {
    [$campaign, $kol] = makeCampaignWithKol();
    $campaign->revisions()->create(['bv_campaign_kol_id' => $kol->id, 'kol_name' => 'adifi_', 'stage' => 'video', 'round' => 1]);

    $campaign->delete();
    expect(CampaignKolRevision::count())->toBe(0);
});
