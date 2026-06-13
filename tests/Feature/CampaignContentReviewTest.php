<?php

use App\Models\BvCampign;
use App\Models\CampaignStoryline;

/**
 * Part C — Campaign On Going Internal: Link Approval Konten.
 */

function makeInternalCampaign(): BvCampign
{
    $campaign = BvCampign::create([
        'campaign_name' => 'Page By Page Launching',
        'campaign_type' => BvCampign::TYPE_INTERNAL,
        'status' => 'ongoing',
    ]);

    CampaignStoryline::create([
        'bv_campaign_id' => $campaign->id,
        'kol_name' => 'cretivox',
        'platform' => 'tiktok',
        'sow' => 'TikTok Video',
        'content_angle' => 'Unboxing seru',
        'status' => 'waiting_approval',
    ]);
    CampaignStoryline::create([
        'bv_campaign_id' => $campaign->id,
        'kol_name' => 'ussfeeds',
        'platform' => 'instagram',
        'sow' => 'IG Reels',
        'status' => 'waiting_approval',
    ]);

    return $campaign->fresh('storylines');
}

it('campaign internal ditandai dengan campaign_type internal', function () {
    $c = makeInternalCampaign();
    expect($c->isInternal())->toBeTrue();
    expect(BvCampign::TYPE_INTERNAL)->toBe('internal');
});

it('generate content review token & url', function () {
    $c = makeInternalCampaign();
    expect($c->content_review_url)->toBeNull();

    $token = $c->generateContentReviewToken();

    expect($token)->toHaveLength(48);
    $c->refresh();
    expect($c->content_review_is_public)->toBeTrue();
    expect($c->content_review_url)->toContain($token);
});

it('halaman approval konten bisa diakses & menampilkan draft', function () {
    $c = makeInternalCampaign();
    $token = $c->generateContentReviewToken();

    $this->get(route('campaign-internal.content-review', ['token' => $token]))
        ->assertOk()
        ->assertSee('Page By Page Launching')
        ->assertSee('cretivox')
        ->assertSee('Unboxing seru')
        ->assertSee('Kirim Approval');
});

it('menolak token tidak publik / tidak valid', function () {
    $c = makeInternalCampaign();
    $c->forceFill(['content_review_token' => str_repeat('b', 48), 'content_review_is_public' => false])->saveQuietly();

    $this->get(route('campaign-internal.content-review', ['token' => str_repeat('b', 48)]))->assertNotFound();
    $this->get(route('campaign-internal.content-review', ['token' => 'nope']))->assertNotFound();
});

it('approve draft → storyline approved & KOL masuk Performance', function () {
    $c = makeInternalCampaign();
    $token = $c->generateContentReviewToken();
    [$s1, $s2] = $c->storylines;

    $this->post(route('campaign-internal.content-review.submit', ['token' => $token]), [
        'choices' => [
            $s1->id => 'approved',
            $s2->id => 'revision',
        ],
        'feedback' => [
            $s2->id => 'Tolong perbaiki hook',
        ],
    ])->assertRedirect(route('campaign-internal.content-review', ['token' => $token]));

    $s1->refresh();
    $s2->refresh();
    $c->refresh();

    expect($s1->status)->toBe('approved');
    expect($s1->client_choice)->toBe('approved');
    expect($s2->status)->toBe('revision');
    expect($s2->client_feedback)->toBe('Tolong perbaiki hook');
    expect($c->content_review_submitted_at)->not->toBeNull();

    // KOL Performance: hanya cretivox (approved) yang dibuat sebagai approved
    $perfKols = $c->kols()->where('brief_status', 'approved')->get();
    expect($perfKols)->toHaveCount(1);
    expect($perfKols->first()->creator_name)->toBe('cretivox');
    expect($perfKols->first()->content_type)->toBe('video');
});

it('validasi menolak choice di luar approved/revision', function () {
    $c = makeInternalCampaign();
    $token = $c->generateContentReviewToken();
    [$s1] = $c->storylines;

    $this->post(route('campaign-internal.content-review.submit', ['token' => $token]), [
        'choices' => [$s1->id => 'maybe'],
    ])->assertSessionHasErrors();
});

it('campaign internal & external sama-sama muncul di query Campaign Ongoing External', function () {
    // Aturan baru: modul External = sisi client. Campaign internal (pipeline Media Plan)
    // ikut tampil agar client bisa preview/revisi lewat modul External.
    $internal = makeInternalCampaign();
    $external = BvCampign::create([
        'campaign_name' => 'External Camp',
        'campaign_type' => 'regular',
        'status' => 'ongoing',
    ]);

    $externalQuery = BvCampign::query()
        ->where('status', 'ongoing')
        ->pluck('id');

    expect($externalQuery)->toContain($external->id);
    expect($externalQuery)->toContain($internal->id);
});
