<?php

use App\Models\BvCampaignKol;
use App\Models\BvCampign;
use App\Models\CampaignKolPayment;

/**
 * Phase 1 — Campaign Ongoing Internal: tracking pembayaran KOL (sheet OFERO).
 * Fokus: baris pembayaran tahan terhadap re-sync KOL (data Paid/bukti transfer tidak hilang).
 */

function makeCampaignWithKols(array $names = ['adifi_', 'kadin5s']): BvCampign
{
    $campaign = BvCampign::create([
        'campaign_name' => 'Masters of the Universe',
        'campaign_type' => BvCampign::TYPE_INTERNAL,
        'status' => 'ongoing',
    ]);

    foreach ($names as $i => $name) {
        BvCampaignKol::create([
            'campaign_id' => $campaign->id,
            'creator_name' => $name,
            'username' => $name,
            'platform' => 'tiktok',
            'content_type' => 'video',
            'price' => ($i + 1) * 1_000_000,
            'status' => 'pending',
        ]);
    }

    return $campaign->fresh();
}

it('membuat satu baris pembayaran per KOL; real_cost default 0 tanpa konteks budget', function () {
    $campaign = makeCampaignWithKols();

    $campaign->load('kols');
    $campaign->syncPaymentRowsFromKols();

    expect($campaign->payments()->count())->toBe(2);

    // Tanpa Media Plan/InternalBudget → tak ada peta biaya aktual → default 0 (finance isi manual).
    // Biaya aktual dari budget item diuji di SonyPicturesEndToEndTest (Stage 6).
    $adifi = CampaignKolPayment::where('kol_name', 'adifi_')->first();
    expect((float) $adifi->real_cost)->toBe(0.0)
        ->and($adifi->payment_status)->toBe('waiting_payment')
        ->and($adifi->bv_campaign_kol_id)->not->toBeNull();
});

it('idempotent: sync ulang tidak menggandakan baris', function () {
    $campaign = makeCampaignWithKols();
    $campaign->load('kols');
    $campaign->syncPaymentRowsFromKols();
    $campaign->syncPaymentRowsFromKols();

    expect($campaign->payments()->count())->toBe(2);
});

it('mempertahankan data bayar saat KOL di-wipe & dibuat ulang (re-sync)', function () {
    $campaign = makeCampaignWithKols();
    $campaign->load('kols');
    $campaign->syncPaymentRowsFromKols();

    // Operator menandai sudah dibayar + isi bukti transfer.
    $payment = CampaignKolPayment::where('kol_name', 'adifi_')->first();
    $payment->update([
        'payment_status' => 'paid',
        'link_bukti_transfer' => 'https://drive.google.com/bukti',
        'npwp' => '09.123.456.7-890.000',
        'real_cost' => 2_500_000,
    ]);

    // Simulasikan re-sync InternalBudget: hapus semua KOL lalu buat ulang.
    $campaign->kols()->delete();
    BvCampaignKol::create([
        'campaign_id' => $campaign->id,
        'creator_name' => 'adifi_',
        'username' => 'adifi_',
        'platform' => 'tiktok',
        'content_type' => 'video',
        'price' => 9_999_999,
        'status' => 'pending',
    ]);

    $campaign->load('kols');
    $campaign->syncPaymentRowsFromKols();

    $payment->refresh();

    // Data bayar harus utuh, pointer di-relink ke KOL baru, real_cost manual TIDAK ditimpa.
    expect($payment->payment_status)->toBe('paid')
        ->and($payment->link_bukti_transfer)->toBe('https://drive.google.com/bukti')
        ->and($payment->npwp)->toBe('09.123.456.7-890.000')
        ->and((float) $payment->real_cost)->toBe(2_500_000.0)
        ->and($payment->bv_campaign_kol_id)->toBe($campaign->kols()->first()->id)
        ->and($campaign->payments()->count())->toBe(2);
});
