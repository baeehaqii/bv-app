<?php

use App\Models\InternalBudgetItem;
use App\Models\MasterMargin;

it('default margin Auto flat 50% sesuai sheet KOL List', function () {
    $item = new InternalBudgetItem(['subtotal' => 1_000_000]);
    expect($item->calculateAutoTargetMargin())->toBe(50.0);

    $item = new InternalBudgetItem(['subtotal' => 80_000_000]);
    expect($item->calculateAutoTargetMargin())->toBe(50.0);
});

it('tabel bertingkat di Master Margin dipakai kalau diisi', function () {
    MasterMargin::query()->delete();
    MasterMargin::create(['name' => 'Kecil', 'min_amount' => 0, 'max_amount' => 2_999_999, 'margin_percent' => 80, 'order' => 1, 'is_active' => true]);
    MasterMargin::create(['name' => 'Besar', 'min_amount' => 3_000_000, 'max_amount' => null, 'margin_percent' => 30, 'order' => 2, 'is_active' => true]);

    expect((new InternalBudgetItem(['subtotal' => 1_000_000]))->calculateAutoTargetMargin())->toBe(80.0)
        ->and((new InternalBudgetItem(['subtotal' => 10_000_000]))->calculateAutoTargetMargin())->toBe(30.0);
});

it('override manual mengalahkan tabel', function () {
    $item = new InternalBudgetItem(['subtotal' => 1_000_000, 'use_flexible_margin' => true, 'margin_percent_override' => 25]);
    expect($item->calculateAutoTargetMargin())->toBe(25.0);
});
