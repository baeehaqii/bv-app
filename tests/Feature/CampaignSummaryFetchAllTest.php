<?php

use App\Filament\Pages\CampaignSummaryList;
use App\Models\{BvCampaignKol, BvCampign, User};
use Illuminate\Support\Facades\{Gate, Http};
use Livewire\Livewire;
use Spatie\Permission\Models\Role;

/**
 * Fetch All diproses BERTAHAP. Satu postingan butuh ~4,7 detik (API 2,8-5,1 dtk
 * + jeda), jadi 31 postingan sekali jalan ≈ 2,5 menit dalam satu request —
 * melewati max_execution_time PHP maupun timeout nginx, dan request mati di
 * tengah: sebagian KOL ter-update, sisanya tidak, tanpa keterangan.
 */
function campaignSiapFetch(int $jumlah): BvCampign
{
    Gate::before(fn () => true);
    Role::firstOrCreate(['name' => 'super_admin']);
    $u = User::create(['name' => 'A', 'email' => 'fa@bvnetwork.net', 'password' => bcrypt('x')]);
    $u->syncRoles(['super_admin']);
    test()->actingAs($u);

    $campaign = BvCampign::create(['campaign_name' => 'Ofero Leasing', 'campaign_type' => 'internal']);

    foreach (range(1, $jumlah) as $i) {
        BvCampaignKol::create([
            'campaign_id' => $campaign->id, 'creator_name' => "KOL {$i}",
            'platform' => 'tiktok', 'content_type' => 'video',
            'post_url' => "https://www.tiktok.com/@k{$i}/video/{$i}",
            'brief_status' => 'approved', 'status' => 'completed', 'views' => 1000 - $i,
        ]);
    }

    return $campaign;
}

function fakeTiktokSukses(): void
{
    Http::fake(['api.scrapecreators.com/v2/tiktok/video*' => Http::response([
        'aweme_detail' => [
            'statistics' => ['play_count' => 5000, 'digg_count' => 100, 'comment_count' => 10,
                'share_count' => 5, 'collect_count' => 3],
            'author' => ['unique_id' => 'k', 'follower_count' => 20_000],
        ],
    ])]);
}

it('memotong antrean, tidak menarik semuanya dalam satu request', function () {
    fakeTiktokSukses();
    $campaign = campaignSiapFetch(7);

    $lw = Livewire::test(CampaignSummaryList::class, ['campaignId' => $campaign->id])
        ->call('startFetchAll')
        ->assertSet('fetching', true)
        ->assertSet('fetchTotal', 7)
        ->assertDispatched(CampaignSummaryList::FETCH_EVENT);

    // Satu potongan = 3 postingan; belum selesai.
    expect($lw->call('fetchChunk')->get('fetchProcessed'))->toBe(3);
    expect($lw->instance()->fetchFinished)->toBeFalse();

    expect($lw->call('fetchChunk')->get('fetchProcessed'))->toBe(6);

    // Potongan terakhir menyisakan 1 dan menutup prosesnya.
    $lw->call('fetchChunk')
        ->assertSet('fetchProcessed', 7)
        ->assertSet('fetchSuccess', 7)
        ->assertSet('fetching', false)
        ->assertSet('fetchFinished', true);

    expect((int) $campaign->kols()->sum('views'))->toBe(7 * 5000);
});

it('fetchChunk mengembalikan true hanya saat antrean habis', function () {
    fakeTiktokSukses();
    $campaign = campaignSiapFetch(4);

    $lw = Livewire::test(CampaignSummaryList::class, ['campaignId' => $campaign->id])->call('startFetchAll');

    // Nilai baliknya yang menghentikan perulangan di sisi klien.
    expect($lw->instance()->fetchChunk())->toBeFalse()
        ->and($lw->instance()->fetchChunk())->toBeTrue();
});

it('satu postingan gagal tidak menjatuhkan sisanya', function () {
    $campaign = campaignSiapFetch(3);
    $rusak = $campaign->kols()->orderBy('id')->first();
    $rusak->update(['post_url' => 'https://situs-tak-dikenal.test/p/1']);

    fakeTiktokSukses();

    $lw = Livewire::test(CampaignSummaryList::class, ['campaignId' => $campaign->id])
        ->call('startFetchAll')
        ->call('fetchChunk');

    expect($lw->get('fetchProcessed'))->toBe(3)
        ->and($lw->get('fetchSuccess'))->toBe(2)
        ->and($lw->get('fetchFailed'))->toBe(1)
        ->and($lw->get('fetchErrors'))->toHaveCount(1);

    expect($lw->get('fetchErrors')[0])->toContain($rusak->creator_name);
});

it('postingan yang dihapus saat antre tidak menggantungkan progres', function () {
    fakeTiktokSukses();
    $campaign = campaignSiapFetch(3);

    $lw = Livewire::test(CampaignSummaryList::class, ['campaignId' => $campaign->id])->call('startFetchAll');

    $campaign->kols()->orderBy('id')->first()->delete();

    $lw->call('fetchChunk')
        // Tetap dihitung selesai — kalau tidak, progresnya berhenti di 2/3 selamanya.
        ->assertSet('fetchProcessed', 3)
        ->assertSet('fetchFinished', true);
});

it('tanpa postingan tayang, tidak memulai apa pun', function () {
    Gate::before(fn () => true);
    Role::firstOrCreate(['name' => 'super_admin']);
    $u = User::create(['name' => 'A', 'email' => 'kosong@bvnetwork.net', 'password' => bcrypt('x')]);
    $u->syncRoles(['super_admin']);
    $this->actingAs($u);

    $campaign = BvCampign::create(['campaign_name' => 'Kosong', 'campaign_type' => 'internal']);

    Livewire::test(CampaignSummaryList::class, ['campaignId' => $campaign->id])
        ->call('startFetchAll')
        ->assertSet('fetching', false)
        ->assertNotDispatched(CampaignSummaryList::FETCH_EVENT);
});

it('tab KOL Performance memakai jalur bertahap yang sama', function () {
    fakeTiktokSukses();
    $campaign = campaignSiapFetch(5);

    // Satu baris tanpa link — tidak ikut antre, tapi juga jangan bikin gagal.
    BvCampaignKol::create([
        'campaign_id' => $campaign->id, 'creator_name' => 'Tanpa Link',
        'platform' => 'tiktok', 'content_type' => 'video', 'post_url' => null,
        'brief_status' => 'approved', 'status' => 'pending',
    ]);

    $rm = App\Filament\Resources\BvCampigns\RelationManagers\KolsRelationManager::class;

    $lw = Livewire::test($rm, [
        'ownerRecord' => $campaign,
        'pageClass' => App\Filament\Resources\BvCampigns\Pages\EditBvCampign::class,
    ])
        ->call('startFetchAll')
        ->assertSet('fetching', true)
        // 5 yang punya link; yang tanpa link tidak ikut.
        ->assertSet('fetchTotal', 5)
        ->assertDispatched($rm::FETCH_EVENT);

    $lw->call('fetchChunk')->assertSet('fetchProcessed', 3);

    $lw->call('fetchChunk')
        ->assertSet('fetchProcessed', 5)
        ->assertSet('fetchSuccess', 5)
        ->assertSet('fetchFinished', true);
});

it('kedua komponen berbagi ukuran potongan yang sama', function () {
    expect(CampaignSummaryList::FETCH_CHUNK)
        ->toBe(App\Filament\Resources\BvCampigns\RelationManagers\KolsRelationManager::FETCH_CHUNK)
        ->toBe(3);
});
