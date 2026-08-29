<?php

use App\Filament\Resources\MediaPlans\Schemas\MediaPlanForm;
use App\Models\DataKol;
use App\Models\MasterSow;

/**
 * Satu KOL bisa punya beberapa baris DataKol — 1 baris per channel, username sama.
 * Channel-lah yang menentukan baris (dan rate card) mana yang dipakai di Media Plan.
 * Dulu lookup-nya `where('username', …)->first()` sehingga channel yang lain
 * tidak pernah terpilih dan Rate per SOW-nya jadi 0.
 */
function kolDuaChannel(): array
{
    $ig = DataKol::create([
        'channel' => 'Instagram', 'username' => 'sahih',
        'link_userprofile' => 'https://instagram.com/sahih',
    ]);
    $tt = DataKol::create([
        'channel' => 'Tiktok', 'username' => 'sahih',
        'link_userprofile' => 'https://tiktok.com/@sahih',
    ]);

    $sowIg = MasterSow::create([
        'name' => 'IG Reels', 'channel' => 'Instagram',
        'is_custom' => false, 'is_active' => true, 'sort_order' => 1,
    ]);
    $sowTt = MasterSow::create([
        'name' => 'TikTok Video', 'channel' => 'Tiktok',
        'is_custom' => false, 'is_active' => true, 'sort_order' => 2,
    ]);

    $ig->rateCards()->create([
        'channel' => 'Instagram', 'master_sow_id' => $sowIg->id,
        'rate' => 3_000_000, 'valid_from' => now()->toDateString(),
    ]);
    $tt->rateCards()->create([
        'channel' => 'Tiktok', 'master_sow_id' => $sowTt->id,
        'rate' => 7_000_000, 'valid_from' => now()->toDateString(),
    ]);

    return [$ig, $tt];
}

it('rate diambil dari channel yang dipilih, bukan channel pertama', function () {
    kolDuaChannel();

    // data_kol_id sengaja null: inilah jalur yang dulu salah ambil baris.
    expect(MediaPlanForm::computeRateFromSow(null, 'sahih', 'Tiktok', ['TikTok Video']))
        ->toBe(7_000_000.0)
        ->and(MediaPlanForm::computeRateFromSow(null, 'sahih', 'Instagram', ['IG Reels']))
        ->toBe(3_000_000.0);
});

it('channel dicocokkan tanpa peduli besar-kecil huruf', function () {
    kolDuaChannel();

    expect(MediaPlanForm::computeRateFromSow(null, 'sahih', 'TIKTOK', ['TikTok Video']))
        ->toBe(7_000_000.0);
});

it('SOW milik channel lain tidak ikut terhitung', function () {
    kolDuaChannel();

    // Rate card IG tidak boleh bocor ke baris TikTok.
    expect(MediaPlanForm::computeRateFromSow(null, 'sahih', 'Tiktok', ['IG Reels']))->toBe(0.0);
});

it('KOL tanpa rate card menghasilkan rate 0 — dasar blokir simpan Media Plan', function () {
    DataKol::create([
        'channel' => 'Instagram', 'username' => 'belum-ada-rate',
        'link_userprofile' => 'https://instagram.com/belum-ada-rate',
    ]);

    expect(MediaPlanForm::computeRateFromSow(null, 'belum-ada-rate', 'Instagram', ['IG Reels']))
        ->toBe(0.0);
});

/**
 * R4: Media Plan tidak boleh tersimpan bila ada KOL yang SOW-nya sudah dipilih
 * tapi rate card-nya belum ada — budget yang tergenerate akan diam-diam nol.
 */
it('menolak simpan Media Plan saat rate card KOL belum diisi', function () {
    \Spatie\Permission\Models\Role::firstOrCreate(['name' => 'super_admin']);
    $user = \App\Models\User::create([
        'name' => 'MP Admin',
        'email' => 'guard-admin@bvnetwork.net',
        'password' => bcrypt('password'),
    ]);
    $user->syncRoles(['super_admin']);
    $this->actingAs($user);
    \Illuminate\Support\Facades\Gate::before(fn() => true);

    $this->seed(\Database\Seeders\SonyPicturesScenarioSeeder::class);
    $mediaPlan = \App\Models\MediaPlan::where(
        'campaign_name',
        \App\Support\MotuScenarioData::CAMPAIGN_NAME
    )->firstOrFail();

    DataKol::create([
        'channel' => 'Instagram', 'username' => 'tanpa-ratecard',
        'link_userprofile' => 'https://instagram.com/tanpa-ratecard',
    ]);

    // Bersihkan KOL bawaan seeder — status-nya di luar enum dan bikin validasi
    // form gagal duluan, jadi guard-nya tidak pernah kebagian jalan.
    $mediaPlan->kols()->each(function ($kol) {
        $kol->internalBudgetItems()->delete();
        $kol->delete();
    });
    $mediaPlan->bvSales->formBrief->update(['sow' => '1x IG Reels']);

    $page = \Livewire\Livewire::test(
        \App\Filament\Resources\MediaPlans\Pages\EditMediaPlan::class,
        ['record' => $mediaPlan->getRouteKey()]
    );

    $kols = $page->get('data')['kols'];
    $key = array_key_first($kols);
    $kols[$key]['name'] = 'tanpa-ratecard';
    $kols[$key]['channel'] = 'Instagram';
    $kols[$key]['data_kol_id'] = null;
    $kols[$key]['scope_items'] = ['IG Reels'];

    // Guard menghentikan save dan menyebut KOL mana yang bermasalah.
    $page->set('data.kols', $kols)
        ->call('save')
        ->assertHasNoFormErrors()
        ->assertNotified('Rate card KOL belum diisi');

    expect(\App\Models\MediaPlanKol::where('name', 'tanpa-ratecard')->exists())->toBeFalse();
});
