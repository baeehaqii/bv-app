<?php

use App\Models\BvSPK;
use App\Models\DataKol;
use App\Models\InternalBudget;
use App\Models\InternalBudgetItem;
use App\Models\MediaPlan;
use App\Models\MediaPlanKol;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

/**
 * Bikin 1 media plan + budget dengan 2 KOL:
 *  - Justeen: 2 item approved (200rb + 316rb) → 1 SPK, nominal 516rb
 *  - Rejected: 1 item rejected → tidak dapat SPK
 */
function budgetWithApprovedKols(): InternalBudget
{
    // Counter: fixture ini dipanggil lebih dari sekali dalam satu test
    // (mis. membandingkan jalur per-KOL vs batch), dan quotation_number unik.
    static $urutan = 0;
    $urutan++;

    $mediaPlan = MediaPlan::create([
        'quotation_number' => 'BV-2026-' . str_pad((string) $urutan, 4, '0', STR_PAD_LEFT),
        'campaign_name' => 'GoPay Gamers',
        'campaign_period_start' => '2026-07-01',
        'campaign_period_end' => '2026-07-31',
        'brand' => 'GoPay',
    ]);

    $dataKol = DataKol::firstOrCreate([
        'username' => 'justeenff',
        'channel' => 'TikTok',
    ], [
        'link_userprofile' => 'https://tiktok.com/@justeenff',
        'full_name' => 'M. Farhan Fava Rizky',
        'nik' => '3201132001000006',
        'address' => 'Jl. Tugu 4 Kav. 321 RT/RW 003/010, Kel. Jaticempaka, Kota Bekasi',
        'bank_account_name' => 'M. Farhan Fava Rizky',
        'bank_account_number' => '901323084234',
        'bank_name' => 'SeaBank',
    ]);

    $justeen = MediaPlanKol::create([
        'media_plan_id' => $mediaPlan->id,
        'data_kol_id' => $dataKol->id,
        'name' => 'justeenff',
        'channel' => 'TikTok',
    ]);

    $ditolak = MediaPlanKol::create([
        'media_plan_id' => $mediaPlan->id,
        'name' => 'kol-ditolak',
        'channel' => 'Instagram',
    ]);

    $budget = InternalBudget::create([
        'media_plan_id' => $mediaPlan->id,
        'status' => 'approve_client',
    ]);

    InternalBudgetItem::create([
        'internal_budget_id' => $budget->id,
        'media_plan_kol_id' => $justeen->id,
        'scope_item' => 'TikTok Video',
        'qty' => 1,
        'rate_base' => 200_000,
        'status' => 'approved',
    ]);

    InternalBudgetItem::create([
        'internal_budget_id' => $budget->id,
        'media_plan_kol_id' => $justeen->id,
        'scope_item' => 'Content Owning',
        'qty' => 2,
        'rate_base' => 316_000,
        'status' => 'approved',
    ]);

    InternalBudgetItem::create([
        'internal_budget_id' => $budget->id,
        'media_plan_kol_id' => $ditolak->id,
        'scope_item' => 'IG Reels',
        'qty' => 1,
        'rate_base' => 999_000,
        'status' => 'rejected',
    ]);

    return $budget;
}

it('menerbitkan satu SPK per KOL approved dengan semua SOW-nya digabung', function () {
    $budget = budgetWithApprovedKols();

    $created = BvSPK::createFromBudget($budget);

    expect($created)->toHaveCount(1);

    $spk = $created->first();

    // Nominal = SUM(rate_base) item approved, basis yang sama dengan CampaignKolPayment::real_cost.
    expect((float) $spk->nominal_kesepakatan)->toBe(516_000.0)
        ->and($spk->nominal_terbilang)->toBe('Lima ratus enam belas ribu rupiah');

    // Semua SOW KOL ini masuk satu kontrak, pakai qty per item.
    expect($spk->sow_disepakati)->toBe("1x TikTok Video\n2x Content Owning");

    // PIHAK KEDUA = KOL, bukan PIC client.
    expect($spk->pihak_kedua_nama_lengkap)->toBe('M. Farhan Fava Rizky')
        ->and($spk->pihak_kedua_nama_akun)->toBe('justeenff (TikTok)')
        ->and($spk->pihak_kedua_nik)->toBe('3201132001000006')
        ->and($spk->nomor_rekening)->toBe('901323084234')
        ->and($spk->nama_bank)->toBe('SeaBank');

    expect($spk->nama_campaign)->toBe('GoPay Gamers')
        ->and($spk->timeline_kerja_sama)->toBe('Juli 2026')
        ->and($spk->status)->toBe('draft');
});

it('tidak menerbitkan SPK untuk KOL yang di-reject client', function () {
    $budget = budgetWithApprovedKols();

    BvSPK::createFromBudget($budget);

    expect(BvSPK::whereHas('mediaPlanKol', fn($q) => $q->where('name', 'kol-ditolak'))->exists())
        ->toBeFalse();
});

it('idempoten: klik ulang tidak membuat SPK ganda, tapi menyusul KOL yang baru approved', function () {
    $budget = budgetWithApprovedKols();

    expect(BvSPK::createFromBudget($budget))->toHaveCount(1);
    expect(BvSPK::createFromBudget($budget))->toHaveCount(0);

    // Client akhirnya approve KOL yang tadinya rejected.
    InternalBudgetItem::where('internal_budget_id', $budget->id)
        ->where('status', 'rejected')
        ->update(['status' => 'approved']);

    expect(BvSPK::createFromBudget($budget->fresh()))->toHaveCount(1)
        ->and(BvSPK::where('internal_budget_id', $budget->id)->count())->toBe(2);
});

it('menerbitkan SPK untuk satu KOL saja tanpa menyentuh KOL lain', function () {
    $budget = budgetWithApprovedKols();

    // Approve juga KOL kedua supaya ada dua kandidat.
    InternalBudgetItem::where('internal_budget_id', $budget->id)
        ->where('status', 'rejected')
        ->update(['status' => 'approved']);

    $justeen = MediaPlanKol::where('name', 'justeenff')->value('id');

    $spk = BvSPK::createForKol($budget, $justeen);

    expect($spk)->not->toBeNull()
        ->and($spk->media_plan_kol_id)->toBe($justeen)
        // KOL lain tidak ikut terbit.
        ->and(BvSPK::count())->toBe(1);

    // Semua SOW milik KOL itu tetap digabung — bukan satu SPK per baris SOW.
    expect((float) $spk->nominal_kesepakatan)->toBe(516_000.0)
        ->and($spk->sow_disepakati)->toBe("1x TikTok Video\n2x Content Owning");
});

it('menghasilkan SPK yang identik lewat jalur per-KOL maupun batch', function () {
    $abaikan = ['id', 'spk_number', 'created_at', 'updated_at'];

    $b1 = budgetWithApprovedKols();
    $satuan = BvSPK::createForKol($b1, MediaPlanKol::where('name', 'justeenff')->value('id'));

    // Budget kedua yang identik, lewat jalur batch.
    $b2 = budgetWithApprovedKols();
    $batch = BvSPK::createFromBudget($b2)->first();

    $bersihkan = fn(BvSPK $s) => collect($s->attributesToArray())
        ->except([...$abaikan, 'internal_budget_id', 'media_plan_kol_id', 'data_kol_id', 'client_id', 'form_brief_id'])
        ->all();

    expect($bersihkan($satuan))->toBe($bersihkan($batch));
});

it('menolak menerbitkan SPK dua kali untuk KOL yang sama', function () {
    $budget = budgetWithApprovedKols();
    $justeen = MediaPlanKol::where('name', 'justeenff')->value('id');

    expect(BvSPK::createForKol($budget, $justeen))->not->toBeNull()
        ->and(BvSPK::existsForKol($budget, $justeen))->toBeTrue()
        ->and(BvSPK::createForKol($budget, $justeen))->toBeNull()
        ->and(BvSPK::count())->toBe(1);
});

it('menolak KOL yang tidak punya item approved', function () {
    $budget = budgetWithApprovedKols();
    $ditolak = MediaPlanKol::where('name', 'kol-ditolak')->value('id');

    expect(BvSPK::createForKol($budget, $ditolak))->toBeNull()
        ->and(BvSPK::existsForKol($budget, $ditolak))->toBeFalse();
});

it('menyisakan KOL lain untuk tombol batch setelah satu KOL diterbitkan sendiri', function () {
    $budget = budgetWithApprovedKols();

    InternalBudgetItem::where('internal_budget_id', $budget->id)
        ->where('status', 'rejected')
        ->update(['status' => 'approved']);

    BvSPK::createForKol($budget, MediaPlanKol::where('name', 'justeenff')->value('id'));

    // Batch hanya menyusul KOL yang belum punya SPK.
    $sisa = BvSPK::createFromBudget($budget->fresh());

    expect($sisa)->toHaveCount(1)
        ->and($sisa->first()->mediaPlanKol->name)->toBe('kol-ditolak')
        ->and(BvSPK::count())->toBe(2);
});

it('menomori SPK urut per bulan dengan format BVN/SPK/YYYY/MM/NNN', function () {
    $this->travelTo('2026-07-15');

    expect(BvSPK::generateNumber())->toBe('BVN/SPK/2026/07/001');

    BvSPK::create(['spk_number' => 'BVN/SPK/2026/07/001', 'status' => 'draft']);
    BvSPK::create(['spk_number' => 'BVN/SPK/2026/07/002', 'status' => 'draft']);

    expect(BvSPK::generateNumber())->toBe('BVN/SPK/2026/07/003');

    // Bulan baru mulai dari 001 lagi.
    $this->travelTo('2026-08-02');
    expect(BvSPK::generateNumber())->toBe('BVN/SPK/2026/08/001');
});

it('mengeja nominal ke terbilang rupiah', function (float $angka, string $harapan) {
    expect(BvSPK::terbilang($angka))->toBe($harapan);
})->with([
    [0, 'Nol rupiah'],
    [11_000, 'Sebelas ribu rupiah'],
    [516_000, 'Lima ratus enam belas ribu rupiah'],
    [1_000_000, 'Satu juta rupiah'],
    [1_500_000, 'Satu juta lima ratus ribu rupiah'],
    [47_898_718, 'Empat puluh tujuh juta delapan ratus sembilan puluh delapan ribu tujuh ratus delapan belas rupiah'],
    [2_000_000_000, 'Dua miliar rupiah'],
]);
