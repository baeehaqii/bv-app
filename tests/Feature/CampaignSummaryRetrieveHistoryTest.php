<?php

use App\Filament\Pages\CampaignSummaryList;
use App\Models\{BvCampaignKol, BvCampign, User};
use Illuminate\Support\Facades\Gate;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;

/**
 * Retrieve History memilih SATU postingan lewat dropdown. Satu KOL bisa punya
 * beberapa postingan di platform berbeda (Raden Rauf: TikTok + IG + YouTube),
 * jadi ketiganya harus bisa dipilih — bukan cuma yang pertama.
 */
/** @return BvCampign campaign berisi $jumlah postingan yang sudah tayang */
function campaignBanyakPostingan(int $jumlah): BvCampign
{
    Gate::before(fn () => true);
    Role::firstOrCreate(['name' => 'super_admin']);
    $u = User::firstOrCreate(
        ['email' => 'pager@bvnetwork.net'],
        ['name' => 'A', 'password' => bcrypt('x')],
    );
    $u->syncRoles(['super_admin']);
    test()->actingAs($u);

    $campaign = BvCampign::create(['campaign_name' => 'Ofero Leasing', 'campaign_type' => 'internal']);

    foreach (range(1, $jumlah) as $i) {
        BvCampaignKol::create([
            'campaign_id' => $campaign->id, 'creator_name' => "KOL {$i}",
            'platform' => 'tiktok', 'content_type' => 'video',
            'post_url' => "https://www.tiktok.com/@k{$i}/video/{$i}",
            'brief_status' => 'approved', 'status' => 'completed',
            // views menurun supaya urutannya bisa ditebak (orderByDesc('views')).
            'views' => 1_000 - $i,
        ])->recordSnapshot();
    }

    return $campaign;
}

function campaignTigaPlatform(): BvCampign
{
    Gate::before(fn () => true);
    Role::firstOrCreate(['name' => 'super_admin']);
    $u = User::create(['name' => 'A', 'email' => 'rh@bvnetwork.net', 'password' => bcrypt('x')]);
    $u->syncRoles(['super_admin']);
    test()->actingAs($u);

    $campaign = BvCampign::create(['campaign_name' => 'Ofero', 'campaign_type' => 'internal']);

    foreach ([
        ['youtube', 'https://www.youtube.com/shorts/a', 824_570],
        ['instagram', 'https://www.instagram.com/p/b', 605_189],
        ['tiktok', 'https://www.tiktok.com/@x/video/c', 194_629],
    ] as [$platform, $url, $views]) {
        BvCampaignKol::create([
            'campaign_id' => $campaign->id, 'creator_name' => 'Raden Rauf',
            'platform' => $platform, 'content_type' => 'video', 'post_url' => $url,
            'brief_status' => 'approved', 'status' => 'completed',
            'views' => $views, 'likes' => 10, 'price' => 1_000_000,
        ])->recordSnapshot();
    }

    return $campaign;
}

it('ketiga postingan tercatat riwayatnya, bukan cuma satu', function () {
    $campaign = campaignTigaPlatform();

    expect(BvCampaignKol::withCount('snapshots')->pluck('snapshots_count', 'platform')->all())
        ->toBe(['youtube' => 1, 'instagram' => 1, 'tiktok' => 1]);
});

it('menampilkan riwayat SEMUA postingan sekaligus, tanpa dropdown', function () {
    $campaign = campaignTigaPlatform();

    $lw = Livewire::test(CampaignSummaryList::class, ['campaignId' => $campaign->id]);

    // Ketiganya tampil bersamaan; dulu dua di antaranya tersembunyi di balik
    // dropdown sehingga terlihat seolah cuma YouTube yang punya riwayat.
    $lw->assertSee('Raden Rauf')
        ->assertSee('YouTube')
        ->assertSee('Instagram')
        ->assertSee('TikTok')
        ->assertSee('Riwayat seluruh 3 postingan')
        // Tidak ada lagi pemilih postingan.
        ->assertDontSeeHtml('wire:model.live="historyKolId"');

    // Angka tiap postingan muncul di tabelnya masing-masing.
    $lw->assertSee('824.570')->assertSee('605.189')->assertSee('194.629');
});

it('postingan yang belum pernah di-fetch tetap terlihat, dengan keterangannya', function () {
    $campaign = campaignTigaPlatform();

    BvCampaignKol::create([
        'campaign_id' => $campaign->id, 'creator_name' => 'Belum Fetch',
        'platform' => 'tiktok', 'content_type' => 'video',
        'post_url' => 'https://www.tiktok.com/@y/video/d',
        'brief_status' => 'approved', 'status' => 'completed', 'views' => 0,
    ]);

    Livewire::test(CampaignSummaryList::class, ['campaignId' => $campaign->id])
        ->assertSee('Belum Fetch')
        ->assertSee('Belum pernah di-fetch.')
        ->assertSee('Riwayat seluruh 4 postingan');
});

it('snapshot di-eager-load supaya tidak satu kueri per postingan', function () {
    $campaign = campaignTigaPlatform();

    $summary = new App\Service\CampaignSummary($campaign->fresh());

    expect($summary->kols->first()->relationLoaded('snapshots'))->toBeTrue();
});

it('Retrieve History dipotong 5 postingan per halaman', function () {
    $campaign = campaignBanyakPostingan(12);

    $lw = Livewire::test(CampaignSummaryList::class, ['campaignId' => $campaign->id])
        ->assertSet('historyPerPage', 5)
        ->assertSet('historyPage', 1);

    expect($lw->instance()->historyPageItems)->toHaveCount(5)
        ->and($lw->instance()->historyPages)->toBe(3);

    // Halaman 1 = 5 teratas menurut views. Dicek lewat isi blok riwayatnya,
    // bukan assertSee: tabel Content List di halaman yang sama juga memuat
    // nama-nama itu, jadi assertDontSee tidak membuktikan apa pun.
    expect($lw->instance()->historyPageItems->pluck('creator_name')->all())
        ->toBe(['KOL 1', 'KOL 2', 'KOL 3', 'KOL 4', 'KOL 5']);

    $lw->assertSee('Postingan 1–5 dari 12');

    $lw->set('historyPage', 3);

    expect($lw->instance()->historyPageItems->pluck('creator_name')->all())
        ->toBe(['KOL 11', 'KOL 12']);

    $lw->assertSee('Postingan 11–12 dari 12');
});

it('mengubah isi per halaman kembali ke halaman pertama', function () {
    $campaign = campaignBanyakPostingan(12);

    Livewire::test(CampaignSummaryList::class, ['campaignId' => $campaign->id])
        ->set('historyPage', 3)
        ->set('historyPerPage', 25)
        // Tanpa reset, halaman 3 dari 1 halaman = layar kosong.
        ->assertSet('historyPage', 1);
});

it('nomor halaman di luar rentang tidak membuat daftar kosong', function () {
    $campaign = campaignBanyakPostingan(7);

    $lw = Livewire::test(CampaignSummaryList::class, ['campaignId' => $campaign->id])
        ->set('historyPage', 99);

    // Dijepit ke halaman terakhir, bukan mengembalikan koleksi kosong.
    expect($lw->instance()->historyPageItems)->toHaveCount(2);
});

it('campaign dengan sedikit postingan tetap menampilkan semuanya', function () {
    $campaign = campaignTigaPlatform();

    $lw = Livewire::test(CampaignSummaryList::class, ['campaignId' => $campaign->id]);

    expect($lw->instance()->historyPages)->toBe(1)
        ->and($lw->instance()->historyPageItems)->toHaveCount(3);

    $lw->assertSee('YouTube')->assertSee('Instagram')->assertSee('TikTok');
});
