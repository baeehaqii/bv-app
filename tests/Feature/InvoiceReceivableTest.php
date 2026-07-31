<?php

use App\Models\BvCashflow;
use App\Models\BvInvoice;
use App\Models\BvQuotation;
use App\Models\FinanceAccount;
use App\Models\User;

/**
 * Invoice / piutang usaha (AR) + saldo kas & bank.
 * Penerimaan kas hanya diakui saat invoice dibayar (SAK).
 */
function arQuotation(float $total = 100_000_000, bool $signed = true): BvQuotation
{
    $user = User::create([
        'name' => 'Pembuat Quotation',
        'email' => 'ar-' . uniqid() . '@bvnetwork.net',
        'password' => bcrypt('password'),
    ]);

    $q = BvQuotation::create([
        'quotation_number' => 'BV/Q/AR/' . uniqid(),
        'quotation_date' => now()->toDateString(),
        'client_name' => 'PT Piutang Uji',
        'total_amount' => $total,
        'status' => 'draft',
        'user_id' => $user->id,
    ]);

    if ($signed) {
        $q->sign('ceo', 'Bos Besar', 'CEO');
        $q->sign('bd', 'Mas BD', 'Business Development');
        $q->sign('client', 'PIC Client', 'Marketing Manager');
    }

    return $q->refresh();
}

function bankAccount(array $attributes = []): FinanceAccount
{
    return FinanceAccount::create(array_merge([
        'name' => 'BCA Operasional ' . uniqid(),
        'type' => 'bank',
        'opening_balance' => 5_000_000,
        'is_default' => true,
    ], $attributes));
}

it('menolak invoice dari quotation yang belum ditandatangani lengkap', function () {
    $q = arQuotation(signed: false);

    expect(fn() => BvInvoice::createFromQuotation($q, 10_000_000))->toThrow(RuntimeException::class);
    expect(BvInvoice::count())->toBe(0);
});

it('menerbitkan invoice termin dan menjaga sisa nilai yang belum ditagihkan', function () {
    $q = arQuotation(100_000_000);

    $dp = BvInvoice::createFromQuotation($q, 50_000_000, 'DP 50%');
    expect($dp->status)->toBe('sent')
        ->and($dp->invoice_number)->toStartWith('BV/INV/')
        ->and($dp->due_date->toDateString())->toBe(now()->addDays(14)->toDateString())
        ->and($q->refresh()->invoiced_amount)->toBe(50_000_000.0)
        ->and($q->uninvoiced_amount)->toBe(50_000_000.0);

    $pelunasan = BvInvoice::createFromQuotation($q, 50_000_000, 'Pelunasan');
    expect($q->refresh()->uninvoiced_amount)->toBe(0.0);

    // Nomor invoice berjalan dalam bulan yang sama.
    expect($pelunasan->invoice_number)->not->toBe($dp->invoice_number);

    // Belum dibayar → belum ada arus kas, tapi piutangnya tercatat.
    expect(BvCashflow::count())->toBe(0)
        ->and(BvInvoice::totalReceivable())->toBe(100_000_000.0);
});

it('pembayaran invoice masuk pos Penerimaan dari Pelanggan & menutup piutang', function () {
    $account = bankAccount();
    $q = arQuotation(40_000_000);
    $invoice = BvInvoice::createFromQuotation($q, 40_000_000, 'Tagihan Penuh');

    $invoice->markPaid(40_000_000, '2026-07-25', $account->id);

    $row = BvCashflow::sole();
    expect($row->category)->toBe('penerimaan_dari_pelanggan')
        ->and($row->type)->toBe('income')
        ->and($row->activity)->toBe('operasi')
        ->and((float) $row->amount)->toBe(40_000_000.0)
        ->and($row->reference_no)->toBe($invoice->invoice_number)
        ->and($row->finance_account_id)->toBe($account->id)
        ->and($row->transaction_date->toDateString())->toBe('2026-07-25');

    expect($invoice->refresh()->status)->toBe('paid')
        ->and($invoice->outstanding)->toBe(0.0)
        ->and(BvInvoice::totalReceivable())->toBe(0.0);

    // Idempoten: simpan ulang tidak menambah baris kas.
    $invoice->touch();
    expect(BvCashflow::count())->toBe(1);
});

it('pembayaran kurang dari tagihan menyisakan piutang', function () {
    $account = bankAccount();
    $q = arQuotation(30_000_000);
    $invoice = BvInvoice::createFromQuotation($q, 30_000_000);

    $invoice->markPaid(20_000_000, '2026-07-25', $account->id);

    expect($invoice->refresh()->status)->toBe('partially_paid')
        ->and($invoice->outstanding)->toBe(10_000_000.0)
        ->and(BvInvoice::totalReceivable())->toBe(10_000_000.0)
        ->and((float) BvCashflow::sole()->amount)->toBe(20_000_000.0);
});

it('menolak pembayaran melebihi nilai invoice atau nominal nol', function () {
    $q = arQuotation(10_000_000);
    $invoice = BvInvoice::createFromQuotation($q, 10_000_000);

    expect(fn() => $invoice->markPaid(11_000_000))->toThrow(InvalidArgumentException::class);
    expect(fn() => $invoice->markPaid(0))->toThrow(InvalidArgumentException::class);
    expect(BvCashflow::count())->toBe(0);
});

it('membatalkan pembayaran & membatalkan invoice menarik kembali arus kasnya', function () {
    $account = bankAccount();
    $q = arQuotation(15_000_000);
    $invoice = BvInvoice::createFromQuotation($q, 15_000_000);
    $invoice->markPaid(15_000_000, '2026-07-25', $account->id);
    expect(BvCashflow::count())->toBe(1);

    $invoice->unmarkPaid();
    expect(BvCashflow::count())->toBe(0)
        ->and($invoice->refresh()->status)->toBe('sent')
        ->and($invoice->outstanding)->toBe(15_000_000.0);

    $invoice->markPaid(15_000_000, '2026-07-25', $account->id);
    $invoice->void();

    expect(BvCashflow::count())->toBe(0)
        ->and($invoice->refresh()->outstanding)->toBe(0.0)
        // Invoice batal tidak lagi menambah nilai tertagih di quotation.
        ->and($q->refresh()->uninvoiced_amount)->toBe(15_000_000.0);

    expect(fn() => $invoice->markPaid(15_000_000))->toThrow(RuntimeException::class);
});

it('menandai invoice lewat jatuh tempo dan menghitungnya sebagai piutang', function () {
    $q = arQuotation(20_000_000);

    $lewat = BvInvoice::createFromQuotation($q, 8_000_000, 'DP', dueDate: now()->subDays(5));
    $belum = BvInvoice::createFromQuotation($q, 12_000_000, 'Pelunasan', dueDate: now()->addDays(10));

    expect($lewat->isOverdue())->toBeTrue()
        ->and($belum->isOverdue())->toBeFalse()
        ->and(BvInvoice::overdue()->count())->toBe(1)
        ->and(BvInvoice::totalReceivable())->toBe(20_000_000.0);

    // Sudah lunas tidak pernah dianggap jatuh tempo.
    $lewat->markPaid(8_000_000, now());
    expect($lewat->refresh()->isOverdue())->toBeFalse()
        ->and(BvInvoice::overdue()->count())->toBe(0);
});

it('menghitung saldo akun dari saldo awal + penerimaan − pengeluaran', function () {
    $account = bankAccount(['opening_balance' => 5_000_000]);
    $q = arQuotation(10_000_000);

    expect($account->balance)->toBe(5_000_000.0);

    BvInvoice::createFromQuotation($q, 10_000_000)->markPaid(10_000_000, '2026-07-25', $account->id);
    expect($account->refresh()->balance)->toBe(15_000_000.0);

    BvCashflow::create([
        'transaction_date' => '2026-07-26',
        'category' => 'beban_pemasaran',
        'amount' => 2_000_000,
        'finance_account_id' => $account->id,
    ]);
    expect($account->refresh()->balance)->toBe(13_000_000.0);
});

it('arus kas tanpa akun jatuh ke akun default, dan hanya ada satu default', function () {
    $utama = bankAccount(['name' => 'BCA Utama', 'is_default' => true]);

    BvCashflow::create([
        'transaction_date' => '2026-07-26',
        'category' => 'beban_gaji',
        'amount' => 1_000_000,
    ]);

    expect(BvCashflow::sole()->finance_account_id)->toBe($utama->id);

    // Menandai akun lain sebagai default mencabut default sebelumnya.
    $kas = bankAccount(['name' => 'Kas Kecil', 'type' => 'kas', 'is_default' => true]);

    expect($kas->refresh()->is_default)->toBeTrue()
        ->and($utama->refresh()->is_default)->toBeFalse()
        ->and(FinanceAccount::defaultId())->toBe($kas->id);
});
