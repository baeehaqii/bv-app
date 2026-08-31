<?php

use App\Models\InternalBudget;
use App\Models\InternalBudgetItem;
use App\Models\MediaPlan;
use App\Models\MediaPlanKol;

/**
 * Part B — Link Review Client untuk Media Plan External.
 * Memverifikasi: status baru, token publik, halaman review, submit pilihan client.
 *
 * Keputusan client diambil per KOL, bukan per SOW — lihat
 * InternalBudgetReviewController. Penyimpanannya tetap per item.
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

    // Satu KOL dengan dua SOW: keputusan client berlaku untuk keduanya.
    $dataKol = \App\Models\DataKol::create([
        'channel' => 'Instagram',
        'username' => 'bagusgandhi',
        'link_userprofile' => 'https://instagram.com/bagusgandhi',
        'followers' => 12000,
    ]);

    $kol = MediaPlanKol::create([
        'media_plan_id' => $mediaPlan->id,
        'row_number' => 1,
        'data_kol_id' => $dataKol->id,
        'name' => 'Bagus Gandhi',
        'channel' => 'Instagram',
        'notes' => 'Sudah pernah kerja sama tahun lalu',
    ]);

    InternalBudgetItem::create([
        'internal_budget_id' => $budget->id,
        'media_plan_kol_id' => $kol->id,
        'qty' => 1,
        'scope_item' => 'IG Reels',
        'rate_base' => 1_000_000,
        'sort_order' => 0,
    ]);
    InternalBudgetItem::create([
        'internal_budget_id' => $budget->id,
        'media_plan_kol_id' => $kol->id,
        'qty' => 1,
        'scope_item' => 'TikTok Video',
        'rate_base' => 2_000_000,
        'sort_order' => 1,
    ]);

    return $budget->fresh('items');
}

function kunciKol(InternalBudget $budget): string
{
    return 'kol-' . $budget->items->first()->media_plan_kol_id;
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
        ->assertSee('Bagus Gandhi')
        ->assertSee('IG Reels')
        ->assertSee('TikTok Video')
        ->assertSee('Submit Review');
});

it('menampilkan satu baris per KOL dengan label +n, bukan satu baris per SOW', function () {
    $budget = makeReviewBudget();
    $token = $budget->generateReviewToken();
    $kunci = kunciKol($budget);

    $halaman = $this->get(route('media-plan-external.review', ['token' => $token]));

    // Satu pasang radio untuk KOL-nya, bukan satu pasang per SOW.
    $html = $halaman->getContent();
    expect(substr_count($html, 'name="choices[' . $kunci . ']"'))->toBe(2)
        ->and(substr_count($html, 'name="choices['))->toBe(2);

    // SOW kedua hanya muncul sebagai "+1" di baris, rinciannya di modal.
    $halaman->assertSee('+1')
        ->assertSee('<dialog id="sow-' . $kunci . '"', escape: false);
});

it('menampilkan catatan KOL dari Media Plan Internal bila ada', function () {
    $budget = makeReviewBudget();
    $token = $budget->generateReviewToken();

    $this->get(route('media-plan-external.review', ['token' => $token]))
        ->assertSee('Sudah pernah kerja sama tahun lalu');
});

it('menampilkan username KOL di samping namanya', function () {
    $budget = makeReviewBudget();
    $token = $budget->generateReviewToken();

    $this->get(route('media-plan-external.review', ['token' => $token]))
        ->assertSee('Bagus Gandhi')
        ->assertSee('@bagusgandhi', escape: false);
});

it('mencari & paginasi dikerjakan di sisi client agar pilihan tidak hilang', function () {
    $budget = makeReviewBudget();
    $token = $budget->generateReviewToken();
    $kunci = kunciKol($budget);

    $halaman = $this->get(route('media-plan-external.review', ['token' => $token]));

    // Seluruh baris tetap dirender; JS hanya menyembunyikan yang di luar
    // halaman. Kalau ini berubah jadi paginasi server, pilihan yang belum
    // disubmit akan hilang setiap kali client pindah halaman.
    $halaman->assertSee('data-kol-baris', escape: false)
        ->assertSee('id="kol-cari"', escape: false)
        ->assertSee('data-per-halaman="10"', escape: false)
        ->assertSee('data-per-halaman="20"', escape: false)
        ->assertSee('data-per-halaman="50"', escape: false)
        ->assertSee('name="choices[' . $kunci . ']"', escape: false);
});

it('menyimpan usulan KOL pengganti dari client', function () {
    $budget = makeReviewBudget();
    $token = $budget->generateReviewToken();
    [$item1, $item2] = $budget->items;

    $this->post(route('media-plan-external.review.submit', ['token' => $token]), [
        'choices' => [kunciKol($budget) => 'rejected'],
        'replace' => [kunciKol($budget) => 'Tolong pakai @kolpilihanku saja'],
    ])->assertRedirect(route('media-plan-external.review', ['token' => $token]));

    // Ditulis seragam ke seluruh SOW milik KOL itu, sama seperti pilihannya.
    expect($item1->refresh()->client_replace_note)->toBe('Tolong pakai @kolpilihanku saja')
        ->and($item2->refresh()->client_replace_note)->toBe('Tolong pakai @kolpilihanku saja');
});

it('usulan pengganti muncul sebagai keterangan di Media Plan Internal', function () {
    $budget = makeReviewBudget();
    $budget->items()->update(['client_replace_note' => 'Tolong pakai @kolpilihanku saja']);

    \App\Filament\Resources\MediaPlans\Schemas\MediaPlanForm::lupakanCache();

    $render = \App\Filament\Resources\MediaPlans\Schemas\MediaPlanForm::class;
    $usulan = (new ReflectionClass($render))->getMethod('renderUsulanGantiClient');
    $usulan->setAccessible(true);

    $html = (string) $usulan->invoke(null, $budget->mediaPlan);

    // Satu baris per KOL, bukan satu per SOW — catatannya ditulis ke keduanya.
    expect(substr_count($html, '<li>'))->toBe(1)
        ->and($html)->toContain('Bagus Gandhi')
        ->and($html)->toContain('Tolong pakai @kolpilihanku saja');
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

it('satu pilihan per KOL ditulis ke seluruh SOW miliknya', function () {
    $budget = makeReviewBudget();
    $token = $budget->generateReviewToken();
    [$item1, $item2] = $budget->items;

    $this->post(route('media-plan-external.review.submit', ['token' => $token]), [
        'choices'  => [kunciKol($budget) => 'approved'],
        'feedback' => [kunciKol($budget) => 'Pakai yang ini'],
    ])->assertRedirect(route('media-plan-external.review', ['token' => $token]));

    $item1->refresh();
    $item2->refresh();
    $budget->refresh();

    // Client memilih orangnya sekali; kedua SOW-nya ikut.
    expect($item1->client_choice)->toBe('approved');
    expect($item1->client_feedback)->toBe('Pakai yang ini');
    expect($item2->client_choice)->toBe('approved');
    expect($item2->client_feedback)->toBe('Pakai yang ini');
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

    $this->post(route('media-plan-external.review.submit', ['token' => $token]), [
        'choices' => [kunciKol($budget) => 'maybe'],
    ])->assertSessionHasErrors();
});
