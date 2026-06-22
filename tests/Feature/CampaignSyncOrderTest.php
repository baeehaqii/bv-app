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
