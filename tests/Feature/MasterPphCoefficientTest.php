<?php

use App\Models\InternalBudgetItem;
use App\Models\MasterPph;

/**
 * Koefisien PPh dipakai sebagai pembagi: MU PPh = subtotal / coefficient.
 * Untuk PT PKP, PPN 11% MENAMBAH uang keluar — pernah keliru dikalikan sehingga
 * cost jadi ~19% lebih rendah dari sheet KOL List client.
 */
it('PT PKP menambahkan PPN, bukan mengurangi cost', function () {
    $pkp = new MasterPph(['coefficient' => 0.98, 'include_ppn' => true, 'ppn_percent' => 11.00]);

    $subtotal = 5_000_000;
    $costSheet = $subtotal / 0.98 + $subtotal * 0.11; // rumus kolom Z di sheet client

    expect($subtotal / $pkp->getCalculatedCoefficient())->toEqualWithDelta($costSheet, 0.01)
        ->and($subtotal / $pkp->getCalculatedCoefficient())->toBeGreaterThan($subtotal);
});

it('koefisien sama dengan InternalBudgetItem::calculateMuPph untuk semua tipe pajak', function (string $tipe, float $koef, bool $ppn) {
    $pph = new MasterPph([
        'coefficient' => $koef,
        'include_ppn' => $ppn,
        'ppn_percent' => $ppn ? 11.00 : null,
    ]);

    $item = new InternalBudgetItem(['vendor_tax_type' => $tipe, 'subtotal' => 5_000_000]);

    expect(5_000_000 / $pph->getCalculatedCoefficient())
        ->toEqualWithDelta($item->calculateMuPph(), 0.01);
})->with([
    ['Pribadi', 0.975, false],
    ['PT Non PKP', 0.98, false],
    ['PT PKP', 0.98, true],
    ['CV', 0.995, false],
]);

it('tanpa PPN koefisien tidak berubah', function () {
    expect((new MasterPph(['coefficient' => 0.975, 'include_ppn' => false]))->getCalculatedCoefficient())
        ->toBe(0.975);
});

/**
 * Kolom "Tax" di PDF Internal Budget meniru kolom Y sheet client: 0.11 untuk
 * PT PKP, kosong untuk tipe lain. Sebelumnya membaca $item->tax_rate — kolom
 * yang tidak pernah ada di tabel — jadi selalu tercetak 0.000.
 */
it('kolom Tax mengambil PPN dari master PPh, bukan kolom yang tidak ada', function () {
    $pkp = MasterPph::create([
        'name' => 'PT PKP', 'entity_type' => 'PT', 'coefficient' => 0.98,
        'include_ppn' => true, 'ppn_percent' => 11.00, 'order' => 1, 'is_active' => true,
    ]);
    $pribadi = MasterPph::create([
        'name' => 'Pribadi', 'entity_type' => 'Pribadi', 'coefficient' => 0.975,
        'include_ppn' => false, 'ppn_percent' => null, 'order' => 2, 'is_active' => true,
    ]);

    expect((new InternalBudgetItem(['master_pph_id' => $pkp->id]))->taxRateDecimal())->toBe(0.11)
        ->and((new InternalBudgetItem(['master_pph_id' => $pribadi->id]))->taxRateDecimal())->toBe(0.0)
        ->and((new InternalBudgetItem)->taxRateDecimal())->toBe(0.0);

    // Override manual menang atas PPN otomatis.
    $override = new InternalBudgetItem([
        'master_pph_id' => $pkp->id, 'use_flexible_tax' => true, 'tax_rate_percent' => 5.00,
    ]);
    expect($override->taxRateDecimal())->toBe(0.05);
});
