<?php

use App\Models\BvCampign;
use App\Models\DataKol;
use App\Models\MasterSow;
use App\Models\MediaPlan;
use App\Support\MotuScenarioData as Motu;
use Database\Seeders\SonyPicturesScenarioSeeder;

/**
 * KOL sudah di-ACC client tapi ternyata tidak available / client minta ganti orang.
 * Item lama disimpan sebagai rejected (jejak ACC client), pengganti dibuat pending,
 * review client dibuka lagi, dan campaign dibangun ulang dari item approved.
 */
beforeEach(function () {
    $this->seed(SonyPicturesScenarioSeeder::class);
    $this->mediaPlan = MediaPlan::where('campaign_name', Motu::CAMPAIGN_NAME)->firstOrFail();
    $this->budget = $this->mediaPlan->internalBudget;
});

it('mengganti KOL yang sudah disetujui client tanpa menghapus jejaknya', function () {
    $budget = $this->budget;
    $item = $budget->items()->where('status', 'approved')->with('mediaPlanKol')->firstOrFail();
    $oldKol = $item->mediaPlanKol;
    $scope = $item->scope_item;

    // Client sudah submit review sebelumnya.
    $budget->forceFill(['review_is_public' => true, 'review_submitted_at' => now()])->saveQuietly();

    // KOL pengganti + rate card untuk SOW yang sama.
    $pengganti = DataKol::create([
        'channel' => $oldKol->channel,
        'username' => 'kolpengganti',
        'link_userprofile' => 'https://instagram.com/kolpengganti',
        'followers' => 500000,
        'tier' => 'Macro',
        'engagement_rate' => 4.5,
        'impressions' => 90000,
        'engagements' => 22000,
    ]);
    $sow = MasterSow::firstOrCreate(
        ['name' => $scope, 'channel' => $oldKol->channel],
        ['is_custom' => false, 'is_active' => true, 'sort_order' => 99],
    );
    $pengganti->rateCards()->create([
        'channel' => $oldKol->channel,
        'master_sow_id' => $sow->id,
        'rate' => 7_000_000,
        'valid_from' => now()->toDateString(),
    ]);

    $newItem = $budget->replaceItemKol($item, $pengganti->id, 'KOL tidak available');

    // Item lama: jejak tetap ada, sudah tidak aktif.
    expect($item->refresh()->status)->toBe('rejected')
        ->and($item->client_choice)->toBe('rejected')
        ->and($item->rejection_notes)->toContain('kolpengganti')
        ->and($oldKol->refresh()->status)->toBe('Unavail');

    // Item pengganti: SOW & qty sama, rate dari rate card KOL baru, menunggu ACC client.
    expect($newItem->scope_item)->toBe($scope)
        ->and((int) $newItem->qty)->toBe(max(1, (int) $item->qty))
        ->and((float) $newItem->rate_base)->toBe(7_000_000.0)
        ->and($newItem->status)->toBe('pending')
        ->and($newItem->mediaPlanKol->name)->toBe('kolpengganti')
        ->and($newItem->mediaPlanKol->data_kol_id)->toBe($pengganti->id);

    // Review client dibuka lagi supaya client bisa meng-ACC pengganti.
    expect($budget->refresh()->review_submitted_at)->toBeNull();

    // Campaign yang sudah jalan: KOL lama keluar dari SOW itu, pengganti masuk setelah di-approve.
    $campaign = BvCampign::where('bv_sales_id', $this->mediaPlan->bv_sales_id)->first();
    expect($campaign)->not->toBeNull();
    expect($campaign->storylines()->where('kol_name', $oldKol->name)->where('sow', $scope)->where('status', 'draft')->count())->toBe(0)
        ->and($campaign->kols()->where('creator_name', 'kolpengganti')->count())->toBe(0);

    $newItem->approve();
    $budget->refresh()->syncCampaignKolsFromApprovedBudget();
    expect($campaign->kols()->where('creator_name', 'kolpengganti')->count())->toBe(1);
});

it('aksi "Ganti KOL" di halaman Media Plan External memanggil replaceItemKol', function () {
    \Spatie\Permission\Models\Role::firstOrCreate(['name' => 'super_admin']);
    $user = \App\Models\User::create([
        'name' => 'Ext Admin',
        'email' => 'ext-admin@bvnetwork.net',
        'password' => bcrypt('password'),
    ]);
    $user->syncRoles(['super_admin']);
    $this->actingAs($user);
    \Illuminate\Support\Facades\Gate::before(fn() => true);

    $budget = $this->budget;
    $item = $budget->items()->where('status', 'approved')->with('mediaPlanKol')->firstOrFail();

    $pengganti = DataKol::create([
        'channel' => $item->mediaPlanKol->channel,
        'username' => 'kolpengganti2',
        'link_userprofile' => 'https://instagram.com/kolpengganti2',
        'followers' => 300000,
    ]);

    $page = \Livewire\Livewire::test(
        \App\Filament\Resources\InternalBudgets\Pages\EditInternalBudget::class,
        ['record' => $budget->getRouteKey()],
    );

    // uuid item repeater = key array state 'items'
    $uuid = array_key_first(collect($page->get('data')['items'])->filter(fn($row) => (int) ($row['id'] ?? 0) === $item->id)->all());

    $page->callFormComponentAction('items', 'replace_kol', data: [
        'data_kol_id' => $pengganti->id,
        'reason' => 'KOL tidak available',
    ], arguments: ['item' => $uuid])->assertHasNoFormErrors();

    expect($item->refresh()->status)->toBe('rejected')
        ->and($budget->items()->where('scope_item', $item->scope_item)->where('status', 'pending')->count())->toBe(1)
        ->and($budget->mediaPlan->kols()->where('name', 'kolpengganti2')->exists())->toBeTrue();
});
