<?php

use App\Filament\Pages\CampaignSummaryList;
use Filament\Actions\Testing\TestAction;
use App\Filament\Resources\BvCampigns\Pages\KolPerformance;
use App\Models\BvCampaignKol;
use App\Models\BvCampign;
use App\Models\User;
use App\Service\CampaignSummary;
use App\Service\SentimentAnalyzer;
use Illuminate\Support\Facades\Gate;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;

/**
 * Campaign Summary — agregat seluruh postingan KOL satu campaign.
 *
 * Batas sumber data yang ikut dikunci: sentimen dihitung dari leksikon sendiri
 * (ScrapeCreators hanya mengirim teks komentar), dan Retrieve History dibangun
 * dari snapshot tiap fetch karena tidak ada histori harian dari API.
 */
function summaryCampaign(array $kols = []): BvCampign
{
    $campaign = BvCampign::create([
        'campaign_name' => 'Ofero Summary ' . uniqid(),
        'campaign_type' => BvCampign::TYPE_INTERNAL,
        'status' => 'ongoing',
    ]);

    foreach ($kols as $kol) {
        BvCampaignKol::create(array_merge([
            'campaign_id' => $campaign->id,
            'creator_name' => 'KOL ' . uniqid(),
            'platform' => 'tiktok',
            'content_type' => 'video',
            'brief_status' => 'approved',
            'post_url' => 'https://www.tiktok.com/@a/video/1',
        ], $kol));
    }

    return $campaign->fresh();
}

function summaryUser(): User
{
    Role::firstOrCreate(['name' => 'super_admin']);

    return tap(User::create([
        'name' => 'Summary Admin',
        'email' => 'summary-' . uniqid() . '@bvnetwork.net',
        'password' => bcrypt('password'),
    ]))->syncRoles(['super_admin']);
}

it('menjumlahkan seluruh postingan KOL, bukan merata-ratakan per KOL', function () {
    $campaign = summaryCampaign([
        // total_engagement itu ACCESSOR (likes+comments+shares+saves), bukan kolom
        // yang bisa diisi — jadi komponennya yang di-set.
        ['views' => 100_000, 'likes' => 5_000, 'comments' => 100, 'shares' => 50, 'saves' => 20,
         'price' => 5_000_000, 'followers_count' => 200_000],
        ['views' => 50_000, 'likes' => 1_000, 'comments' => 40, 'shares' => 10, 'saves' => 5,
         'price' => 1_000_000, 'followers_count' => 100_000],
    ]);

    $s = new CampaignSummary($campaign);
    $t = $s->totals();

    expect($t['views'])->toBe(150_000.0)
        ->and($t['engagement'])->toBe(6_225.0)
        ->and($t['cost'])->toBe(6_000_000.0)
        // CPE campaign = total cost / total engagement, bukan rata-rata CPE per KOL.
        ->and($s->cpe())->toBe(round(6_000_000 / 6_225, 2))
        ->and($s->cpv())->toBe(40.0)
        ->and($s->cpm())->toBe(40_000.0)
        ->and($s->engagementRate())->toBe(round(6_225 / 150_000 * 100, 2));
});

it('KOL yang belum approved atau belum tayang tidak dihitung sebagai konten', function () {
    $campaign = summaryCampaign([
        ['views' => 1_000, 'post_url' => 'https://tt/1'],
        ['views' => 500, 'post_url' => null],                       // belum tayang
        ['views' => 999, 'brief_status' => 'draft'],                 // belum approved
    ]);

    $s = new CampaignSummary($campaign);

    expect($s->kols)->toHaveCount(2)          // draft tersaring dari agregat
        ->and($s->published())->toHaveCount(1) // hanya yang punya post_url
        ->and($s->totals()['views'])->toBe(1_500.0);
});

it('metrics overview menilai CPE terbalik: makin kecil makin baik', function () {
    config()->set('kol.campaign_benchmark.cpe', ['excellent' => 500, 'good' => 2_000]);

    $murah = new CampaignSummary(summaryCampaign([
        ['views' => 1_000_000, 'likes' => 100_000, 'price' => 10_000_000], // CPE 100
    ]));
    $mahal = new CampaignSummary(summaryCampaign([
        ['views' => 1_000, 'likes' => 100, 'price' => 10_000_000],          // CPE 100.000
    ]));

    $cpe = fn(CampaignSummary $s) => collect($s->metricsOverview())->firstWhere('key', 'cpe')['verdict'];

    expect($cpe($murah))->toBe('excellent')
        ->and($cpe($mahal))->toBe('bad');
});

it('sentimen: skor per komentar dari leksikon, netral tidak dianggap buruk', function () {
    expect(SentimentAnalyzer::bucket('produknya keren bagus mantap'))->toBe('excellent')
        ->and(SentimentAnalyzer::bucket('lucu juga'))->toBe('good')
        ->and(SentimentAnalyzer::bucket('kapan restock'))->toBe('neutral')
        ->and(SentimentAnalyzer::bucket('mahal banget'))->toBe('average')
        ->and(SentimentAnalyzer::bucket('jelek parah penipu'))->toBe('negative');

    // "top" tidak boleh ikut kena dari dalam kata "laptop" — pencocokannya per kata utuh.
    expect(SentimentAnalyzer::score('pakai laptop'))->toBe(0);

    $ringkas = SentimentAnalyzer::summarize(['keren bagus mantap', 'kapan restock', 'jelek parah penipu']);

    expect($ringkas['total'])->toBe(3)
        ->and($ringkas['counts']['excellent'])->toBe(1)
        ->and($ringkas['counts']['neutral'])->toBe(1)
        ->and($ringkas['counts']['negative'])->toBe(1)
        ->and($ringkas['percentages']['excellent'])->toBe(33.33);

    // Semua komentar netral → 2.5/5, bukan 0.
    expect(SentimentAnalyzer::summarize(['kapan restock', 'ada diskon'])['score'])->toBe(2.5);
});

it('buzz word membuang stopword dan kata terlalu pendek', function () {
    $kata = SentimentAnalyzer::buzzWords([
        'produk ini bagus banget yang di kak',
        'produk bagus juga',
        'produk mantap',
    ], 5);

    expect(array_keys($kata))->toContain('produk')
        ->and($kata['produk'])->toBe(3)
        // 'ini', 'yang', 'di', 'kak' stopword; 'di' juga di bawah panjang minimal.
        ->and($kata)->not->toHaveKey('yang')
        ->and($kata)->not->toHaveKey('kak')
        ->and($kata)->not->toHaveKey('di');
});

it('sentimen campaign menggabungkan komentar dari semua postingan', function () {
    $campaign = summaryCampaign([
        ['comments_data' => ['keren bagus mantap', 'suka banget'], 'comments_fetched_at' => now()],
        ['comments_data' => ['jelek parah'], 'comments_fetched_at' => now()],
    ]);

    $s = new CampaignSummary($campaign);

    expect($s->allComments())->toHaveCount(3)
        ->and($s->sentiment()['total'])->toBe(3)
        ->and($s->sentiment()['counts']['negative'])->toBe(1);
});

it('snapshot postingan: 1 baris per tanggal, turunan dihitung bukan disimpan', function () {
    $campaign = summaryCampaign([
        ['views' => 10_000, 'likes' => 500, 'price' => 1_000_000, 'followers_count' => 20_000],
    ]);

    $kol = $campaign->kols()->sole();
    $kol->recordSnapshot();
    $kol->update(['views' => 12_000]);
    $kol->recordSnapshot();

    expect($kol->snapshots()->count())->toBe(1);

    $snap = $kol->snapshots()->sole();

    expect((int) $snap->views)->toBe(12_000)
        ->and($snap->cpe())->toBe(2_000.0)          // 1jt / 500
        ->and($snap->cpv())->toBe(83.33)            // 1jt / 12rb
        ->and($snap->cpm())->toBe(83_333.33)
        ->and($snap->vtr())->toBe(60.0);            // 12rb / 20rb
});

it('menu Campaign Summary hanya memuat campaign internal yang jalan', function () {
    Gate::before(fn() => true);

    $jalan = summaryCampaign([['views' => 10]]);
    $lain = BvCampign::create(['campaign_name' => 'External', 'campaign_type' => 'regular', 'status' => 'ongoing']);
    $draft = BvCampign::create([
        'campaign_name' => 'Belum Jalan', 'campaign_type' => BvCampign::TYPE_INTERNAL, 'status' => 'draft',
    ]);

    Livewire::actingAs(summaryUser())
        ->test(CampaignSummaryList::class)
        ->assertCanSeeTableRecords([$jalan])
        ->assertCanNotSeeTableRecords([$lain, $draft]);
});

it('kolom Engagement di daftar menjumlahkan komponennya, bukan kolom total_engagement yang basi', function () {
    Gate::before(fn() => true);

    $campaign = summaryCampaign([
        ['likes' => 100, 'comments' => 10, 'shares' => 5, 'saves' => 2],
        ['likes' => 50, 'comments' => 3, 'shares' => 1, 'saves' => 0],
    ]);

    $baris = Livewire::actingAs(summaryUser())
        ->test(CampaignSummaryList::class)
        ->instance()->getTableRecords()->firstWhere('id', $campaign->id);

    expect((int) $baris->total_engagement_sum)->toBe(171);
});

it('halaman summary ter-render dengan semua section', function () {
    Gate::before(fn() => true);

    $campaign = summaryCampaign([
        ['creator_name' => 'erennnnjuslim', 'views' => 4_599_937, 'likes' => 38_911, 'comments' => 111,
         'shares' => 1_739, 'saves' => 8_502, 'price' => 5_000_000,
         'followers_count' => 826_320, 'comments_data' => ['keren bagus'], 'comments_fetched_at' => now()],
    ]);

    $campaign->kols()->sole()->recordSnapshot();

    Livewire::actingAs(summaryUser())
        ->test(CampaignSummaryList::class, ['campaignId' => $campaign->id])
        ->assertSee('Campaign Performance')
        ->assertSee('Campaign Sentiments Summary')
        ->assertSee('Top 10 Buzz Word')
        ->assertSee('Content List')
        ->assertSee('Retrieve History')
        ->assertSee('Metrics Overview')
        ->assertDontSee('Komentar belum pernah diambil');
});

it('section sentimen memberi arahan saat komentar belum diambil', function () {
    Gate::before(fn() => true);

    $campaign = summaryCampaign([['views' => 100]]);

    Livewire::actingAs(summaryUser())
        ->test(CampaignSummaryList::class, ['campaignId' => $campaign->id])
        ->assertSee('Komentar belum pernah diambil')
        ->assertActionVisible('analyze_sentiment');
});

it('detail tetap di halaman Campaign Summary, tidak melompat ke resource lain', function () {
    Gate::before(fn() => true);

    $campaign = summaryCampaign([['views' => 100, 'likes' => 10]]);
    $detail = CampaignSummaryList::getUrl(['campaign' => $campaign->id]);

    // Baris daftar mengarah ke halaman yang sama dengan query ?campaign=, bukan ke resource lain.
    expect($detail)->toContain('/campaign-summary')->not->toContain('campaign-ongoing-internal');

    Livewire::actingAs(summaryUser())
        ->test(CampaignSummaryList::class)
        ->assertSet('campaignId', null)
        ->assertTableActionHasUrl('buka_summary', $detail, record: $campaign);

    $this->actingAs(summaryUser())->get($detail)->assertOk();
});

it('Content List di mode detail berisi postingan KOL, bukan daftar campaign', function () {
    Gate::before(fn() => true);

    $campaign = summaryCampaign([
        ['creator_name' => 'erennnnjuslim', 'username' => 'erennnnjuslim', 'tier' => 'macro',
         'views' => 4_599_937, 'likes' => 38_911, 'price' => 5_000_000],
    ]);

    $kol = $campaign->kols()->sole();

    Livewire::actingAs(summaryUser())
        ->test(CampaignSummaryList::class, ['campaignId' => $campaign->id])
        // Kolom mengikuti referensi Content List.
        ->assertTableColumnExists('creator_name')
        ->assertTableColumnExists('upload_status')
        ->assertTableColumnExists('cpe')
        ->assertCanSeeTableRecords([$kol])
        // Kolom milik tabel daftar campaign tidak boleh ikut terbawa.
        ->assertTableColumnDoesNotExist('campaign_name')
        ->assertTableColumnDoesNotExist('posted_count');
});

it('rute lama kol-performance dialihkan ke menu Campaign Summary', function () {
    Gate::before(fn() => true);

    $campaign = summaryCampaign([['views' => 100]]);

    $this->actingAs(summaryUser())
        ->get(KolPerformance::getUrl(['record' => $campaign]))
        ->assertRedirect(CampaignSummaryList::getUrl(['campaign' => $campaign->id]));
});

it('mengekspor Campaign Summary jadi PDF', function () {
    Gate::before(fn() => true);

    $campaign = summaryCampaign([
        ['creator_name' => 'Windah', 'username' => 'windah', 'views' => 100_000, 'likes' => 5_000,
         'comments' => 100, 'shares' => 50, 'saves' => 20, 'price' => 5_000_000, 'followers_count' => 200_000],
    ]);

    $response = $this->actingAs(summaryUser())
        ->get(route('campaign-summary.pdf', ['bvCampign' => $campaign->id]));

    $response->assertOk()->assertHeader('content-type', 'application/pdf');

    expect($response->getContent())->toStartWith('%PDF');
});

it('menulis ringkasan AI ke campaign lewat aksi header', function () {
    Gate::before(fn() => true);
    config(['ai.providers.gemini.key' => 'key-palsu']);

    // agent() menghasilkan AnonymousAgent, jadi fake-nya dipasang di kelas itu.
    \Laravel\Ai\Ai::fakeAgent(\Laravel\Ai\AnonymousAgent::class, ['Campaign berjalan baik. Engagement di atas benchmark.']);

    $campaign = summaryCampaign([
        ['views' => 100_000, 'likes' => 5_000, 'comments' => 100, 'price' => 5_000_000, 'followers_count' => 200_000],
    ]);

    Livewire::actingAs(summaryUser())
        ->test(CampaignSummaryList::class, ['campaignId' => $campaign->id])
        ->callAction('ringkasan_ai')
        ->assertHasNoActionErrors();

    $campaign->refresh();

    expect($campaign->ai_summary)->toContain('Engagement di atas benchmark')
        ->and($campaign->ai_summary_at)->not->toBeNull();
});

it('menyembunyikan tombol Ringkasan AI saat API key belum diisi', function () {
    Gate::before(fn() => true);
    config(['ai.providers.gemini.key' => null]);

    $campaign = summaryCampaign([['views' => 100]]);

    Livewire::actingAs(summaryUser())
        ->test(CampaignSummaryList::class, ['campaignId' => $campaign->id])
        ->assertActionHidden('ringkasan_ai');
});
