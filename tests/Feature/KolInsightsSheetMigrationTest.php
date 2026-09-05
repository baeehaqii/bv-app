<?php

use App\Models\{BvCampaignKol, BvCampign, MediaPlanCalcSetting};
use App\Service\KolInsightsSheetMigration;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;

/**
 * Acuan: docs/[EXT] Ofero - KOL Insights.xlsx.
 *
 * Grid disusun dari file itu dalam bentuk yang PERSIS sama dengan keluaran
 * GoogleSheetReader::readGrid() — {v, h} per sel, hasil rumus (bukan teks
 * rumusnya), tanggal sebagai serial. Jadi logika parsing diuji dengan data
 * sungguhan tanpa memanggil Google.
 */
const SHEET_INSIGHTS = 'docs/[EXT] Ofero - KOL Insights.xlsx';

/** @return array<int, array<int, array{v: mixed, h: string|null}>> */
function gridDari(string $tab): array
{
    $path = base_path(SHEET_INSIGHTS);

    if (! file_exists($path)) {
        test()->markTestSkipped('File acuan KOL Insights tidak ada di repo ini.');
    }

    $reader = IOFactory::createReader('Xlsx');
    $reader->setReadDataOnly(false); // hyperlink hilang kalau true
    $reader->setLoadSheetsOnly([$tab]);
    $ws = $reader->load($path)->getSheetByName($tab);

    $grid = [];

    for ($r = 1; $r <= $ws->getHighestDataRow(); $r++) {
        $baris = [];

        for ($c = 1; $c <= 30; $c++) {
            $cell = $ws->getCellByColumnAndRow($c, $r);
            $v = $cell->getValue();

            // Google mengembalikan HASIL rumus di effectiveValue.
            if (is_string($v) && str_starts_with($v, '=')) {
                $v = $cell->getOldCalculatedValue();
            }

            // Google mengembalikan tanggal sebagai serial.
            if ($v instanceof DateTimeInterface) {
                $v = ExcelDate::PHPToExcel($v);
            }

            $baris[] = ['v' => $v, 'h' => $cell->getHyperlink()->getUrl() ?: null];
        }

        $grid[] = $baris;
    }

    return $grid;
}

function profilUntuk(string $tab, ?BvCampign $campaign = null): KolInsightsSheetMigration
{
    return (new KolInsightsSheetMigration)
        ->untukTab($tab)
        ->untukCampaign($campaign?->id);
}

beforeEach(function () {
    (new Database\Seeders\MediaPlanCalcSettingSeeder)->run();
    MediaPlanCalcSetting::forgetCached();
});

it('mengambil link postingan dari hyperlink sel, bukan tulisan "Link"', function () {
    $items = profilUntuk('KOL Leasing TikTok')->parseRows(gridDari('KOL Leasing TikTok'));
    $baris = collect($items)->firstWhere('creator_name', 'Mey Intan');

    expect($baris['post_url'])->toBe('https://www.tiktok.com/@meymard/video/7623393673065106706')
        ->and($baris['username'])->toBe('meymard')
        ->and($baris['platform'])->toBe('tiktok')
        ->and($baris['content_type'])->toBe('video')
        ->and($baris['sow'])->toBe('Leasing')
        ->and($baris['followers_count'])->toBe(6100)   // "6,1K"
        ->and($baris['posting_date'])->toBe('2026-03-31');

    expect($items)->toHaveCount(25)
        ->and(collect($items)->whereNull('post_url'))->toHaveCount(0);
});

it('membaca hasil rumus, bukan teks rumusnya', function () {
    $items = profilUntuk('KOL Leasing TikTok')->parseRows(gridDari('KOL Leasing TikTok'));
    $baris = collect($items)->firstWhere('creator_name', 'Mey Intan');

    // Engagement =SUM(F:I), E.R =L/K, Reach =70%*Views — semuanya sel berumus.
    expect($baris['total_engagement'])->toBe(34)
        ->and($baris['reach'])->toBe(1100)
        ->and($baris['likes'])->toBe(27)
        ->and($baris['views'])->toBe(1621)
        // "E.R" di sheet adalah PECAHAN berformat 0.00% (0,0209747 = 2,10%).
        ->and($baris['engagement_rate'])->toBe(2.0975);
});

it('tidak membaca baris judul blok bertumpuk sebagai KOL', function () {
    // Tab Visit memuat DUA tabel: "Micro KOL" lalu "Nano KOL".
    $items = profilUntuk('KOL Visit IG')->parseRows(gridDari('KOL Visit IG'));
    $nama = collect($items)->pluck('creator_name');

    expect($nama)->not->toContain('KOL Name')
        ->and($nama)->not->toContain('Stareer 5 Lit Launching')
        ->and($nama)->not->toContain('Visit IG- Nano KOL')
        ->and($items)->toHaveCount(19);

    // Tier terbawa dari label tiap blok.
    expect(collect($items)->pluck('tier')->unique()->sort()->values()->all())->toBe(['Micro', 'Nano']);
});

it('SOW & platform dibaca dari nama tab, bukan judul di dalam sheet', function () {
    // Baris judul tab "KOL Leasing TikTok" tertulis "Visit TikTok - Micro KOL"
    // — sisa salin-tempel yang tidak boleh dipercaya.
    $leasing = profilUntuk('KOL Leasing TikTok')->parseRows(gridDari('KOL Leasing TikTok'));
    expect($leasing[0]['sow'])->toBe('Leasing')->and($leasing[0]['platform'])->toBe('tiktok');

    $stitch = profilUntuk('KOL Stitch IG')->parseRows(gridDari('KOL Stitch IG'));
    expect($stitch[0]['sow'])->toBe('Stitch')->and($stitch[0]['platform'])->toBe('instagram');

    // Tab tanpa nama platform: platformnya ditulis per baris.
    $main = profilUntuk('Raden Rauf')->parseRows(gridDari('Raden Rauf'));
    expect($main[0]['sow'])->toBe('Raden Rauf')->and($main[0]['platform'])->toBe('tiktok');
});

it('membaca semua baris walau nama KOL-nya sel merge', function () {
    // Tab "Raden Rauf": satu nama di-merge B4:B6 untuk tiga postingan di tiga
    // platform. Sel hasil merge datang kosong dari Google — tanpa membawa turun
    // nama terakhir, dua baris terakhir hilang tanpa jejak.
    $items = profilUntuk('Raden Rauf')->parseRows(gridDari('Raden Rauf'));

    expect($items)->toHaveCount(3)
        ->and(collect($items)->pluck('creator_name')->unique()->all())->toBe(['Raden Rauf'])
        ->and(collect($items)->pluck('platform')->all())->toBe(['tiktok', 'instagram', 'youtube'])
        ->and(collect($items)->pluck('content_type')->all())->toBe(['video', 'feed', 'short']);

    // Angka & follower tetap milik barisnya masing-masing, bukan ikut baris atas.
    expect(collect($items)->pluck('views')->all())->toBe([192_200, 686_563, 77_300])
        ->and(collect($items)->pluck('followers_count')->all())->toBe([2_100_000, 2_200_000, 4_300_000]);

    // "16-19 Jan 2026" itu rentang, bukan satu tanggal — dikosongkan, bukan ditebak.
    expect($items[0]['posting_date'])->toBeNull()
        ->and($items[1]['posting_date'])->toBe('2026-01-28');
});

it('menafsirkan follower bergaya sheet', function () {
    $p = new KolInsightsSheetMigration;

    expect($p->parseFollowers('13K'))->toBe(13_000)
        ->and($p->parseFollowers('6,1K'))->toBe(6_100)
        ->and($p->parseFollowers('19.0K'))->toBe(19_000)
        ->and($p->parseFollowers('2.1M'))->toBe(2_100_000)
        ->and($p->parseFollowers(4033))->toBe(4033)
        ->and($p->parseFollowers('1.317.470'))->toBe(1_317_470)
        ->and($p->parseFollowers('-'))->toBeNull();
});

it('menyimpan ke KOL Performance dengan angka yang cocok dengan tab Summary', function () {
    $campaign = BvCampign::create(['campaign_name' => 'Ofero Leasing', 'campaign_type' => 'internal']);

    foreach (['KOL Leasing TikTok', 'KOL Leasing IG'] as $tab) {
        $profil = profilUntuk($tab, $campaign);
        $hasil = $profil->persist($profil->parseRows(gridDari($tab)));

        expect($hasil['success'])->toBe(25)->and($hasil['failed'])->toBe(0);
    }

    // Validasi silang dengan tab "Summary Ofero Leasing" di file yang sama.
    // Kalau pembacaan kolom bergeser, angka ini yang meleset duluan.
    expect((int) $campaign->kols()->sum('views'))->toBe(234_886)
        ->and((int) $campaign->kols()->sum('likes'))->toBe(4_519)
        ->and((int) $campaign->kols()->sum('comments'))->toBe(492)
        ->and((int) $campaign->kols()->sum('saves'))->toBe(185)
        ->and((int) $campaign->kols()->sum('shares'))->toBe(1_960)
        ->and((int) $campaign->kols()->sum('total_engagement'))->toBe(7_246)
        // Repost hanya ada di tab Instagram; totalnya cocok dengan Summary.
        ->and((int) $campaign->kols()->sum('reposts'))->toBe(90)
        // Summary menulis 137.635,5 — pembulatan per baris menghasilkan 137.636.
        ->and((int) $campaign->kols()->sum('reach'))->toBe(137_636);

    // Satu KOL yang sama di dua platform = dua postingan, bukan satu baris.
    expect($campaign->kols()->where('creator_name', 'Mey Intan')->count())->toBe(2);
});

it('menolak menyimpan kalau campaign tujuan belum dipilih', function () {
    $profil = profilUntuk('KOL Leasing TikTok'); // tanpa campaign
    $hasil = $profil->persist($profil->parseRows(gridDari('KOL Leasing TikTok')));

    expect($hasil['success'])->toBe(0)
        ->and($hasil['failed'])->toBe(25)
        ->and($hasil['notes'][0])->toContain('Campaign tujuan belum dipilih');
});

it('migrasi ulang memperbarui, tidak menggandakan, dan tidak memundurkan angka yang sudah di-fetch', function () {
    $campaign = BvCampign::create(['campaign_name' => 'Ofero Leasing', 'campaign_type' => 'internal']);
    $grid = gridDari('KOL Leasing TikTok');
    $profil = profilUntuk('KOL Leasing TikTok', $campaign);

    $profil->persist($profil->parseRows($grid));

    $sudahFetch = $campaign->kols()->where('creator_name', 'Mey Intan')->first();
    $sudahFetch->update(['views' => 999_999, 'last_fetched_at' => now()]);

    $belumFetch = $campaign->kols()->where('creator_name', 'yunnims')->first();
    $belumFetch->update(['views' => 1]);

    $profil->persist($profil->parseRows($grid));

    expect(BvCampaignKol::where('campaign_id', $campaign->id)->count())->toBe(25)
        ->and($sudahFetch->fresh()->views)->toBe(999_999)  // fetch menang, selamanya
        ->and($belumFetch->fresh()->views)->toBe(504);     // belum pernah di-fetch: sheet acuan
});

it('baris hasil migrasi benar-benar tampil di tabel KOL Performance', function () {
    Illuminate\Support\Facades\Gate::before(fn () => true);
    Spatie\Permission\Models\Role::firstOrCreate(['name' => 'super_admin']);
    $admin = App\Models\User::create(['name' => 'Admin', 'email' => 'a@bvnetwork.net', 'password' => bcrypt('x')]);
    $admin->syncRoles(['super_admin']);
    $this->actingAs($admin);

    $campaign = BvCampign::create(['campaign_name' => 'Ofero Leasing', 'campaign_type' => 'internal']);
    $profil = profilUntuk('KOL Leasing TikTok', $campaign);
    $profil->persist($profil->parseRows(gridDari('KOL Leasing TikTok')));

    // Regresi: tabel ini memfilter brief_status = 'approved' sementara default
    // kolomnya 'draft'. Menghitung lewat relasi saja LOLOS padahal layar kosong.
    $lw = Livewire\Livewire::test(
        App\Filament\Resources\BvCampigns\RelationManagers\KolsRelationManager::class,
        ['ownerRecord' => $campaign, 'pageClass' => App\Filament\Resources\BvCampigns\Pages\EditBvCampign::class],
    );

    expect($lw->instance()->getTable()->getQuery()->count())->toBe(25)
        ->and($lw->instance()->getTable()->getQuery()->where('creator_name', 'Mey Intan')->value('post_url'))
        ->toBe('https://www.tiktok.com/@meymard/video/7623393673065106706');

    // Link punya kolomnya sendiri, bukan cuma aksi baris.
    expect(array_map(fn ($c) => $c->getName(), $lw->instance()->getTable()->getColumns()))
        ->toContain('post_url');
});

it('terdaftar sebagai jenis di halaman Migrasi Data, dengan target campaign', function () {
    Illuminate\Support\Facades\Gate::before(fn () => true);
    Spatie\Permission\Models\Role::firstOrCreate(['name' => 'super_admin']);
    $admin = App\Models\User::create(['name' => 'Admin', 'email' => 'm@bvnetwork.net', 'password' => bcrypt('x')]);
    $admin->syncRoles(['super_admin']);
    $this->actingAs($admin);

    BvCampign::create(['campaign_name' => 'Ofero Leasing', 'campaign_type' => 'internal']);

    expect(App\Filament\Pages\MigrasiData::PROFIL)->toHaveKey('kolinsights');

    $lw = Livewire\Livewire::test(App\Filament\Pages\MigrasiData::class)
        ->set('data.jenis', 'kolinsights');

    // Profil menerima campaign & nama tab dari form.
    $profil = $lw->set('data.campaignId', BvCampign::first()->id)
        ->set('data.sheetName', 'KOL Leasing TikTok')
        ->instance()->profil();

    expect($profil)->toBeInstanceOf(KolInsightsSheetMigration::class)
        // Butuh grid: link postingan tersembunyi di hyperlink.
        ->and($profil->butuhGrid())->toBeTrue();

    // Profil lain tidak ikut terseret ke jalur grid yang lebih berat.
    expect((new App\Service\ClientSheetMigration)->butuhGrid())->toBeFalse();

    $this->get('/office/migrasi-data')->assertSuccessful();
});

it('menolak preview sebelum campaign tujuan dipilih', function () {
    Illuminate\Support\Facades\Gate::before(fn () => true);
    Spatie\Permission\Models\Role::firstOrCreate(['name' => 'super_admin']);
    $admin = App\Models\User::create(['name' => 'Admin', 'email' => 'n@bvnetwork.net', 'password' => bcrypt('x')]);
    $admin->syncRoles(['super_admin']);
    $this->actingAs($admin);

    Livewire\Livewire::test(App\Filament\Pages\MigrasiData::class)
        ->set('data.jenis', 'kolinsights')
        ->set('data.sheetLink', 'https://docs.google.com/spreadsheets/d/1sSKOULjZgEl-_pkGdkB68OJPaUWNybY6iJ-r3juGcS8/edit')
        ->set('data.campaignId', null)
        ->call('preview')
        ->assertSet('errorMessage', 'Pilih dulu campaign tujuannya.');
});

/** Pembaca palsu: mengembalikan grid dari file .xlsx, tanpa memanggil Google. */
function pakaiPembacaPalsu(string $tab): void
{
    $grid = gridDari($tab);

    $palsu = new class($grid) extends App\Service\GoogleSheetReader
    {
        public function __construct(private array $grid) {}

        public function readGrid(string $spreadsheetId, ?string $sheetName = null): array
        {
            return $this->grid;
        }

        public function sheetNames(string $spreadsheetId): array
        {
            return [];
        }
    };

    app()->instance(App\Service\GoogleSheetReader::class, $palsu);
}

function adminMigrasi(string $email): void
{
    Illuminate\Support\Facades\Gate::before(fn () => true);
    Spatie\Permission\Models\Role::firstOrCreate(['name' => 'super_admin']);
    $u = App\Models\User::create(['name' => 'Admin', 'email' => $email, 'password' => bcrypt('x')]);
    $u->syncRoles(['super_admin']);
    test()->actingAs($u);
}

it('preview di halaman Migrasi Data jalan untuk baris grid', function () {
    adminMigrasi('p@bvnetwork.net');
    pakaiPembacaPalsu('KOL Leasing TikTok');

    $campaign = BvCampign::create(['campaign_name' => 'Ofero Leasing', 'campaign_type' => 'internal']);

    // Regresi: headerRowIndex()/mapHeaders()/pisahHeader() di kelas dasar
    // menerima sel {v,h} dan meledak "Array to string conversion". parseRows()
    // milik profil sudah benar, jadi bug ini HANYA muncul lewat preview().
    Livewire\Livewire::test(App\Filament\Pages\MigrasiData::class)
        ->set('data.jenis', 'kolinsights')
        ->set('data.sheetLink', 'https://docs.google.com/spreadsheets/d/1sSKOULjZgEl-_pkGdkB68OJPaUWNybY6iJ-r3juGcS8/edit')
        ->set('data.campaignId', $campaign->id)
        ->set('data.sheetName', 'KOL Leasing TikTok')
        ->call('preview')
        ->assertSet('errorMessage', null)
        ->assertSet('previewed', true)
        ->assertSet('totalItems', 25)
        // Link postingan ikut terbawa ke baris preview.
        ->assertSee('https://www.tiktok.com/@meymard/video/7623393673065106706');
});

it('migrasi penuh lewat halaman menyimpan ke campaign', function () {
    adminMigrasi('q@bvnetwork.net');
    pakaiPembacaPalsu('KOL Leasing TikTok');

    $campaign = BvCampign::create(['campaign_name' => 'Ofero Leasing', 'campaign_type' => 'internal']);

    $lw = Livewire\Livewire::test(App\Filament\Pages\MigrasiData::class)
        ->set('data.jenis', 'kolinsights')
        ->set('data.sheetLink', 'https://docs.google.com/spreadsheets/d/1sSKOULjZgEl-_pkGdkB68OJPaUWNybY6iJ-r3juGcS8/edit')
        ->set('data.campaignId', $campaign->id)
        ->set('data.sheetName', 'KOL Leasing TikTok')
        ->call('preview')
        ->call('startMigration');

    // Proses per chunk sampai selesai.
    for ($i = 0; $i < 20 && ! $lw->get('finished'); $i++) {
        $lw->call('processChunk');
    }

    $lw->assertSet('finished', true)->assertSet('success', 25);

    expect($campaign->kols()->count())->toBe(25)
        // Views tab TikTok saja; 28.725 + 206.161 (tab IG) = 234.886 di Summary.
        ->and((int) $campaign->kols()->sum('views'))->toBe(28_725);
});

it('ER dihitung ulang dengan satu rumus, bukan disalin dari kolom E.R sheet', function () {
    $campaign = BvCampign::create(['campaign_name' => 'Ofero Leasing', 'campaign_type' => 'internal']);

    foreach (['KOL Leasing TikTok', 'KOL Leasing IG'] as $tab) {
        $profil = profilUntuk($tab, $campaign);
        $profil->persist($profil->parseRows(gridDari($tab)));
    }

    $tiktok = $campaign->kols()->where('platform', 'tiktok')->where('creator_name', 'Mey Intan')->first();
    $ig = $campaign->kols()->where('platform', 'instagram')->where('creator_name', 'Mey Intan')->first();

    // Tab TikTok di sheet memang memakai Engagement/Views — angkanya sama persis.
    expect((float) $tiktok->engagement_rate)->toBe(2.0975)
        ->and($tiktok->er_type)->toBe('views');

    // Tab Instagram di sheet memakai Engagement/REACH (7,18%). Sistem memakai
    // Views supaya ER tetap bisa diperbarui — Reach tak bisa di-fetch dari
    // platform mana pun. Engagement 435 (termasuk 2 repost) / 10.100 views.
    expect($ig->total_engagement)->toBe(435)
        ->and($ig->reposts)->toBe(2)
        ->and((float) $ig->engagement_rate)->toBe(round(435 / 10100 * 100, 4));

    // Rumus yang sama dipakai ulang oleh model, jadi tidak melompat saat fetch.
    expect($tiktok->calculateEngagementRate())->toBe((float) $tiktok->engagement_rate)
        ->and($ig->calculateEngagementRate())->toBe((float) $ig->engagement_rate);
});
