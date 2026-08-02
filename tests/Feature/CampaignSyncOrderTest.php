<?php

use App\Models\BvCampign;
use App\Models\BvSales;
use App\Models\BvSalesList;
use App\Models\DataClient;
use App\Models\MediaPlanKol;
use App\Support\MotuScenarioData as Motu;

/**
 * Jaminan ROBUSTNESS rantai approval → Campaign Ongoing:
 * - Sync KOL + seed storyline harus jalan APA PUN urutan event (Campaign Live lebih dulu
 *   atau Approve AM lebih dulu) — memastikan perbaikan kondisi 'approve_am' & seed
 *   order-independent (bukan kondisi mati 'approved' yang lama).
 * - Sync bersifat idempotent (tidak menggandakan KOL bila dipanggil ulang).
 */

/** Bangun sampai budget punya 3 item approved, BELUM approve AM, campaign belum ada. */
function buildApprovedBudget(): array
{
    $gerry = BvSalesList::create(['nama_sales' => 'Gerry']);
    DataClient::create(['nama_brand' => 'Sony Pictures', 'type' => 'direct']);

    $sales = BvSales::create([
        'bv_sales_list_id' => $gerry->id,
        'event_name' => 'Masters of the Universe',
        'company_name' => 'Sony Pictures',
        'pic_media_plan' => 'Baehaqi',
        'start_date' => '2026-06-01',
        'end_date' => '2026-06-30',
        'status' => 'not_started',
    ]);
    $sales->update(['status' => 'briefing']);
    $sales->refresh();

    $mediaPlan = $sales->mediaPlan;
    $row = 1;
    foreach (array_slice(Motu::shortlist(), 0, 3) as $k) {
        MediaPlanKol::create([
            'media_plan_id' => $mediaPlan->id, 'row_number' => $row++,
            'name' => $k['name'], 'channel' => $k['channel'], 'rate' => $k['rate'],
            'status' => 'Locked', 'is_selected' => true, 'scope_items' => ['TT Video'],
        ]);
    }
    $mediaPlan->update(['status' => 'To Client']);

    $budget = $mediaPlan->internalBudget->refresh();
    foreach ($budget->items as $item) {
        $item->approve();
    }
    // quotation + ongoing disiapkan agar tryActivateCampaign juga bisa lewat
    $mediaPlan->update(['quotation_signed_path' => 'q.pdf', 'quotation_signed_at' => now(), 'status' => 'Ongoing']);

    return [$sales, $mediaPlan, $budget->refresh()];
}

it('urutan Campaign Live DULU, lalu Approve AM → KOL & storyline tetap ter-seed', function () {
    [$sales, $mediaPlan, $budget] = buildApprovedBudget();

    // Campaign Live lebih dulu → campaign dibuat, budget belum approve_am → belum ada KOL
    $sales->update(['status' => 'campaign_live']);
    $campaign = $sales->refresh()->campaign;
    expect($campaign)->not->toBeNull();
    expect($campaign->kols()->count())->toBe(0);

    // Approve AM menyusul → boot hook menyiramkan KOL + storyline ke campaign yang sudah ada
    $budget->approve();

    $campaign->refresh();
    expect($campaign->kols()->count())->toBe(3);
    expect($campaign->storylines()->count())->toBe(3);
    expect($campaign->campaign_type)->toBe(BvCampign::TYPE_INTERNAL);
});

it('urutan Approve AM DULU, lalu Campaign Live → hasil akhir sama (3 KOL + 3 storyline)', function () {
    [$sales, $mediaPlan, $budget] = buildApprovedBudget();

    $budget->approve();              // approve_am, campaign belum ada → no-op aman
    $sales->update(['status' => 'campaign_live']); // campaign dibuat → sync jalan

    $campaign = $sales->refresh()->campaign;
    expect($campaign->kols()->count())->toBe(3);
    expect($campaign->storylines()->count())->toBe(3);
    expect($campaign->campaign_type)->toBe(BvCampign::TYPE_INTERNAL);
});

it('sync idempotent: memanggil ulang tidak menggandakan KOL / storyline', function () {
    [$sales, $mediaPlan, $budget] = buildApprovedBudget();
    $budget->approve();
    $sales->update(['status' => 'campaign_live']);
    $campaign = $sales->refresh()->campaign;

    // Panggil ulang manual
    $budget->fresh()->syncCampaignKolsFromApprovedBudget();
    $budget->fresh()->syncCampaignKolsFromApprovedBudget();

    expect($campaign->refresh()->kols()->count())->toBe(3);
    expect($campaign->storylines()->count())->toBe(3); // seed idempotent (tidak duplikat)
});

it('username KOL terisi otomatis dari Database KOL lewat data_kol_id', function () {
    [$sales, $mediaPlan, $budget] = buildApprovedBudget();
    $budget->approve();
    $sales->update(['status' => 'campaign_live']);
    $campaign = $sales->refresh()->campaign;

    $kol = $mediaPlan->kols()->first();

    // Belum ter-link & nama tidak ada di Database KOL → form tidak mengisi apa-apa.
    expect($campaign->approvedKolUsername($kol->name))->toBeNull();
    expect($campaign->approvedKolUsername(null))->toBeNull();

    // full_name sengaja beda: membuktikan resolusi lewat data_kol_id, bukan cocok nama.
    $dataKol = \App\Models\DataKol::create([
        'username' => 'si_kreator',
        'full_name' => 'Nama Beda',
        'channel' => 'Tiktok',
        'link_userprofile' => 'https://www.tiktok.com/@si_kreator',
    ]);
    $kol->update(['data_kol_id' => $dataKol->id]);

    expect($campaign->approvedKolUsername($kol->name))->toBe('si_kreator');
});

it('kolom SPK di Pembayaran KOL menemukan SPK dari modul SPK KOL', function () {
    [$sales, $mediaPlan, $budget] = buildApprovedBudget();
    $budget->approve();
    $sales->update(['status' => 'campaign_live']);
    $campaign = $sales->refresh()->campaign;
    $campaign->syncPaymentRowsFromKols();

    $kol = $mediaPlan->kols()->first();
    $payment = $campaign->payments()->where('kol_name', $kol->name)->sole();

    // SPK belum diterbitkan → kolom kosong, bukan error.
    expect($payment->resolveSpk())->toBeNull();

    $spk = \App\Models\BvSPK::createForKol($budget, $kol->id);

    expect($payment->fresh()->resolveSpk()?->id)->toBe($spk->id)
        // KOL lain di budget yang sama tidak ikut kebawa SPK ini.
        ->and($campaign->payments()->where('kol_name', '!=', $kol->name)->get()
            ->every(fn($p) => $p->resolveSpk() === null))->toBeTrue();
});

it('sync pembayaran menarik identitas & rekening dari Database KOL, tanpa menimpa isian manual', function () {
    [$sales, $mediaPlan, $budget] = buildApprovedBudget();
    $budget->approve();
    $sales->update(['status' => 'campaign_live']);
    $campaign = $sales->refresh()->campaign;

    $kol = $mediaPlan->kols()->first();
    $dataKol = \App\Models\DataKol::create([
        'username' => 'si_kreator',
        'channel' => 'Tiktok',
        'link_userprofile' => 'https://www.tiktok.com/@si_kreator',
        'full_name' => 'Nama Lengkap KOL',
        'nik' => '3201132001000006',
        'address' => 'Jl. Tugu 4, Bekasi',
        'bank_name' => 'SeaBank',
        'bank_account_number' => '901323084234',
        'bank_account_name' => 'Nama Lengkap KOL',
    ]);
    $kol->update(['data_kol_id' => $dataKol->id]);

    // Rekening sengaja dikoreksi manual duluan → sync tidak boleh menimpanya.
    $campaign->payments()->where('kol_name', $kol->name)
        ->update(['nomor_rekening' => '999-rekening-khusus']);

    $campaign->load('kols')->syncPaymentRowsFromKols();

    $payment = $campaign->payments()->where('kol_name', $kol->name)->sole();

    expect($payment->username)->toBe('si_kreator')
        ->and($payment->alamat)->toBe('Jl. Tugu 4, Bekasi')
        ->and($payment->ktp)->toBe('3201132001000006')
        ->and($payment->nama_bank)->toBe('SeaBank')
        ->and($payment->nama_rekening)->toBe('Nama Lengkap KOL')
        ->and($payment->detail_sow)->toBe('TT Video')
        ->and($payment->nomor_rekening)->toBe('999-rekening-khusus');
});

it('form edit pembayaran mengisi field kosong dari Database KOL tanpa perlu Sync dulu', function () {
    [$sales, $mediaPlan, $budget] = buildApprovedBudget();
    $budget->approve();
    $sales->update(['status' => 'campaign_live']);
    $campaign = $sales->refresh()->campaign;

    $kol = $mediaPlan->kols()->first();
    // Link platform saja, TANPA data_kol_id → username diambil dari URL-nya.
    $kol->update(['links' => ['https://www.instagram.com/akun_dari_link'], 'data_kol_id' => null]);

    $payment = $campaign->payments()->where('kol_name', $kol->name)->sole();
    $payment->update(['username' => null, 'pic' => 'Baehaqi']);

    $data = $payment->fresh()->formDefaults();

    expect($data['username'])->toBe('akun_dari_link')
        // Nilai tersimpan menang atas isian dari Database KOL.
        ->and($data['pic'])->toBe('Baehaqi')
        // Yang di-fill hanya untuk form; baris DB belum berubah sampai disimpan.
        ->and($payment->fresh()->username)->toBeNull();
});
