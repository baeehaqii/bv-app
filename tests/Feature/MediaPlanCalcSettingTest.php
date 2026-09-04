<?php

use App\Models\{InternalBudget, InternalBudgetItem, MasterPph, MediaPlan, MediaPlanCalcSetting, MediaPlanKol};

/**
 * Semua angka rumus Media Plan Internal harus bisa diubah dari master data.
 * Test ini menjaga supaya tak ada yang diam-diam balik jadi hardcode.
 */
beforeEach(function () {
    (new Database\Seeders\MasterPphSeeder)->run();
    (new Database\Seeders\MasterMarginSeeder)->run();
    (new Database\Seeders\MediaPlanCalcSettingSeeder)->run();
    MasterPph::forgetCachedDefault();
    MediaPlanCalcSetting::forgetCached();
});

function itemDenganRate(float $rate, ?int $pphId = null): InternalBudgetItem
{
    $plan = MediaPlan::create([
        'brand' => 'Bir Kawan Senja',
        'campaign_name' => 'BKS',
        'quotation_number' => 'Q-'.uniqid(),
    ]);

    return InternalBudget::create(['media_plan_id' => $plan->id])
        ->items()
        ->create([
            'scope_item' => 'IG Reels',
            'qty' => 1,
            'rate_base' => $rate,
            'master_pph_id' => $pphId ?? MasterPph::defaultId(),
            'sort_order' => 1,
        ])
        ->refresh();
}

it('tipe pajak default diambil dari kolom is_default, bukan nama di kode', function () {
    expect(MasterPph::defaultRow()->name)->toBe('PT PKP');

    MasterPph::where('name', 'Pribadi')->first()->update(['is_default' => true]);
    MasterPph::forgetCachedDefault();

    expect(MasterPph::defaultRow()->name)->toBe('Pribadi')
        // menandai satu default otomatis melepas yang lain
        ->and(MasterPph::where('is_default', true)->count())->toBe(1);
});

it('mengganti default PPh mengubah cost KOL baru', function () {
    expect((float) itemDenganRate(2_000_000)->mu_pph)->toEqualWithDelta(2_260_816.33, 1);

    MasterPph::where('name', 'Pribadi')->first()->update(['is_default' => true]);
    MasterPph::forgetCachedDefault();

    // Pribadi: 2jt / 0.975, tanpa PPN
    expect((float) itemDenganRate(2_000_000)->mu_pph)->toEqualWithDelta(2_051_282.05, 1);
});

it('kelipatan & arah pembulatan diambil dari master data', function () {
    // bawaan sheet: ke atas per 100rb
    expect((float) itemDenganRate(2_000_000)->rounded)->toBe(4_600_000.0);

    MediaPlanCalcSetting::current()->update(['rounding_step' => 500_000]);
    expect((float) itemDenganRate(2_000_000)->rounded)->toBe(5_000_000.0);

    MediaPlanCalcSetting::current()->update(['rounding_mode' => 'down']);
    expect((float) itemDenganRate(2_000_000)->rounded)->toBe(4_500_000.0);

    // 0 = pembulatan dimatikan
    MediaPlanCalcSetting::current()->update(['rounding_step' => 0]);
    expect((float) itemDenganRate(2_000_000)->rounded)->toEqualWithDelta(4_521_632.65, 1);
});

it('margin default diambil dari master data saat tak ada tingkatan yang cocok', function () {
    App\Models\MasterMargin::query()->delete();
    App\Models\MasterMargin::forgetCached();

    MediaPlanCalcSetting::current()->update(['default_margin_percent' => 60]);

    // cost 2.260.816 / (1 - 0.6) = 5.652.041 → dibulatkan ke atas per 100rb
    expect((float) itemDenganRate(2_000_000)->target_margin_percent)->toBe(60.0)
        ->and((float) itemDenganRate(2_000_000)->rounded)->toBe(5_700_000.0);
});

it('ambang tier diambil dari master data, termasuk band Celebrity sheet', function () {
    expect(MediaPlanKol::calculateTier(5_000_000))->toBe('Celebrity')
        ->and(MediaPlanKol::calculateTier(1_800_000))->toBe('Mega')
        ->and(MediaPlanKol::calculateTier(217_000))->toBe('Macro')
        ->and(MediaPlanKol::calculateTier(20_700))->toBe('Micro')
        ->and(MediaPlanKol::calculateTier(5_000))->toBe('Nano');

    MediaPlanCalcSetting::current()->update(['tier_thresholds' => [
        ['label' => 'Besar', 'min_followers' => 50_000],
        ['label' => 'Kecil', 'min_followers' => 0],
    ]]);

    expect(MediaPlanKol::calculateTier(217_000))->toBe('Besar')
        ->and(MediaPlanKol::calculateTier(20_700))->toBe('Kecil');
});

it('batas margin maksimum menjaga pembagi (1 - margin) tidak nol', function () {
    $calc = MediaPlanCalcSetting::current();

    expect($calc->applyMargin(1_000_000, 150))->toEqualWithDelta(100_000_000, 1) // di-clamp ke 99%
        ->and($calc->applyMargin(1_000_000, -10))->toEqualWithDelta(1_000_000, 1);
});

it('halaman Masterdata menyimpan setelan dan langsung dipakai perhitungan', function () {
    $admin = App\Models\User::create([
        'name' => 'Admin', 'email' => 'admin@bvnetwork.net', 'password' => bcrypt('x'),
    ]);
    Spatie\Permission\Models\Role::firstOrCreate(['name' => 'super_admin']);
    $admin->syncRoles(['super_admin']);
    $this->actingAs($admin);
    Illuminate\Support\Facades\Gate::before(fn() => true);

    Livewire\Livewire::test(App\Filament\Pages\MasterdataMediaPlanInternal::class)
        ->assertFormSet(['rounding_step' => 100000.0, 'rounding_mode' => 'up'])
        ->fillForm([
            'rounding_step' => 250_000,
            'rounding_mode' => 'up',
        ])
        ->call('simpan')
        ->assertHasNoFormErrors();

    MediaPlanCalcSetting::forgetCached();

    expect((float) MediaPlanCalcSetting::current()->rounding_step)->toBe(250000.0)
        // 4.521.632 dibulatkan ke atas per 250rb
        ->and((float) itemDenganRate(2_000_000)->rounded)->toBe(4_750_000.0);
});

it('DataKol, Media Plan Internal, dan semua service scraping memakai tier yang sama', function () {
    $services = [
        new App\Service\InstagramService,
        new App\Service\TiktokService,
        new App\Service\YoutubeShortsService,
        new App\Service\YoutubeChannelsService,
        new App\Service\ThreadsService,
    ];

    $tierService = function (object $svc, int $n): string {
        $m = new ReflectionMethod($svc, 'calculateTier');
        $m->setAccessible(true);

        return $m->invoke($svc, $n);
    };

    foreach ([500, 5_000, 50_000, 500_000, 2_000_000, 9_000_000] as $followers) {
        $harapan = MediaPlanCalcSetting::current()->tierFor($followers);

        expect(MediaPlanKol::calculateTier($followers))->toBe($harapan)
            ->and(App\Models\DataKol::tierFor($followers))->toBe($harapan);

        foreach ($services as $svc) {
            expect($tierService($svc, $followers))
                ->toBe($harapan, class_basename($svc)." beda tier di {$followers} follower");
        }
    }
});

it('mengubah tier di master data ikut mengubah DataKol dan service scraping', function () {
    MediaPlanCalcSetting::current()->update(['tier_thresholds' => [
        ['label' => 'Papan Atas', 'min_followers' => 100_000],
        ['label' => 'Papan Bawah', 'min_followers' => 0],
    ]]);

    $ig = new ReflectionMethod(App\Service\InstagramService::class, 'calculateTier');
    $ig->setAccessible(true);

    expect(App\Models\DataKol::tierFor(200_000))->toBe('Papan Atas')
        ->and(MediaPlanKol::calculateTier(200_000))->toBe('Papan Atas')
        ->and($ig->invoke(new App\Service\InstagramService, 200_000))->toBe('Papan Atas')
        ->and(App\Models\DataKol::tierFor(50))->toBe('Papan Bawah');
});

it('batas atas tiap band diturunkan dari ambang band di atasnya', function () {
    expect(MediaPlanCalcSetting::current()->tierRanges())->toBe([
        'Celebrity' => [4_000_000, null],
        'Mega' => [1_000_000, 3_999_999],
        'Macro' => [100_000, 999_999],
        'Micro' => [10_000, 99_999],
        'Nano' => [1_000, 9_999],
        'Mini' => [0, 999],
    ]);
});
