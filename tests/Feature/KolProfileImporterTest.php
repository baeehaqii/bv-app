<?php

use App\Filament\Resources\DataKols\Pages\EditDataKol;
use App\Models\BvSPK;
use App\Models\DataKol;
use App\Models\KolRateCard;
use App\Models\User;
use App\Service\KolProfileImporter;
use Filament\Actions\Testing\TestAction;
use Filament\Notifications\Notification as FilamentNotification;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Testing\File as TestingFile;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Gate;
use Livewire\Livewire;
use PhpOffice\PhpSpreadsheet\Cell\DataValidation;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx as XlsxWriter;
use Spatie\Permission\Models\Role;

/**
 * KolProfileImporter dipakai tombol "New Data KOL" (daftar) dan "Tambah Channel"
 * (edit). Yang diuji di sini pemetaan profil → baris DataKol dan aturan
 * penggabungan channel — HTTP-nya di-stub, bukan yang sedang diuji.
 */
beforeEach(function () {
    Role::firstOrCreate(['name' => 'super_admin']);
    $user = User::create([
        'name' => 'KOL Admin',
        'email' => 'importer@bvnetwork.net',
        'password' => bcrypt('password'),
    ]);
    $user->syncRoles(['super_admin']);
    $this->actingAs($user);
    Gate::before(fn() => true);
});

/** Bentuk array yang dikembalikan service scraping. */
function fakeProfile(string $username, int $followers = 5_000_000): array
{
    return [
        'username' => $username,
        'followers_count' => $followers,
        'tier' => DataKol::tierFor($followers),
        'engagement_rate' => 6.5,
        'total_engagements' => 88_000,
        'average_impressions' => 3_200_000,
        'average_likes' => 80_000,
        'average_comments' => 8_000,
        'following_count' => 120,
        'media_count' => 340,
        'category_name' => 'Gamers & Lifestyle',
        'business_category_name' => 'None',
        'full_name' => 'Windah Basudara',
        'business_email' => 'windah@example.test',
        'business_phone_number' => '08123456789',
        'biography' => 'Bocah ngapa yak',
        'is_verified' => true,
        'is_business_account' => false,
        'external_url' => 'https://example.test',
    ];
}

/** Importer dengan HTTP di-stub: URL yang ada di peta berhasil, sisanya gagal. */
function importerStub(array $profilPerUrl): KolProfileImporter
{
    return new class($profilPerUrl) extends KolProfileImporter
    {
        public function __construct(private array $profilPerUrl) {}

        public function fetchProfile(string $channel, string $url): array
        {
            return $this->profilPerUrl[$url]
                ?? throw new RuntimeException('Profil tidak ditemukan.');
        }
    };
}

it('memetakan profil scraping ke kolom DataKol', function () {
    $kol = (new KolProfileImporter())->save(
        fakeProfile('windahbasudara'),
        'Tiktok',
        'https://tiktok.com/@windahbasudara',
    );

    expect($kol->username)->toBe('windahbasudara')
        ->and($kol->channel)->toBe('Tiktok')
        ->and((int) $kol->followers)->toBe(5_000_000)
        ->and($kol->tier)->toBe('Mega')
        ->and($kol->category)->toBe(['Gamers & Lifestyle'])
        ->and($kol->email)->toBe('windah@example.test')
        ->and($kol->contact)->toBe('windah@example.test')
        ->and($kol->wa_number)->toBe('08123456789')
        ->and($kol->notes)->toContain('Bio: Bocah ngapa yak')
        ->toContain('✓ Verified Account')
        ->toContain('Videos: 340');   // Tiktok → "Videos", bukan "Posts"
});

it('memperbarui baris yang ada, bukan menggandakan, untuk username+channel yang sama', function () {
    $importer = new KolProfileImporter();

    $importer->save(fakeProfile('windahbasudara', 5_000_000), 'Tiktok', 'https://tiktok.com/@windahbasudara');
    $kedua = $importer->save(fakeProfile('windahbasudara', 5_100_000), 'Tiktok', 'https://tiktok.com/@windahbasudara');

    expect(DataKol::count())->toBe(1)
        ->and((int) $kedua->followers)->toBe(5_100_000);
});

it('menggabungkan channel baru lewat kol_key tanpa menimpa username aslinya', function () {
    // Handle TikTok beda dengan handle IG, tapi orangnya sama.
    $kol = (new KolProfileImporter())->save(
        fakeProfile('windah_tiktok_official'),
        'Tiktok',
        'https://tiktok.com/@windah_tiktok_official',
        kolKey: 'windahbasudara',
    );

    // Yang disamakan cuma kunci grupnya; handle asli tetap utuh di kolom username.
    expect($kol->kol_key)->toBe('windahbasudara')
        ->and($kol->username)->toBe('windah_tiktok_official')
        ->and($kol->link_userprofile)->toBe('https://www.tiktok.com/@windah_tiktok_official');
});

it('meringkas hasil impor massal: berhasil, gagal, dan handle yang berbeda', function () {
    $importer = importerStub([
        'ig-ok' => fakeProfile('windahbasudara', 3_500_000),
        'tt-beda' => fakeProfile('windah_tiktok', 5_000_000),
    ]);

    $hasil = $importer->importMany([
        ['channel' => 'Instagram', 'link_userprofile' => 'ig-ok'],
        ['channel' => 'Tiktok', 'link_userprofile' => 'tt-beda'],
        ['channel' => 'Instagram', 'link_userprofile' => 'tidak-ada'],
        ['channel' => 'Instagram', 'link_userprofile' => '   '],   // baris kosong dilewati
    ], kolKey: 'windahbasudara');

    expect($hasil['created'])->toBe(2)
        ->and($hasil['updated'])->toBe(0)
        ->and($hasil['failed'])->toHaveCount(1)
        ->and($hasil['first']->username)->toBe('windahbasudara')
        // Handle TikTok beda → tetap disimpan apa adanya, tapi dilaporkan.
        ->and($hasil['mismatched'])->toBe(['Tiktok: @windah_tiktok'])
        ->and(DataKol::where('channel', 'Tiktok')->first()->username)->toBe('windah_tiktok');

    // Keduanya mengelompok jadi 1 KOL dengan 2 channel.
    expect(DataKol::oneRowPerKol()->count())->toBe(1)
        ->and(DataKol::count())->toBe(2);
});

it('memotong impor massal di batas MAX_BULK', function () {
    $urls = [];
    $rows = [];
    foreach (range(1, KolProfileImporter::MAX_BULK + 5) as $i) {
        $urls["akun{$i}"] = fakeProfile("akun{$i}", 50_000);
        $rows[] = ['channel' => 'Instagram', 'link_userprofile' => "akun{$i}"];
    }

    $hasil = importerStub($urls)->importMany($rows);

    expect($hasil['created'])->toBe(KolProfileImporter::MAX_BULK)
        ->and(DataKol::count())->toBe(KolProfileImporter::MAX_BULK);
});

/** @return array{DataKol, TestAction} KOL yang sedang dibuka + alamat tombolnya. */
function kolDenganTombolTambahChannel(): array
{
    $kol = DataKol::create([
        'username' => 'windahbasudara',
        'channel' => 'Tiktok',
        'link_userprofile' => 'https://tiktok.com/@windahbasudara',
        'followers' => 5_000_000,
    ]);

    return [$kol, TestAction::make('add_channel')->schemaComponent()];
}

it('menampilkan tombol Tambah Channel di halaman edit', function () {
    [$kol, $tombol] = kolDenganTombolTambahChannel();

    Livewire::test(EditDataKol::class, ['record' => $kol->id])
        ->assertActionExists($tombol);
});

it('membuka modal sudah pada mode satu channel, field Channel & URL langsung tampil', function () {
    [$kol, $tombol] = kolDenganTombolTambahChannel();

    // Regresi: mountUsing() kustom sempat menimpa fill() bawaan Filament, jadi
    // `mode` kosong dan field yang bergantung padanya baru muncul setelah radio
    // di-klik bolak-balik.
    Livewire::test(EditDataKol::class, ['record' => $kol->id])
        ->mountAction($tombol)
        ->assertActionDataSet([
            'mode' => 'satu',
            'channel' => 'Instagram',
        ]);
});

it('mode satu channel menggabungkan channel baru ke KOL yang sedang dibuka', function () {
    [$kol, $tombol] = kolDenganTombolTambahChannel();

    app()->bind(KolProfileImporter::class, fn() => importerStub([
        'https://instagram.com/windah_ig' => fakeProfile('windah_ig', 3_500_000),
    ]));

    Livewire::test(EditDataKol::class, ['record' => $kol->id])
        ->callAction($tombol, [
            'mode' => 'satu',
            'channel' => 'Instagram',
            'link_userprofile' => 'https://instagram.com/windah_ig',
        ]);

    // Handle IG-nya beda dan TETAP disimpan apa adanya; yang menyatukan kol_key.
    expect(DataKol::count())->toBe(2)
        ->and(DataKol::oneRowPerKol()->count())->toBe(1)
        ->and(DataKol::where('channel', 'Instagram')->first()->username)->toBe('windah_ig')
        ->and(DataKol::where('channel', 'Instagram')->first()->kol_key)->toBe('windahbasudara');
});

/** Upload CSV palsu; Livewire butuh Http\Testing\File (punya properti ->name). */
function csvUpload(string $isi): TestingFile
{
    return UploadedFile::fake()->createWithContent('import.csv', $isi);
}

it('mode bulk membaca CSV dan membuat KOL baru per baris', function () {
    [$kol, $tombol] = kolDenganTombolTambahChannel();

    app()->bind(KolProfileImporter::class, fn() => importerStub([
        'akun-a' => fakeProfile('akun_a', 200_000),
        'akun-b' => fakeProfile('akun_b', 300_000),
    ]));

    Livewire::test(EditDataKol::class, ['record' => $kol->id])
        ->callAction($tombol, [
            'mode' => 'bulk',
            'csv' => [csvUpload("channel,link\nInstagram,akun-a\nTiktok,akun-b\n")],
        ]);

    // 3 baris: windah (TikTok) + 2 KOL baru dengan username ASLI masing-masing.
    expect(DataKol::count())->toBe(3)
        ->and(DataKol::oneRowPerKol()->pluck('username')->sort()->values()->all())
        ->toBe(['akun_a', 'akun_b', 'windahbasudara']);
});

it('menahan modal terbuka dan menampilkan rincian ketika ada baris yang gagal', function () {
    [$kol, $tombol] = kolDenganTombolTambahChannel();

    app()->bind(KolProfileImporter::class, fn() => importerStub([
        'akun-a' => fakeProfile('akun_a', 200_000),
    ]));

    Livewire::test(EditDataKol::class, ['record' => $kol->id])
        ->callAction($tombol, [
            'mode' => 'bulk',
            'csv' => [csvUpload("channel,link\nInstagram,akun-a\nInstagram,akun-hilang\n")],
        ])
        // halt() → modal TIDAK ditutup, jadi rincian kegagalannya sempat dibaca.
        // (Kalau semua sukses modal ditutup + redirect; itu kasus test di atas.)
        ->assertActionMounted($tombol);

    // Yang berhasil tetap tersimpan — satu baris gagal tidak membatalkan sisanya.
    expect(DataKol::where('username', 'akun_a')->exists())->toBeTrue()
        ->and(DataKol::count())->toBe(2);
});

it('menampilkan baris gagal beserta alasannya di rincian hasil', function () {
    $hasil = importerStub(['akun-a' => fakeProfile('akun_a', 200_000)])->importMany([
        ['channel' => 'Instagram', 'link_userprofile' => 'akun-a'],
        ['channel' => 'Instagram', 'link_userprofile' => 'akun-hilang'],
    ]);
    $hasil['errors'] = ['Baris 4: channel "Telegram" tidak dikenal.'];

    $html = view('filament.data-kols.import-result', [
        'hasil' => $hasil,
        'reloadUrl' => '/office/data-kol/1/edit',
    ])->render();

    expect($html)
        ->toContain('1 berhasil')
        ->toContain('1 gagal')
        ->toContain('akun-hilang')
        ->toContain('Profil tidak ditemukan.')     // alasan gagalnya, bukan cuma jumlah
        ->toContain('Telegram')                    // error parsing CSV ikut tampil
        ->toContain('akun_a');
});

it('mem-parse baris: header dilewati, ejaan bebas kapital, channel tak dikenal dilaporkan', function () {
    $hasil = KolProfileImporter::parseRows([
        ['channel', 'link'],
        ['Instagram', 'akun-a'],
        ['tiktok', 'akun-b'],
        ['Telegram', 'akun-c'],
        ['', ''],
    ]);

    expect($hasil['rows'])->toBe([
        ['channel' => 'Instagram', 'link_userprofile' => 'akun-a'],
        // Ejaan bebas huruf besar-kecil, disimpan kanonik.
        ['channel' => 'Tiktok', 'link_userprofile' => 'akun-b'],
    ])->and($hasil['errors'])->toBe(['Baris 4: channel "Telegram" tidak dikenal.']);
});

it('memotong impor di batas MAX_BULK dan memberi tahu berapa yang dilewati', function () {
    $baris = [['channel', 'link']];
    foreach (range(1, KolProfileImporter::MAX_BULK + 3) as $i) {
        $baris[] = ['Instagram', "akun{$i}"];
    }

    $hasil = KolProfileImporter::parseRows($baris);

    expect($hasil['rows'])->toHaveCount(KolProfileImporter::MAX_BULK)
        ->and($hasil['errors'])->toBe(['3 baris terakhir dilewati — maksimal ' . KolProfileImporter::MAX_BULK . ' per impor.']);
});

it('membuat template xlsx dengan dropdown channel di kolom A', function () {
    $path = tempnam(sys_get_temp_dir(), 'tpl') . '.xlsx';
    file_put_contents($path, KolProfileImporter::templateXlsx());

    $sheet = IOFactory::load($path)->getActiveSheet();

    expect($sheet->getCell('A1')->getValue())->toBe('channel')
        ->and($sheet->getCell('B1')->getValue())->toBe('link');

    // Dropdown terpasang di semua baris data, bukan cuma baris pertama.
    $daftar = '"' . implode(',', array_keys(KolProfileImporter::SCRAPABLE)) . '"';

    foreach ([2, KolProfileImporter::MAX_BULK + 1] as $baris) {
        $validasi = $sheet->getCell("A{$baris}")->getDataValidation();

        expect($validasi->getType())->toBe(DataValidation::TYPE_LIST)
            ->and($validasi->getShowDropDown())->toBeTrue()
            ->and($validasi->getFormula1())->toBe($daftar);
    }

    // Template harus bisa dibaca balik oleh parser-nya sendiri tanpa error.
    expect(KolProfileImporter::parseFile($path)['errors'])->toBe([]);

    unlink($path);
});

it('menyediakan route unduh template yang mengirim file xlsx', function () {
    $this->get(route('data-kol.import-template'))
        ->assertOk()
        ->assertDownload('template-import-kol.xlsx');
});

it('tombol refresh mengambil ulang data channel dan memberi tahu selisihnya', function () {
    [$kol] = kolDenganTombolTambahChannel();   // TikTok, 5.000.000 followers

    app()->bind(KolProfileImporter::class, fn() => importerStub([
        'https://tiktok.com/@windahbasudara' => fakeProfile('windahbasudara', 5_250_000),
    ]));

    Livewire::test(EditDataKol::class, ['record' => $kol->id])
        ->call('refreshChannel', $kol->id)
        ->assertNotified('Tiktok diperbarui');

    expect((int) $kol->refresh()->followers)->toBe(5_250_000);
});

/** Profil yang engagement-nya nol, dengan jumlah postingan yang bisa diatur. */
function profilTanpaEngagement(int $jumlahPost): array
{
    return array_merge(fakeProfile('windahbasudara', 37_400), [
        'total_engagements' => 0,
        'engagement_rate' => 0,
        'average_impressions' => 2_805,
        'media_count' => $jumlahPost,
    ]);
}

it('membedakan akun tanpa postingan dari API yang tidak memberi data per-post', function (
    int $jumlahPost,
    string $catatan,
) {
    [$kol] = kolDenganTombolTambahChannel();

    app()->bind(KolProfileImporter::class, fn() => importerStub([
        'https://tiktok.com/@windahbasudara' => profilTanpaEngagement($jumlahPost),
    ]));

    Livewire::test(EditDataKol::class, ['record' => $kol->id])
        ->call('refreshChannel', $kol->id);

    FilamentNotification::assertNotified(
        FilamentNotification::make()
            ->title('Tiktok diperbarui')
            ->body('37,400 followers (−4,962,600) · ER 0.00% · 2,805 avg impressions' . $catatan)
            ->warning()
            ->persistent()   // peringatannya jangan hilang sendiri sebelum sempat dibaca
    );
})->with([
    // Kasus @windahbasudara: akun benar-benar kosong, jadi 0 itu data yang benar.
    'akun kosong' => [0, ' — Akun ini belum punya postingan, jadi engagement & ER memang 0.'],
    // Punya 340 postingan tapi engagement 0 → yang rusak API-nya, bukan akunnya.
    'API bisu' => [340, ' — Engagement & ER tidak terhitung: data per-post tidak tersedia dari API,'
        . ' angka 0 di sini bukan hasil pengukuran.'],
]);

it('tidak memperingatkan saat engagement benar-benar terhitung', function () {
    [$kol] = kolDenganTombolTambahChannel();

    app()->bind(KolProfileImporter::class, fn() => importerStub([
        'https://tiktok.com/@windahbasudara' => fakeProfile('windahbasudara', 5_250_000),
    ]));

    Livewire::test(EditDataKol::class, ['record' => $kol->id])
        ->call('refreshChannel', $kol->id);

    FilamentNotification::assertNotified(
        FilamentNotification::make()
            ->title('Tiktok diperbarui')
            ->body('5,250,000 followers (+250,000) · ER 6.50% · 3,200,000 avg impressions')
            ->success()
    );
});

it('tombol refresh melaporkan kegagalan tanpa merusak data lama', function () {
    [$kol] = kolDenganTombolTambahChannel();

    app()->bind(KolProfileImporter::class, fn() => importerStub([]));   // semua gagal

    Livewire::test(EditDataKol::class, ['record' => $kol->id])
        ->call('refreshChannel', $kol->id)
        ->assertNotified('Gagal memperbarui Tiktok');

    expect((int) $kol->refresh()->followers)->toBe(5_000_000);
});

it('menyimpan URL profil kanonik, bukan mentahan yang di-paste', function () {
    // Kasus nyata: user paste tanpa "@", dan tiktok.com/<user> membuka halaman 404.
    $kol = (new KolProfileImporter())->save(
        fakeProfile('windahbasudara'),
        'Tiktok',
        'https://www.tiktok.com/windahbasudara',
    );

    expect($kol->link_userprofile)->toBe('https://www.tiktok.com/@windahbasudara');
});

it('memetakan URL kanonik per channel', function (string $channel, string $harapan) {
    expect(KolProfileImporter::canonicalUrl($channel, '@budi'))->toBe($harapan);
})->with([
    ['Instagram', 'https://www.instagram.com/budi/'],
    ['Tiktok', 'https://www.tiktok.com/@budi'],
    ['Youtube Channels', 'https://www.youtube.com/@budi'],
    ['Youtube Shorts', 'https://www.youtube.com/@budi'],
]);

it('menghapus channel beserta rate card-nya', function () {
    [$kol] = kolDenganTombolTambahChannel();

    $instagram = (new KolProfileImporter())->save(
        fakeProfile('windahbasudara', 3_500_000),
        'Instagram',
        'https://instagram.com/windahbasudara',
    );
    KolRateCard::create(['data_kol_id' => $instagram->id, 'channel' => 'Instagram', 'rate' => 15_000_000, 'valid_from' => now()]);

    Livewire::test(EditDataKol::class, ['record' => $kol->id])
        ->call('deleteChannel', $instagram->id)
        ->assertNotified('Channel Instagram dihapus');

    expect(DataKol::whereKey($instagram->id)->exists())->toBeFalse()
        // cascadeOnDelete di kol_rate_cards.
        ->and(KolRateCard::count())->toBe(0)
        ->and(DataKol::whereKey($kol->id)->exists())->toBeTrue();
});

it('menolak menghapus channel yang sudah dipakai SPK', function () {
    [$kol] = kolDenganTombolTambahChannel();

    BvSPK::create([
        'spk_number' => 'BVN/SPK/2026/08/091',
        'tanggal_perjanjian' => now()->toDateString(),
        'data_kol_id' => $kol->id,
        'pihak_kedua_nama_lengkap' => 'Windah Basudara',
        'nominal_kesepakatan' => 15_000_000,
        'status' => 'draft',
    ]);

    Livewire::test(EditDataKol::class, ['record' => $kol->id])
        ->call('deleteChannel', $kol->id)
        ->assertNotified('Tiktok tidak bisa dihapus');

    // FK-nya nullOnDelete, jadi tanpa penjagaan ini SPK kehilangan rujukan diam-diam.
    expect(DataKol::whereKey($kol->id)->exists())->toBeTrue()
        ->and(BvSPK::first()->data_kol_id)->toBe($kol->id);
});

it('menolak menghapus channel milik KOL lain', function () {
    [$kol] = kolDenganTombolTambahChannel();

    $orangLain = DataKol::create([
        'username' => 'awkarin',
        'channel' => 'Instagram',
        'link_userprofile' => 'https://instagram.com/awkarin',
        'followers' => 2_100_000,
    ]);

    expect(fn() => Livewire::test(EditDataKol::class, ['record' => $kol->id])
        ->call('deleteChannel', $orangLain->id))
        ->toThrow(ModelNotFoundException::class);

    expect(DataKol::whereKey($orangLain->id)->exists())->toBeTrue();
});

it('menolak refresh channel milik KOL lain', function () {
    [$kol] = kolDenganTombolTambahChannel();

    $orangLain = DataKol::create([
        'username' => 'awkarin',
        'channel' => 'Instagram',
        'link_userprofile' => 'https://instagram.com/awkarin',
        'followers' => 2_100_000,
    ]);

    // id dari klien tidak dipercaya: refresh dibatasi ke channel milik username ini.
    expect(fn() => Livewire::test(EditDataKol::class, ['record' => $kol->id])
        ->call('refreshChannel', $orangLain->id))
        ->toThrow(ModelNotFoundException::class);

    expect((int) $orangLain->refresh()->followers)->toBe(2_100_000);
});

it('membaca file xlsx yang diunggah, bukan cuma CSV', function () {
    [$kol, $tombol] = kolDenganTombolTambahChannel();

    app()->bind(KolProfileImporter::class, fn() => importerStub([
        'akun-a' => fakeProfile('akun_a', 200_000),
    ]));

    // Bangun xlsx sungguhan lewat template + isi 1 baris.
    $path = tempnam(sys_get_temp_dir(), 'imp') . '.xlsx';
    file_put_contents($path, KolProfileImporter::templateXlsx());
    $spreadsheet = IOFactory::load($path);
    $spreadsheet->getActiveSheet()->fromArray([['Instagram', 'akun-a']], null, 'A2');
    (new XlsxWriter($spreadsheet))->save($path);

    Livewire::test(EditDataKol::class, ['record' => $kol->id])
        ->callAction($tombol, [
            'mode' => 'bulk',
            'csv' => [UploadedFile::fake()->createWithContent('import.xlsx', (string) file_get_contents($path))],
        ]);

    expect(DataKol::where('username', 'akun_a')->exists())->toBeTrue();

    unlink($path);
});

it('mengekstrak username dari link profil apa pun bentuknya', function () {
    expect(KolProfileImporter::usernameFromUrl('https://www.instagram.com/awkarin'))->toBe('awkarin')
        ->and(KolProfileImporter::usernameFromUrl('https://www.instagram.com/awkarin/'))->toBe('awkarin')
        ->and(KolProfileImporter::usernameFromUrl('https://www.tiktok.com/@windahbasudara'))->toBe('windahbasudara')
        // Link tanpa "@" (yang dipakai di Media Plan) dan link dengan path lanjutan.
        ->and(KolProfileImporter::usernameFromUrl('https://www.tiktok.com/windahbasudara'))->toBe('windahbasudara')
        ->and(KolProfileImporter::usernameFromUrl('https://www.tiktok.com/@justeen/video/12345'))->toBe('justeen')
        ->and(KolProfileImporter::usernameFromUrl('https://www.youtube.com/@kanal'))->toBe('kanal')
        ->and(KolProfileImporter::usernameFromUrl('https://www.instagram.com/'))->toBeNull()
        ->and(KolProfileImporter::usernameFromUrl(null))->toBeNull();
});
