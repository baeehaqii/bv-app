<?php

use App\Models\{BvCampaignKol, BvCampign};
use App\Service\PostPerformanceService;
use Illuminate\Support\Facades\Http;

/**
 * Respons diambil dari bentuk asli ScrapeCreators (diuji langsung ke API-nya),
 * dipalsukan di sini supaya test deterministik & tidak memakai kredit.
 *
 * Inti yang dijaga: metrik yang TIDAK dilaporkan platform jangan ditulis 0.
 * Menulis 0 berarti menghapus angka yang benar — hasil migrasi sheet atau
 * isian manual dari IG Insights — dan itu kehilangan data, bukan pembaruan.
 */
function kolDenganAngkaSheet(string $url, string $platform, string $contentType): BvCampaignKol
{
    $campaign = BvCampign::firstOrCreate(
        ['campaign_name' => 'Ofero Leasing'],
        ['campaign_type' => 'internal'],
    );

    return BvCampaignKol::create([
        'campaign_id' => $campaign->id,
        'creator_name' => 'Mey Intan',
        'platform' => $platform,
        'content_type' => $contentType,
        'post_url' => $url,
        'brief_status' => 'approved',
        // Angka hasil migrasi sheet.
        'views' => 1621, 'likes' => 27, 'comments' => 2, 'shares' => 3, 'saves' => 2, 'reposts' => 0,
        'reach' => 1100, 'impressions' => 0, 'total_engagement' => 34,
        'engagement_rate' => 2.0975, 'price' => 5_000_000,
    ]);
}

it('TikTok mengisi lengkap termasuk shares & saves', function () {
    Http::fake(['api.scrapecreators.com/v2/tiktok/video*' => Http::response([
        'aweme_detail' => [
            'statistics' => [
                'play_count' => 1674, 'digg_count' => 30, 'comment_count' => 4,
                'share_count' => 5, 'collect_count' => 6,
            ],
            'author' => ['unique_id' => 'meymard', 'follower_count' => 6389],
        ],
    ])]);

    $kol = kolDenganAngkaSheet('https://www.tiktok.com/@meymard/video/7623393673065106706', 'tiktok', 'video');
    (new PostPerformanceService)->fetchAndUpdateKol($kol);

    expect($kol->fresh())
        ->views->toBe(1674)
        ->likes->toBe(30)
        ->comments->toBe(4)
        ->shares->toBe(5)
        ->saves->toBe(6)
        // Engagement = like + comment + share + save.
        ->total_engagement->toBe(45)
        ->and($kol->fresh()->last_fetched_at)->not->toBeNull();

    // ER ikut definisi sheet: Engagement / Views (bukan (like+comment)/views).
    expect((float) $kol->fresh()->engagement_rate)->toBe(round(45 / 1674 * 100, 4));
});

it('Instagram tidak menghapus shares & saves yang tidak disediakan API-nya', function () {
    // Respons nyata Instagram: tanpa save_count, share_count, maupun followers.
    Http::fake(['api.scrapecreators.com/v1/instagram/post*' => Http::response([
        'success' => true,
        'data' => ['xdt_shortcode_media' => [
            'product_type' => 'clips',
            'is_video' => true,
            'video_play_count' => 10235,
            'edge_media_preview_like' => ['count' => 426],
            'comment_count' => 10,
            'owner' => ['username' => 'meymard'],
        ]],
    ])]);

    $kol = kolDenganAngkaSheet('https://www.instagram.com/p/DWjGVMBDd5C/', 'instagram', 'reels');
    (new PostPerformanceService)->fetchAndUpdateKol($kol);

    expect($kol->fresh())
        ->views->toBe(10235)   // diperbarui
        ->likes->toBe(426)     // diperbarui
        ->comments->toBe(10)   // diperbarui
        ->shares->toBe(3)      // DIPERTAHANKAN — dulu jadi 0
        ->saves->toBe(2)       // DIPERTAHANKAN — dulu jadi 0
        ->reach->toBe(1100)    // reach tak tersedia di platform mana pun
        // Engagement dihitung dari nilai final, termasuk yang dipertahankan.
        ->total_engagement->toBe(441);

    // Engagement / Views — shares & saves yang dipertahankan ikut jadi pembilang.
    expect((float) $kol->fresh()->engagement_rate)->toBe(round(441 / 10235 * 100, 4));
});

it('YouTube tidak menghapus shares & saves', function () {
    Http::fake(['api.scrapecreators.com/v1/youtube/video*' => Http::response([
        'success' => true,
        'viewCountInt' => 824_575, 'likeCountInt' => 10_237, 'commentCountInt' => 176,
        'channel' => ['subscriberCount' => 4_300_000],
    ])]);

    $kol = kolDenganAngkaSheet('https://www.youtube.com/shorts/UHZS49cHvso', 'youtube', 'short');
    (new PostPerformanceService)->fetchAndUpdateKol($kol);

    expect($kol->fresh())
        ->views->toBe(824_575)
        ->likes->toBe(10_237)
        ->shares->toBe(3)
        ->saves->toBe(2)
        ->total_engagement->toBe(10_418);
});

it('fetch yang tidak mengembalikan apa pun tidak merusak angka yang sudah ada', function () {
    Http::fake(['api.scrapecreators.com/v2/tiktok/video*' => Http::response([
        'aweme_detail' => ['statistics' => [], 'author' => []],
    ])]);

    $kol = kolDenganAngkaSheet('https://www.tiktok.com/@meymard/video/7623393673065106706', 'tiktok', 'video');
    $sebelum = $kol->only(['views', 'likes', 'comments', 'shares', 'saves', 'reach']);

    (new PostPerformanceService)->fetchAndUpdateKol($kol);

    expect($kol->fresh()->only(['views', 'likes', 'comments', 'shares', 'saves', 'reach']))->toBe($sebelum)
        // Tetap ditandai sudah dicoba, supaya migrasi ulang tak menimpanya.
        ->and($kol->fresh()->last_fetched_at)->not->toBeNull();

    // ER dihitung ulang agar SELALU konsisten dengan angka tersimpan, memakai
    // definisi sheet: Engagement / Views = 34 / 1621. Karena tak ada angka baru,
    // hasilnya sama persis dengan nilai dari sheet.
    expect((float) $kol->fresh()->engagement_rate)->toBe(2.0975);
});

it('CPE dihitung otomatis dari price dibagi engagement', function () {
    $kol = kolDenganAngkaSheet('https://www.tiktok.com/@meymard/video/1', 'tiktok', 'video');

    // 5.000.000 / 34
    expect($kol->cpe())->toBe(147_058.82)
        ->and($kol->cpv())->toBe(3084.52);   // 5.000.000 / 1.621

    // total_engagement punya accessor yang selalu menjumlah ulang keempat
    // komponennya, jadi kolomnya tidak bisa dinolkan sendirian.
    $kol->update(['likes' => 0, 'comments' => 0, 'shares' => 0, 'saves' => 0, 'views' => 0]);

    // Tanpa penyebut, jangan bagi nol.
    expect($kol->fresh()->cpe())->toBe(0.0)->and($kol->fresh()->cpv())->toBe(0.0);
});

it('kolom CPE tampil di tabel KOL Performance', function () {
    Illuminate\Support\Facades\Gate::before(fn () => true);
    Spatie\Permission\Models\Role::firstOrCreate(['name' => 'super_admin']);
    $admin = App\Models\User::create(['name' => 'A', 'email' => 'cpe@bvnetwork.net', 'password' => bcrypt('x')]);
    $admin->syncRoles(['super_admin']);
    $this->actingAs($admin);

    $kol = kolDenganAngkaSheet('https://www.tiktok.com/@meymard/video/1', 'tiktok', 'video');

    $lw = Livewire\Livewire::test(
        App\Filament\Resources\BvCampigns\RelationManagers\KolsRelationManager::class,
        ['ownerRecord' => $kol->campaign, 'pageClass' => App\Filament\Resources\BvCampigns\Pages\EditBvCampign::class],
    );

    expect(array_map(fn ($c) => $c->getName(), $lw->instance()->getTable()->getColumns()))
        ->toContain('cpe');
});

it('ER memakai definisi sheet: Engagement dibagi Views', function () {
    $kol = kolDenganAngkaSheet('https://www.tiktok.com/@meymard/video/1', 'tiktok', 'video');

    // Baris Mey Intan di sheet: Engagement 34, Views 1.621, E.R 2,10%.
    expect($kol->calculateEngagementRate())->toBe(2.0975);

    // Shares & saves ikut jadi pembilang — itu bedanya dari rumus lama
    // (like + comment saja) yang memberi 1,789%.
    $kol->update(['shares' => 0, 'saves' => 0]);
    expect($kol->fresh()->calculateEngagementRate())->toBe(1.789);
});

it('postingan tanpa views memakai followers sebagai penyebut', function () {
    $kol = kolDenganAngkaSheet('https://www.instagram.com/p/X/', 'instagram', 'feed');
    $kol->update(['views' => 0, 'followers_count' => 10_000, 'er_type' => 'followers']);

    // 34 / 10.000
    expect($kol->fresh()->calculateEngagementRate())->toBe(0.34);
});

it('reposts ikut engagement dan tidak dihapus fetch', function () {
    Http::fake(['api.scrapecreators.com/v1/instagram/post*' => Http::response([
        'success' => true,
        'data' => ['xdt_shortcode_media' => [
            'product_type' => 'clips', 'is_video' => true,
            'video_play_count' => 10_100,
            'edge_media_preview_like' => ['count' => 421],
            'comment_count' => 10,
            'owner' => ['username' => 'meymard'],
        ]],
    ])]);

    $kol = kolDenganAngkaSheet('https://www.instagram.com/p/DWjGVMBDd5C/', 'instagram', 'reels');
    // Baris Instagram Mey Intan di sheet: saves 2, share 0, repost 2.
    $kol->update(['shares' => 0, 'saves' => 2, 'reposts' => 2]);

    (new PostPerformanceService)->fetchAndUpdateKol($kol);

    expect($kol->fresh())
        // Tak ada API yang mengembalikan repost — nilainya dijaga.
        ->reposts->toBe(2)
        // 421 + 10 + 0 + 2 + 2 = 435, sama dengan kolom Engagement di sheet.
        ->total_engagement->toBe(435);

    expect((float) $kol->fresh()->engagement_rate)->toBe(round(435 / 10100 * 100, 4));
});
