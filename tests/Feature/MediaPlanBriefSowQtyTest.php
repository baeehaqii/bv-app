<?php

use App\Filament\Resources\MediaPlans\Pages\EditMediaPlan;
use App\Filament\Resources\MediaPlans\Schemas\MediaPlanForm;
use App\Models\BvCampign;
use App\Models\MediaPlan;
use App\Models\User;
use App\Support\MotuScenarioData as Motu;
use Database\Seeders\SonyPicturesScenarioSeeder;
use Illuminate\Support\Facades\Gate;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;

/**
 * Revisi: KOL List auto-generate 1 baris per SOW brief (KOL dipilih manual),
 * + kolom Qty yang mengalikan rate base SOW (5x IG Reels @1jt = 5jt).
 */
it('mem-parse SOW brief jadi item + qty', function () {
    expect(MediaPlanForm::parseBriefSow('1x IG Reels 1x TikTok Video 1x Visit'))->toBe([
        ['sow' => 'IG Reels', 'qty' => 1],
        ['sow' => 'TikTok Video', 'qty' => 1],
        ['sow' => 'Visit', 'qty' => 1],
    ]);

    expect(MediaPlanForm::parseBriefSow("<p>- 5x IG Reels</p><p>- IG Story (3x)</p><p>- Visit</p>"))->toBe([
        ['sow' => 'IG Reels', 'qty' => 5],
        ['sow' => 'IG Story', 'qty' => 3],
        ['sow' => 'Visit', 'qty' => 1],
    ]);

    expect(MediaPlanForm::parseBriefSow(null))->toBe([]);
});

it('mengalikan rate SOW dengan qty', function () {
    $kol = \App\Models\DataKol::create([
        'channel' => 'Instagram',
        'username' => 'budi',
        'link_userprofile' => 'https://instagram.com/budi',
    ]);
    $sow = \App\Models\MasterSow::create([
        'name' => 'IG Reels', 'channel' => 'Instagram',
        'is_custom' => false, 'is_active' => true, 'sort_order' => 1,
    ]);
    $kol->rateCards()->create([
        'channel' => 'Instagram',
        'master_sow_id' => $sow->id,
        'rate' => 1_000_000,
        'valid_from' => now()->toDateString(),
    ]);

    $labels = $kol->rateCards()->with('masterSow')->get()->pluck('sow_label')->all();

    expect(MediaPlanForm::computeRateFromSow($kol->id, 'budi', 'Instagram', $labels))->toBe(1_000_000.0)
        ->and(MediaPlanForm::computeRateFromSow($kol->id, 'budi', 'Instagram', $labels, 5))->toBe(5_000_000.0);
});

it('auto-generate baris KOL List dari SOW brief dan menyimpan qty ke budget item', function () {
    Role::firstOrCreate(['name' => 'super_admin']);
    $user = User::create([
        'name' => 'MP Admin',
        'email' => 'mp-admin@bvnetwork.net',
        'password' => bcrypt('password'),
    ]);
    $user->syncRoles(['super_admin']);
    $this->actingAs($user);
    Gate::before(fn() => true);

    $this->seed(SonyPicturesScenarioSeeder::class);
    $mediaPlan = MediaPlan::where('campaign_name', Motu::CAMPAIGN_NAME)->firstOrFail();

    // Kosongkan KOL List + set SOW brief supaya jalur auto-generate yang diuji.
    $mediaPlan->kols()->each(function ($kol) {
        $kol->internalBudgetItems()->delete();
        $kol->delete();
    });
    $mediaPlan->bvSales->formBrief->update(['sow' => '2x IG Reels 1x TikTok Video']);

    $page = Livewire::test(EditMediaPlan::class, ['record' => $mediaPlan->getRouteKey()]);

    $kols = $page->get('data')['kols'];
    expect($kols)->toHaveCount(2);

    $rows = array_values($kols);
    expect($rows[0]['scope_items'])->toBe(['IG Reels'])
        ->and((int) $rows[0]['qty'])->toBe(2)
        ->and($rows[1]['scope_items'])->toBe(['TikTok Video'])
        ->and((int) $rows[1]['qty'])->toBe(1);

    // Simpan: baris auto tersimpan + qty ikut ke budget item.
    $page->call('save')->assertHasNoFormErrors();

    $saved = $mediaPlan->kols()->orderBy('row_number')->get();
    expect($saved)->toHaveCount(2)
        ->and($saved[0]->qty)->toBe(2)
        ->and($saved[0]->scope_items)->toBe(['IG Reels']);

    $items = $mediaPlan->internalBudget->items()->orderBy('sort_order')->get();
    expect($items->firstWhere('scope_item', 'IG Reels')->qty)->toBe(2)
        ->and($items->firstWhere('scope_item', 'TikTok Video')->qty)->toBe(1);
});
