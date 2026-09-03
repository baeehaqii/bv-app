<?php

use App\Enums\SalesStatus;
use App\Models\BvSales;
use App\Models\GrossProfitTarget;

it('target GP dihitung otomatis dari revenue x benchmark margin', function () {
    // Angka dari sheet 2026 Sales Target: Jul 1.1M revenue @31% = 341jt GP
    $target = GrossProfitTarget::create([
        'year' => 2026,
        'month' => 7,
        'target_deal_revenue' => 1_100_000_000,
        'margin_benchmark_percent' => 31,
    ]);

    expect((float) $target->target_amount)->toBe(341_000_000.0);

    $target->update(['margin_benchmark_percent' => 40]);

    expect((float) $target->fresh()->target_amount)->toBe(440_000_000.0);
});

it('actual GP & profit margin dihitung dari deal yang sudah won', function () {
    $target = GrossProfitTarget::create([
        'year' => 2026,
        'month' => 8,
        'target_deal_revenue' => 1_000_000_000,
        'margin_benchmark_percent' => 31,
    ]);

    BvSales::create([
        'event_name' => 'Deal Won',
        'company_name' => 'Brand A',
        'status' => SalesStatus::PAID->value,
        'close_date' => '2026-08-10',
        'deal_value' => 500_000_000,
        'margin' => 30,
    ]);

    // Belum won → tidak dihitung
    BvSales::create([
        'event_name' => 'Masih Nego',
        'company_name' => 'Brand B',
        'status' => SalesStatus::NEGOTIATION->value,
        'close_date' => '2026-08-15',
        'deal_value' => 900_000_000,
        'margin' => 50,
    ]);

    // Bulan lain → tidak dihitung
    BvSales::create([
        'event_name' => 'Deal September',
        'company_name' => 'Brand C',
        'status' => SalesStatus::PAID->value,
        'close_date' => '2026-09-01',
        'deal_value' => 200_000_000,
        'margin' => 40,
    ]);

    $target = $target->fresh();

    expect($target->actual_revenue)->toBe(500_000_000.0)
        ->and($target->actual_gp)->toBe(150_000_000.0)
        ->and($target->profit_margin_percent)->toBe(30.0)
        ->and($target->gp_achievement_percent)->toBe(48.39); // 150jt / 310jt
});
