<?php

use App\Models\BvCampign;
use App\Models\BvCashflow;
use App\Models\BvQuotation;
use App\Models\CampaignKolPayment;
use App\Models\User;

/**
 * Auto-posting arus kas mengikuti Standar Akuntansi Keuangan:
 * kas dicatat saat benar-benar cair, dan pos akunnya dipisah sesuai
 * penyajian laporan arus kas metode langsung (PSAK 2).
 */
function kolPayment(array $attributes = []): CampaignKolPayment
{
    $campaign = BvCampign::create([
        'campaign_name' => 'Campaign Kas ' . uniqid(),
        'campaign_type' => BvCampign::TYPE_INTERNAL,
        'status' => 'ongoing',
    ]);

    return CampaignKolPayment::create(array_merge([
        'campaign_id' => $campaign->id,
        'kol_name' => 'KOL Uji ' . uniqid(),
        'platform' => 'TikTok',
        'real_cost' => 10_000_000,   // netto jasa KOL (rate_base)
        'cost_tax' => 10_256_410,    // total kas keluar setelah gross-up (mu_pph)
        'payment_status' => 'waiting_payment',
    ], $attributes));
}

function signedQuotation(): BvQuotation
{
    $user = User::create([
        'name' => 'Pembuat Quotation',
        'email' => 'kas-' . uniqid() . '@bvnetwork.net',
        'password' => bcrypt('password'),
    ]);

    $q = BvQuotation::create([
        'quotation_number' => 'BV/Q/KAS/' . uniqid(),
        'quotation_date' => now()->toDateString(),
        'client_name' => 'PT Kas Uji',
        'total_amount' => 25_000_000,
        'status' => 'draft',
        'user_id' => $user->id,
    ]);

    $q->sign('ceo', 'Bos Besar', 'CEO');
    $q->sign('bd', 'Mas BD', 'Business Development');
    $q->sign('client', 'PIC Client', 'Marketing Manager');

    return $q->refresh();
}

it('tidak mencatat kas selama pembayaran KOL belum cair', function () {
    kolPayment();

    expect(BvCashflow::count())->toBe(0);
});

it('memecah pembayaran KOL yang cair jadi Beban Pokok Pendapatan + Pembayaran Pajak', function () {
    $payment = kolPayment(['payment_status' => 'paid', 'invoice_date_received' => '2026-07-20']);

    $pokok = BvCashflow::where('category', 'beban_pokok_pendapatan')->sole();
    $pajak = BvCashflow::where('category', 'pembayaran_pajak')->sole();

    expect((float) $pokok->amount)->toBe(10_000_000.0)
        ->and((float) $pajak->amount)->toBe(256_410.0)
        // Jenis & aktivitas diturunkan dari pos akun, bukan dari input.
        ->and($pokok->type)->toBe('expense')
        ->and($pokok->activity)->toBe('operasi')
        ->and($pajak->activity)->toBe('operasi')
        ->and($pokok->transaction_date->toDateString())->toBe('2026-07-20')
        ->and($pokok->isAutoPosted())->toBeTrue();

    // Total kas keluar = cost_tax (nilai gross-up), bukan dobel.
    expect((float) BvCashflow::expense()->sum('amount'))->toBe(10_256_410.0);

    // Idempoten: simpan ulang tidak menambah baris.
    $payment->touch();
    expect(BvCashflow::count())->toBe(2);
});

it('status PAID terkunci — arus kas tidak bisa dicabut lewat ubah status', function () {
    $payment = kolPayment(['payment_status' => 'paid']);
    expect(BvCashflow::count())->toBe(2);

    $payment->update(['payment_status' => 'waiting_payment']);

    expect($payment->fresh()->payment_status)->toBe('paid')
        ->and(BvCashflow::count())->toBe(2);
});

it('baris kas tetap tercabut bila baris pembayaran KOL dihapus', function () {
    $payment = kolPayment(['payment_status' => 'paid']);
    expect(BvCashflow::count())->toBe(2);

    $payment->delete();
    expect(BvCashflow::count())->toBe(0);
});

it('KOL tanpa gross-up hanya mencatat beban pokok, tanpa baris pajak nol', function () {
    kolPayment(['payment_status' => 'paid', 'cost_tax' => 0]);

    expect(BvCashflow::count())->toBe(1)
        ->and(BvCashflow::sole()->category)->toBe('beban_pokok_pendapatan')
        ->and((float) BvCashflow::sole()->amount)->toBe(10_000_000.0);
});

it('quotation tidak pernah mencatat penerimaan kas sendiri — pintunya invoice', function () {
    $q = signedQuotation();

    // Tidak ada jalur pelunasan di quotation: uang masuk hanya lewat BvInvoice,
    // supaya penerimaan kas tidak dicatat dobel.
    expect(BvCashflow::count())->toBe(0)
        ->and(method_exists($q, 'markPaid'))->toBeFalse()
        ->and($q->invoices()->count())->toBe(0);
});

it('menolak pos akun di luar daftar SAK', function () {
    $payment = kolPayment();

    expect(fn() => BvCashflow::post($payment, 'beban_ngawur', 1000, now(), 'Uji'))
        ->toThrow(InvalidArgumentException::class);
});
