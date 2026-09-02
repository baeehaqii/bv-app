<?php

use App\Filament\Pages\KolAnalyzer;
use Filament\Actions\Testing\TestAction;
use App\Models\DataKol;
use App\Models\User;
use App\Service\KolPostNormalizer;
use Illuminate\Support\Facades\Gate;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;

/**
 * KOL Analyzer — analisis per channel dari data yang SUDAH tersimpan.
 *
 * Batasan sumber data yang ikut dikunci di sini (lihat docs.scrapecreators.com):
 * tidak ada histori followers (grafik growth dibangun dari snapshot sendiri),
 * dan demografi audiens hanya tersedia untuk TikTok, itu pun negara saja.
 */
function kolChannel(array $attributes = []): DataKol
{
    return DataKol::create(array_merge([
        'username' => 'raffinagita1717',
        'channel' => 'Instagram',
        'link_userprofile' => 'https://www.instagram.com/raffinagita1717/',
        'full_name' => 'Raffi Ahmad',
        'biography' => 'Entertainer. Kontak: mgmt@example.com',
        'profile_pic_url' => 'https://cdn.example.com/pp.jpg',
        'followers' => 2_000_000,
        'following_count' => 500,
        'media_count' => 4_200,
        'is_verified' => true,
        'engagement_rate' => 3.0,
        'engagements' => 90_000,
        'impressions' => 800_000,
        'average_likes' => 50_000,
        'average_comments' => 1_200,
        'average_views' => 600_000,
    ], $attributes));
}

function analyzerUser(): User
{
    Role::firstOrCreate(['name' => 'super_admin']);

    $user = User::create([
        'name' => 'Analyzer Admin',
        'email' => 'analyzer-' . uniqid() . '@bvnetwork.net',
        'password' => bcrypt('password'),
    ]);

    return tap($user)->syncRoles(['super_admin']);
}

it('Analyzer mulai dari daftar KOL, lalu klik baris untuk membuka analisis', function () {
    $raffi = kolChannel();
    kolChannel(['username' => 'awkarin', 'full_name' => 'Karin Novilda', 'followers' => 900_000]);

    $page = Livewire::actingAs(analyzerUser())->test(KolAnalyzer::class);

    // Mulai di daftar — bukan langsung ke detail salah satu KOL.
    $page->assertSet('channelId', null)
        ->assertSee('Klik satu baris')
        ->assertCanSeeTableRecords(DataKol::oneRowPerKol()->get());

    // Barisnya tautan, bukan tombol aksi: tombol bernama "Analyze" di sini pernah
    // tertukar dengan Analyze di halaman edit KOL yang men-scrape ulang.
    expect($page->instance()->getTable()->getRecordUrl($raffi))
        ->toContain('channelId=' . $raffi->id);

    // Membuka tautannya → detail analisis KOL itu.
    $page->set('channelId', $raffi->id)
        ->assertDontSee('Estimasi Rate Card')
        ->assertSee('Social Data');

    // Dan bisa balik ke daftar.
    $page->callAction('kembali')
        ->assertSet('channelId', null)
        ->assertSee('Klik satu baris');
});

it('VTR dihitung dari avg views terhadap followers', function () {
    expect(kolChannel()->viewThroughRate())->toBe(30.0);
    expect(kolChannel(['username' => 'tanpa-views', 'average_views' => 0])->viewThroughRate())->toBeNull();
});

it('snapshot mencatat 1 baris per tanggal, bukan menumpuk tiap refresh', function () {
    $kol = kolChannel();

    $kol->recordSnapshot();
    $kol->update(['followers' => 2_100_000]);
    $kol->recordSnapshot();

    expect($kol->snapshots()->count())->toBe(1)
        ->and((int) $kol->snapshots()->sole()->followers)->toBe(2_100_000);
});

it('grafik follower growth butuh minimal 2 tanggal', function () {
    $kol = kolChannel();
    $kol->recordSnapshot();

    Livewire::actingAs(analyzerUser())
        ->test(KolAnalyzer::class, ['channelId' => $kol->id])
        ->assertSee('Butuh minimal 2 tanggal');
});

it('top hashtag dihitung dari caption, satu kali per postingan', function () {
    $posts = [
        ['caption' => 'Seru banget #liburan #Bali #liburan'], // #liburan dobel dalam 1 post
        ['caption' => 'Pantai #bali #sunset'],
        ['caption' => 'Tanpa tagar'],
    ];

    expect(KolPostNormalizer::topHashtags($posts))
        ->toBe(['bali' => 2, 'liburan' => 1, 'sunset' => 1]);
});

it('normalizer menyamakan bentuk post dari tiap channel & membatasi 10', function () {
    $ig = KolPostNormalizer::normalize('Instagram', [[
        'shortcode' => 'ABC123',
        'thumbnail_src' => 'https://cdn/x.jpg',
        'caption' => 'halo #bv',
        'likes_count' => 10,
        'comments_count' => 2,
        'video_view_count' => 500,
        'is_video' => true,
        'taken_at_timestamp' => 1_760_000_000,
    ]]);

    expect($ig[0]['url'])->toBe('https://www.instagram.com/p/ABC123/')
        ->and($ig[0]['likes'])->toBe(10)
        ->and($ig[0]['views'])->toBe(500)
        ->and($ig[0]['is_video'])->toBeTrue()
        ->and($ig[0]['posted_at'])->not->toBeNull();

    // Threads berbasis teks: tidak ada views maupun thumbnail.
    $threads = KolPostNormalizer::normalize('Threads', [[
        'id' => 'p1', 'url' => 'https://threads.net/@a/post/1', 'caption' => 'hai', 'likes' => 5, 'comments' => 1,
    ]]);

    expect($threads[0]['views'])->toBe(0)
        ->and($threads[0]['thumbnail'])->toBeNull()
        ->and($threads[0]['is_video'])->toBeFalse();

    $banyak = KolPostNormalizer::normalize('Tiktok', array_fill(0, 25, ['id' => 'v', 'video_url' => 'https://tt/v']));
    expect($banyak)->toHaveCount(KolPostNormalizer::LIMIT);
});

it('caption TikTok dibaca dari desc dan URL-nya halaman post, bukan file mp4', function () {
    [$post] = KolPostNormalizer::normalize('Tiktok', [[
        'id' => '7412345',
        'desc' => 'wkwk #gaming #windah',                       // TikTok pakai `desc`, bukan `caption`
        'video_url' => 'https://v16.tiktokcdn.com/abc.mp4?exp=1', // play_addr: cepat kedaluwarsa
        'thumbnail_src' => 'https://cdn/tt.jpg',
        'likes_count' => 900, 'comments_count' => 12, 'video_view_count' => 40_000,
        'create_time' => 1_760_000_000,
    ]], 'windahbasudara');

    expect($post['url'])->toBe('https://www.tiktok.com/@windahbasudara/video/7412345')
        ->and($post['caption'])->toBe('wkwk #gaming #windah');

    // Konsekuensi caption yang hilang dulu: Top Hashtag TikTok selalu kosong.
    expect(KolPostNormalizer::topHashtags([$post]))->toBe(['gaming' => 1, 'windah' => 1]);
});

it('audience breakdown: TikTok bisa diambil, channel lain ditandai tidak tersedia', function () {
    Gate::before(fn() => true);

    $ig = kolChannel();
    Livewire::actingAs(analyzerUser())
        ->test(KolAnalyzer::class, ['channelId' => $ig->id])
        ->assertSee('hanya menyediakan')                       // Instagram → tidak tersedia
        ->assertActionHidden('fetch_audience');

    $tt = kolChannel(['username' => 'windah', 'channel' => 'Tiktok']);
    Livewire::actingAs(analyzerUser())
        ->test(KolAnalyzer::class, ['channelId' => $tt->id])
        ->assertActionVisible('fetch_audience');

    // Kota, umur, dan gender tidak ada di endpoint mana pun — jangan pernah dikarang.
    Livewire::actingAs(analyzerUser())
        ->test(KolAnalyzer::class, ['channelId' => $tt->id])
        ->assertSee('Tidak tersedia dari sumber data');
});

it('tab 10 postingan terakhir memakai rata-rata postingan itu saja', function () {
    $kol = kolChannel([
        'followers' => 1000,
        'average_likes' => 999_999, // angka keseluruhan sengaja beda jauh
        'latest_posts' => [
            ['id' => '1', 'url' => 'u', 'thumbnail' => null, 'caption' => '#a', 'likes' => 100, 'comments' => 10, 'views' => 2000, 'is_video' => true, 'posted_at' => null],
            ['id' => '2', 'url' => 'u', 'thumbnail' => null, 'caption' => '', 'likes' => 200, 'comments' => 20, 'views' => 0, 'is_video' => false, 'posted_at' => null],
        ],
    ]);

    $page = new KolAnalyzer;
    $page->channelId = $kol->id;
    $stats = $page->getLatestStatsProperty();

    expect($stats['likes'])->toBe(150)
        ->and($stats['comments'])->toBe(15)
        ->and($stats['views'])->toBe(1000)
        ->and($stats['vtr'])->toBe(100.0)
        ->and($stats['videos'])->toBe(1)
        ->and($stats['photos'])->toBe(1);
});

it('halaman ter-render penuh saat data lengkap (grafik growth + grid postingan)', function () {
    $kol = kolChannel([
        'latest_posts' => [
            ['id' => '1', 'url' => 'https://ig/p/1', 'thumbnail' => 'https://cdn/1.jpg', 'caption' => 'liburan #bali',
             'likes' => 100, 'comments' => 10, 'views' => 2000, 'is_video' => true, 'posted_at' => '2026-07-30 10:00:00'],
        ],
        'audience_countries' => [
            ['country' => 'Indonesia', 'countryCode' => 'ID', 'count' => 900, 'percentage' => 90.0],
        ],
        'audience_fetched_at' => now(),
    ]);

    // Dua tanggal → grafik growth benar-benar digambar.
    $kol->recordSnapshot();
    $kol->snapshots()->create(['captured_on' => now()->subDays(7)->toDateString(), 'followers' => 1_900_000]);

    Livewire::actingAs(analyzerUser())
        ->test(KolAnalyzer::class, ['channelId' => $kol->id])
        ->assertSee('Follower Growth')
        ->assertSee('kolz-spark-bar', escape: false)
        ->assertSee('#bali')
        ->assertSee('Indonesia')
        ->assertDontSee('Butuh minimal 2 tanggal')
        ->set('tab', 'latest')
        ->assertSee('10 Postingan Terakhir')
        ->assertSee('Video');
});

it('meringkas angka gabungan seluruh channel milik satu KOL', function () {
    $ig = DataKol::create([
        'username' => 'gabungan', 'channel' => 'Instagram',
        'link_userprofile' => 'https://instagram.com/gabungan',
        'followers' => 40_000, 'engagements' => 2_000,
        'engagement_rate' => 5.0, 'average_views' => 10_000,
    ]);
    DataKol::create([
        'username' => 'gabungan', 'channel' => 'TikTok',
        'link_userprofile' => 'https://tiktok.com/@gabungan',
        'followers' => 80_000, 'engagements' => 6_000,
        'engagement_rate' => 7.0, 'average_views' => 30_000,
    ]);

    $gab = $ig->crossChannelSummary();

    expect($gab['channels'])->toBe(2)
        // Followers & engagements dijumlahkan; ER dan avg views dirata-rata —
        // aturan yang sama dengan kolom di KOL Data.
        ->and($gab['followers'])->toBe(120_000)
        ->and($gab['engagements'])->toBe(8_000)
        ->and($gab['engagement_rate'])->toBe(6.0)
        ->and($gab['average_views'])->toBe(20_000)
        // Tier ikut followers GABUNGAN, bukan followers channel yang dibuka.
        ->and($gab['tier'])->toBe('Macro');

    Livewire::actingAs(analyzerUser())
        ->test(KolAnalyzer::class, ['channelId' => $ig->id])
        ->assertSee('Total Followers')
        ->assertSee('ER Gabungan')
        // 120.000 gabungan, bukan 40.000 milik channel Instagram yang dibuka.
        ->assertSee('120.000')
        ->assertSee('2 channel · tier Macro', escape: false);
});

it('menulis kartu AI KOL lalu mengunduhnya sebagai PDF', function () {
    config(['ai.providers.gemini.key' => 'key-palsu']);
    \Laravel\Ai\Ai::fakeAgent(\Laravel\Ai\AnonymousAgent::class, [
        'Kreator gaming dengan audiens loyal. Cocok untuk brand gadget.',
    ]);

    $kol = kolChannel();

    Livewire::actingAs(analyzerUser())
        ->test(KolAnalyzer::class, ['channelId' => $kol->id])
        ->callAction('kartu_ai')
        ->assertHasNoActionErrors();

    $kol->refresh();
    expect($kol->ai_insight)->toContain('brand gadget')
        ->and($kol->ai_insight_at)->not->toBeNull();

    $response = $this->actingAs(analyzerUser())->get(route('kol-card.pdf', ['dataKol' => $kol->id]));

    $response->assertOk()->assertHeader('content-type', 'application/pdf');
    expect($response->getContent())->toStartWith('%PDF');
});

it('menolak unduh kartu PDF sebelum kartu AI pernah dibuat', function () {
    $kol = kolChannel();

    $this->actingAs(analyzerUser())
        ->get(route('kol-card.pdf', ['dataKol' => $kol->id]))
        ->assertNotFound();
});
