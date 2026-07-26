<?php

use App\Models\BvCampign;
use App\Models\CampaignStoryline;
use App\Models\MediaPlan;
use App\Support\MotuScenarioData as Motu;
use Database\Seeders\SonyPicturesScenarioSeeder;

/**
 * Storyline: KOL & SOW diambil dari KOL yang di-approve client, konten (gambar + link)
 * dikirim ke client per versi, dan perbaikan dibatasi 3x.
 */
beforeEach(function () {
    $this->seed(SonyPicturesScenarioSeeder::class);
    $this->mediaPlan = MediaPlan::where('campaign_name', Motu::CAMPAIGN_NAME)->firstOrFail();
    $this->campaign = BvCampign::where('bv_sales_id', $this->mediaPlan->bv_sales_id)->firstOrFail();
});

it('menyediakan dropdown KOL & SOW dari budget item yang di-approve client', function () {
    $approvedItems = $this->mediaPlan->internalBudget->items()->where('status', 'approved')->with('mediaPlanKol')->get();
    expect($approvedItems)->not->toBeEmpty();

    $options = $this->campaign->approvedKolOptions();
    $firstKol = $approvedItems->first()->mediaPlanKol->name;

    expect($options)->toHaveKey($firstKol)
        ->and($this->campaign->approvedSowOptions($firstKol))
        ->toContain($approvedItems->first()->scope_item);

    // KOL yang tidak di-approve tidak ikut muncul.
    expect($options)->not->toHaveKey('KOL Tidak Terdaftar');
});

it('menyimpan konten per versi saat dikirim ke client', function () {
    $storyline = $this->campaign->storylines()->create([
        'kol_name' => 'Raden Rauf',
        'platform' => 'instagram',
        'sow' => 'IG Reels',
        'status' => 'draft',
        'images' => ['storyline-contents/a.jpg', 'storyline-contents/b.jpg'],
        'content_link' => 'https://drive.google.com/file/v1',
    ]);

    $content = $storyline->submitToClient();

    expect($content->revision_number)->toBe(0)
        ->and($content->images)->toBe(['storyline-contents/a.jpg', 'storyline-contents/b.jpg'])
        ->and($content->content_link)->toBe('https://drive.google.com/file/v1')
        ->and($content->submitted_at)->not->toBeNull()
        ->and($storyline->refresh()->status)->toBe('waiting_approval');

    // Dikirim ulang sebelum client memutuskan → versi yang sama diperbarui, bukan versi baru.
    $storyline->update(['content_link' => 'https://drive.google.com/file/v1b']);
    $storyline->submitToClient();

    expect($storyline->contents()->count())->toBe(1)
        ->and($storyline->latestContent()->content_link)->toBe('https://drive.google.com/file/v1b');
});

it('membatasi perbaikan konten maksimal 3x', function () {
    $storyline = $this->campaign->storylines()->create([
        'kol_name' => 'Raden Rauf',
        'sow' => 'IG Reels',
        'status' => 'draft',
        'content_link' => 'https://link/v0',
    ]);

    $storyline->submitToClient();                        // versi awal
    $storyline->recordClientDecision('revision', 'Kurang cerah');

    foreach ([1, 2, 3] as $n) {
        expect($storyline->refresh()->canSubmitToClient())->toBeTrue();
        $storyline->update(['content_link' => "https://link/v{$n}"]);
        $content = $storyline->submitToClient();
        expect($content->revision_number)->toBe($n);
        $storyline->recordClientDecision('revision', "Revisi {$n}");
    }

    $storyline->refresh();
    expect($storyline->revisionCount())->toBe(CampaignStoryline::MAX_REVISIONS)
        ->and($storyline->remainingRevisions())->toBe(0)
        ->and($storyline->canSubmitToClient())->toBeFalse();

    expect(fn() => $storyline->submitToClient())->toThrow(RuntimeException::class);

    // Riwayat lengkap tersimpan: versi awal + 3 revisi, tiap versi punya feedback client.
    expect($storyline->contents()->count())->toBe(4)
        ->and($storyline->contents()->pluck('client_feedback')->filter()->count())->toBe(4);
});

it('client approve lewat link menutup versi terakhir dan menyetujui storyline', function () {
    $storyline = $this->campaign->storylines()->create([
        'kol_name' => 'Raden Rauf',
        'sow' => 'IG Reels',
        'status' => 'draft',
        'images' => ['storyline-contents/a.jpg'],
    ]);
    $storyline->submitToClient();

    $this->campaign->generateContentReviewToken();

    $this->post(route('campaign-internal.content-review.submit', ['token' => $this->campaign->content_review_token]), [
        'choices' => [$storyline->id => 'approved'],
        'feedback' => [$storyline->id => 'Sudah oke'],
    ])->assertRedirect();

    $storyline->refresh();
    expect($storyline->status)->toBe('approved')
        ->and($storyline->latestContent()->client_choice)->toBe('approved')
        ->and($storyline->latestContent()->client_feedback)->toBe('Sudah oke')
        ->and($storyline->latestContent()->reviewed_at)->not->toBeNull();
});

it('halaman approval client menampilkan gambar & link konten', function () {
    $storyline = $this->campaign->storylines()->create([
        'kol_name' => 'Raden Rauf',
        'sow' => 'IG Reels',
        'status' => 'draft',
        'images' => ['storyline-contents/preview.jpg'],
        'content_link' => 'https://drive.google.com/preview',
    ]);
    $storyline->submitToClient();
    $this->campaign->generateContentReviewToken();

    $this->get(route('campaign-internal.content-review', ['token' => $this->campaign->content_review_token]))
        ->assertOk()
        ->assertSee('storyline-contents/preview.jpg')
        ->assertSee('https://drive.google.com/preview');
});
