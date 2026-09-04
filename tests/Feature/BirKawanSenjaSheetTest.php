<?php

use App\Models\{InternalBudget, InternalBudgetItem, MasterMargin, MasterPph, MediaPlan};

/**
 * Acuan: docs/berkas-referensi/[INT] Bir Kawan Senja - KOL List (1).xlsx
 * (sheet Nano/Micro/Macro/Homeless Media — 197 baris berisi, rumus kolom W-AD
 * identik semua). Angka di bawah dibaca langsung dari cached value file itu.
 *
 *   W  Subtotal          = T * V                (qty * rate)
 *   X  Gross Up PPh      = 0.98
 *   Y  Tax               = 0.11                 (PPN, MENAMBAH cost)
 *   Z  MU PPh* (cost)    = (W / X) + (W * Y)
 *   AA MU**              = Z / 0.5              (margin target flat 50%)
 *   AC Rounded           = ROUNDUP(AA, -5)      (bulat ke atas per 100rb)
 *   AD Margin %          = (AC - Z) / AC
 */
function bksItem(float $rate, int $qty = 1): InternalBudgetItem
{
    $plan = MediaPlan::create([
        'brand' => 'Bir Kawan Senja',
        'campaign_name' => 'BKS',
        'quotation_number' => 'Q-'.uniqid(),
    ]);

    // Persis seperti CreateMediaPlan: hanya master_pph_id yang diisi,
    // vendor_tax_type dibiarkan ikut default kolom DB.
    return InternalBudget::create(['media_plan_id' => $plan->id])
        ->items()
        ->create([
            'scope_item' => 'IG Reels',
            'qty' => $qty,
            'rate_base' => $rate,
            'master_pph_id' => MasterPph::defaultId(),
            'sort_order' => 1,
        ])
        ->refresh();
}

beforeEach(function () {
    (new Database\Seeders\MasterPphSeeder)->run();
    (new Database\Seeders\MasterMarginSeeder)->run();
    (new Database\Seeders\MediaPlanCalcSettingSeeder)->run();
    MasterPph::forgetCachedDefault();
    App\Models\MediaPlanCalcSetting::forgetCached();
});

it('master data seeder sama dengan sheet: PPh 0.98 + PPN 11%, margin flat 50%', function () {
    MasterPph::forgetCachedDefault();
    $pkp = MasterPph::defaultRow();

    expect($pkp->name)->toBe('PT PKP')                       // ditandai is_default
        ->and((float) $pkp->coefficient)->toBe(0.98)         // kolom X
        ->and((float) $pkp->ppn_percent)->toBe(11.0)         // kolom Y
        ->and(MasterMargin::getMarginForAmount(1_000_000))->toBe(50.0)
        ->and(MasterMargin::getMarginForAmount(80_000_000))->toBe(50.0)
        ->and(MasterMargin::count())->toBe(1); // tak ada baris bertingkat yang membayangi
});

it('baris budget mereproduksi kolom Z/AA/AC/AD sheet', function (float $rate, float $z, float $aa, float $ac, float $ad) {
    $item = bksItem($rate);

    expect((float) $item->subtotal)->toEqualWithDelta($rate, 0.01)
        ->and((float) $item->mu_pph)->toEqualWithDelta($z, 1)
        ->and((float) $item->target_margin_percent)->toBe(50.0)
        ->and((float) $item->mu_target)->toEqualWithDelta($aa, 1)
        ->and((float) $item->rounded)->toEqualWithDelta($ac, 0.01)
        ->and((float) $item->actual_margin_percent)->toEqualWithDelta($ad * 100, 0.01);
})->with([
    // rate,        Z,             AA,            AC,          AD
    [2_000_000.0, 2_260_816.33, 4_521_632.65, 4_600_000.0, 0.508518],  // Micro   IG Reels
    [  500_000.0,   565_204.08, 1_130_408.16, 1_200_000.0, 0.529, ],   // Micro   IG Story
    [5_000_000.0, 5_652_040.82, 11_304_081.63, 11_400_000.0, 0.504207], // Macro  IG Reels
    [3_000_000.0, 3_391_224.49, 6_782_448.98, 6_800_000.0, 0.501291],  // Micro   Visit Store
]);

it('qty ikut mengalikan subtotal seperti kolom W = T * V', function () {
    $item = bksItem(500_000, qty: 3);

    expect((float) $item->subtotal)->toEqualWithDelta(1_500_000, 0.01)
        ->and((float) $item->mu_pph)->toEqualWithDelta(1_695_612.24, 1);
});
