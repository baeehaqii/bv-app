<?php

use App\Models\InternalBudget;
use App\Models\InternalBudgetItem;
use App\Models\MediaPlan;

/**
 * Part B — Link Review Client untuk Media Plan External.
 * Memverifikasi: status baru, token publik, halaman review, submit pilihan client.
 */

function makeReviewBudget(string $status = 'review_client'): InternalBudget
{
    $mediaPlan = MediaPlan::create([
        'brand' => 'Test Brand',
        'quotation_number' => 'Q-TEST-' . uniqid(),
        'campaign_name' => 'Page By Page Launching',
    ]);

    $budget = InternalBudget::create([
        'media_plan_id' => $mediaPlan->id,
        'status' => $status,
    ]);

    InternalBudgetItem::create([
        'internal_budget_id' => $budget->id,
        'qty' => 1,
        'scope_item' => 'IG Reels',
        'rate_base' => 1_000_000,
        'sort_order' => 0,
    ]);
    InternalBudgetItem::create([
        'internal_budget_id' => $budget->id,
        'qty' => 1,
        'scope_item' => 'TikTok Video',
        'rate_base' => 2_000_000,
        'sort_order' => 1,
    ]);

    return $budget->fresh('items');
}

it('memetakan STATUS_OPTIONS ke status baru', function () {
    expect(InternalBudget::STATUS_OPTIONS)->toHaveKeys([
        'draft', 'review_client', 'approve_client', 'approve_am', 'rejected',
    ]);
    expect(InternalBudget::STATUS_FINAL)->toEqual(['approve_client', 'approve_am']);
});

it('generate review token & url publik', function () {
    $budget = makeReviewBudget();

    expect($budget->review_url)->toBeNull();

    $token = $budget->generateReviewToken();

    expect($token)->toHaveLength(48);
    $budget->refresh();
    expect($budget->review_is_public)->toBeTrue();
    expect($budget->review_url)->toContain($token);
});

it('halaman review publik bisa diakses dengan token aktif', function () {
    $budget = makeReviewBudget();
    $token = $budget->generateReviewToken();

    $this->get(route('media-plan-external.review', ['token' => $token]))
        ->assertOk()
        ->assertSee('Page By Page Launching')
        ->assertSee('IG Reels')
        ->assertSee('TikTok Video')
        ->assertSee('Submit Review');
});

it('menolak token yang tidak publik / tidak valid', function () {
    $budget = makeReviewBudget();
    // token ada tapi belum di-publish
    $budget->forceFill(['review_token' => str_repeat('a', 48), 'review_is_public' => false])->saveQuietly();

    $this->get(route('media-plan-external.review', ['token' => str_repeat('a', 48)]))
        ->assertNotFound();

    $this->get(route('media-plan-external.review', ['token' => 'tidak-ada']))
        ->assertNotFound();
});

it('submit menyimpan pilihan & feedback client lalu menandai submitted', function () {
    $budget = makeReviewBudget();
    $token = $budget->generateReviewToken();
    [$item1, $item2] = $budget->items;

    $this->post(route('media-plan-external.review.submit', ['token' => $token]), [
        'choices' => [
            $item1->id => 'approved',
            $item2->id => 'rejected',
        ],
        'feedback' => [
            $item1->id => 'Pakai yang ini',
            $item2->id => 'Skip dulu',
        ],
    ])->assertRedirect(route('media-plan-external.review', ['token' => $token]));

    $item1->refresh();
    $item2->refresh();
    $budget->refresh();

    expect($item1->client_choice)->toBe('approved');
    expect($item1->client_feedback)->toBe('Pakai yang ini');
    expect($item2->client_choice)->toBe('rejected');
    expect($budget->review_submitted_at)->not->toBeNull();

    // submit TIDAK mengubah status budget (BV finalisasi manual)
    expect($budget->status)->toBe('review_client');
});

it('setelah submit menampilkan state read-only', function () {
    $budget = makeReviewBudget();
    $token = $budget->generateReviewToken();
    $budget->forceFill(['review_submitted_at' => now()])->saveQuietly();

    $this->get(route('media-plan-external.review', ['token' => $token]))
        ->assertOk()
        ->assertSee('Review telah disubmit')
        // Form (button submit) tidak boleh dirender lagi setelah submit.
        ->assertDontSee('type="submit"', escape: false)
        ->assertSee('Ringkasan Pilihan Anda');
});

it('submit menolak nilai choice di luar approved/rejected', function () {
    $budget = makeReviewBudget();
    $token = $budget->generateReviewToken();
    [$item1] = $budget->items;

    $this->post(route('media-plan-external.review.submit', ['token' => $token]), [
        'choices' => [$item1->id => 'maybe'],
    ])->assertSessionHasErrors();
});
