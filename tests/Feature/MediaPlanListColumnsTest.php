<?php

use App\Filament\Resources\MediaPlans\Pages\ListMediaPlans;
use App\Models\InternalBudgetItem;
use App\Models\MediaPlan;
use App\Models\User;
use App\Support\MotuScenarioData as Motu;
use Database\Seeders\SonyPicturesScenarioSeeder;
use Illuminate\Support\Facades\Gate;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;

/**
 * Kolom daftar Media Plan Internal.
 *
 * Kolom "Selected" dulu menghitung is_selected — centang shortlist internal, bukan
 * keputusan client — jadi selalu 0 walau di Media Plan External KOL-nya sudah
 * di-approve. Yang dihitung sekarang item budget berstatus approved.
 */
beforeEach(function () {
    Role::firstOrCreate(['name' => 'super_admin']);

    $admin = User::create([
        'name' => 'MP Admin',
        'email' => 'mp-admin@bvnetwork.net',
        'password' => bcrypt('password'),
    ]);
    $admin->syncRoles(['super_admin']);
    $this->actingAs($admin);
    Gate::before(fn() => true);

    $this->seed(SonyPicturesScenarioSeeder::class);
    $this->mediaPlan = MediaPlan::where('campaign_name', Motu::CAMPAIGN_NAME)->firstOrFail();
});

it('kolom Approved menghitung KOL yang di-approve client, bukan centang shortlist', function () {
    $budget = $this->mediaPlan->internalBudget;

    // Dua SOW milik satu KOL + satu SOW milik KOL lain = 2 KOL approved.
    $items = $budget->items()->whereNotNull('media_plan_kol_id')->get();
    $kolIds = $items->pluck('media_plan_kol_id')->unique()->take(2)->values();

    expect($kolIds)->toHaveCount(2);

    // Skenario Sony sudah punya item approved sendiri — mulai dari bersih.
    $budget->items()->update(['status' => 'pending']);

    InternalBudgetItem::whereIn('media_plan_kol_id', $kolIds)
        ->where('internal_budget_id', $budget->id)
        ->update(['status' => 'approved']);

    // Shortlist internal sengaja dikosongkan: kolomnya tidak boleh ikut ini.
    $this->mediaPlan->kols()->update(['is_selected' => false]);

    Livewire::test(ListMediaPlans::class)
        ->assertSuccessful()
        ->assertTableColumnStateSet('approved_kols_count', 2, $this->mediaPlan);
});

it('menampilkan detail campaign menggantikan kolom Channel(s)', function () {
    Livewire::test(ListMediaPlans::class)
        ->assertSuccessful()
        ->assertTableColumnExists('picSalesBd.nama_sales')
        ->assertTableColumnExists('pic_project_internal_ids')
        ->assertTableColumnExists('picAm.nama_sales')
        ->assertTableColumnExists('picLeadsProject.nama_sales')
        ->assertTableColumnExists('quotations_count')
        ->assertTableColumnExists('campaign_period_start')
        ->assertTableColumnExists('campaign_period_end')
        ->assertTableColumnDoesNotExist('kols.channel');
});
