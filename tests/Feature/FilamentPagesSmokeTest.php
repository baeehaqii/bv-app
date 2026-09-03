<?php

use App\Models\BvCampign;
use App\Models\MediaPlan;
use App\Models\User;
use App\Support\MotuScenarioData as Motu;
use Database\Seeders\SonyPicturesScenarioSeeder;
use Illuminate\Support\Facades\Gate;
use Spatie\Permission\Models\Role;

/**
 * Smoke test RENDER halaman Filament panel "office".
 *
 * Menangkap error saat halaman dirender (mis. namespace Filament v5 salah, cast tanggal,
 * closure form yang melempar) — kelas bug yang LOLOS dari test model/alur karena test itu
 * tidak me-render UI. Memakai data skenario Sony Pictures end-to-end.
 */

/** Super admin (Shield intercept_gate=before) + email domain yang diizinkan canAccessPanel(). */
function officeSuperAdmin(): User
{
    Role::firstOrCreate(['name' => 'super_admin']);

    $user = User::create([
        'name' => 'Smoke Admin',
        'email' => 'smoke-admin@bvnetwork.net',
        'password' => bcrypt('password'),
    ]);
    $user->syncRoles(['super_admin']);

    return $user;
}

beforeEach(function () {
    $this->seed(SonyPicturesScenarioSeeder::class);
    $this->actingAs(officeSuperAdmin());

    // Smoke test ini fokus menangkap error RENDER (PHP) halaman, bukan otorisasi.
    // Bypass gate agar isolasi: authz Shield diuji terpisah.
    Gate::before(fn () => true);

    $this->mediaPlan = MediaPlan::where('campaign_name', Motu::CAMPAIGN_NAME)->firstOrFail();
    $this->budget = $this->mediaPlan->internalBudget;
    $this->campaign = BvCampign::where('campaign_name', Motu::CAMPAIGN_NAME)
        ->where('bv_sales_id', $this->mediaPlan->bv_sales_id)
        ->firstOrFail();
});

it('halaman List tiap modul ter-render', function () {
    $this->get('/office/media-plan-internal')->assertSuccessful();
    $this->get('/office/media-plan-external')->assertSuccessful();
    $this->get('/office/campaign-ongoing-internal')->assertSuccessful();
    $this->get('/office/target-finance')->assertSuccessful();
    $this->get('/office/sales-target-matrix')->assertSuccessful();
    $this->get('/office/target-finance/create')->assertSuccessful();
});

it('Edit Media Plan Internal ter-render (regression: BvSales date cast)', function () {
    $this->get("/office/media-plan-internal/{$this->mediaPlan->id}/edit")->assertSuccessful();
});

it('Edit Media Plan External ter-render (regression: namespace Filament\\Actions\\Action)', function () {
    // status review_client memunculkan aksi "Link Review Client" dgn suffixAction.
    $this->budget->update(['status' => 'review_client']);
    $this->get("/office/media-plan-external/{$this->budget->id}/edit")->assertSuccessful();
});

it('Edit Campaign Ongoing Internal ter-render', function () {
    $this->get("/office/campaign-ongoing-internal/{$this->campaign->id}/edit")->assertSuccessful();
});

it('List & View Campaign Ongoing External ter-render (Tracker RelationManager)', function () {
    $this->get('/office/campaign-ongoing-external')->assertSuccessful();
    $this->get("/office/campaign-ongoing-external/{$this->campaign->id}")->assertSuccessful();
});

it('menu KOL Area ter-render: KOL Data, KOL Analyzer, KOL SPK', function () {
    $this->get('/office/data-kol')->assertSuccessful();
    $this->get('/office/kol-analyzer')->assertSuccessful()->assertSee('Klik satu baris');
    $this->get('/office/spk')->assertSuccessful();
});
