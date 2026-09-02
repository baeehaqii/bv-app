<?php

use App\Filament\Resources\DataKols\Pages\EditDataKol;
use App\Models\DataKol;
use App\Models\User;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Http;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;

/**
 * Tombol "Analyze" di halaman edit KOL: satu klik menarik ulang profil SELURUH
 * channel milik KOL itu, lalu isi form ikut segar tanpa perlu reload halaman.
 */
function analyzeUser(): User
{
    Role::firstOrCreate(['name' => 'super_admin']);

    return tap(User::create([
        'name' => 'Analyze Admin',
        'email' => 'analyze-' . uniqid() . '@bvnetwork.net',
        'password' => bcrypt('password'),
    ]))->syncRoles(['super_admin']);
}

function fakeProfilInstagram(int $followers): void
{
    Http::fake([
        '*/v1/instagram/profile*' => Http::response([
            'success' => true,
            'data' => ['user' => [
                'username' => 'kuliner_jogya',
                'full_name' => 'Kuliner Jogya',
                'biography' => 'Kuliner sekitar Jogja',
                'edge_followed_by' => ['count' => $followers],
                'edge_follow' => ['count' => 321],
                'edge_owner_to_timeline_media' => ['count' => 87, 'edges' => []],
            ]],
        ]),
        '*' => Http::response(['success' => true, 'items' => []]),
    ]);
}

it('Analyze menarik ulang profil dan memperbarui angka KOL', function () {
    Gate::before(fn() => true);
    fakeProfilInstagram(158_000);

    // Baris hasil migrasi spreadsheet: followers ada, tapi bio/following/post kosong
    // karena belum pernah di-scrape.
    $kol = DataKol::create([
        'username' => 'kuliner_jogya',
        'channel' => 'Instagram',
        'kol_key' => 'kuliner_jogya',
        'link_userprofile' => 'https://instagram.com/kuliner_jogya',
        'followers' => 1,
    ]);

    Livewire::actingAs(analyzeUser())
        ->test(EditDataKol::class, ['record' => $kol->id])
        ->call('analyzeKol');

    $kol->refresh();

    expect((int) $kol->followers)->toBe(158_000)
        ->and((int) $kol->following_count)->toBe(321)
        ->and((int) $kol->media_count)->toBe(87)
        ->and($kol->biography)->toBe('Kuliner sekitar Jogja')
        ->and($kol->terakhir_update)->not->toBeNull();
});

it('Analyze menyegarkan isi form, bukan cuma database', function () {
    Gate::before(fn() => true);
    fakeProfilInstagram(158_000);

    $kol = DataKol::create([
        'username' => 'kuliner_jogya',
        'channel' => 'Instagram',
        'kol_key' => 'kuliner_jogya',
        'link_userprofile' => 'https://instagram.com/kuliner_jogya',
        'followers' => 1,
    ]);

    $halaman = Livewire::actingAs(analyzeUser())
        ->test(EditDataKol::class, ['record' => $kol->id]);

    $halaman->call('analyzeKol');

    // Daftar FIELD_HASIL_SCRAPING dulu menyebut kolom yang tidak disentuh scraping
    // dan melewatkan followers — angkanya berubah di database tapi form tetap
    // menampilkan yang lama sampai halamannya dimuat ulang.
    $diForm = preg_replace('/\D/', '', (string) $halaman->get('data')['followers']);

    expect((int) $diForm)->toBe(158_000);
});

it('Analyze menarik seluruh channel milik KOL, bukan hanya yang sedang dibuka', function () {
    Gate::before(fn() => true);
    fakeProfilInstagram(158_000);

    $ig = DataKol::create([
        'username' => 'kuliner_jogya', 'channel' => 'Instagram', 'kol_key' => 'kuliner_jogya',
        'link_userprofile' => 'https://instagram.com/kuliner_jogya', 'followers' => 1,
    ]);
    DataKol::create([
        'username' => 'kuliner_jogya_th', 'channel' => 'Threads', 'kol_key' => 'kuliner_jogya',
        'link_userprofile' => 'https://threads.net/@kuliner_jogya', 'followers' => 1,
    ]);

    $halaman = Livewire::actingAs(analyzeUser())->test(EditDataKol::class, ['record' => $ig->id]);

    $halaman->call('analyzeKol');

    // Dua channel, dua panggilan profil — bukan cuma baris yang sedang dibuka.
    Http::assertSentCount(Http::recorded()->count());
    expect(Http::recorded()->count())->toBeGreaterThanOrEqual(2);
});

it('Analyze juga ada di KOL Analyzer, halaman yang paling sering menampilkan data basi', function () {
    Gate::before(fn() => true);
    fakeProfilInstagram(149_000);

    $kol = DataKol::create([
        'username' => 'fira_maringka',
        'channel' => 'Instagram',
        'kol_key' => 'fira_maringka',
        'link_userprofile' => 'https://instagram.com/fira_maringka',
        'followers' => 1,
    ]);

    $halaman = Livewire::actingAs(analyzeUser())
        ->test(\App\Filament\Pages\KolAnalyzer::class, ['channelId' => $kol->id]);

    // Tidak muncul di daftar — di sana belum ada channel yang dipilih.
    $halaman->assertActionVisible('analyze')
        ->callAction('analyze');

    expect((int) $kol->refresh()->followers)->toBe(149_000)
        ->and((int) $kol->following_count)->toBe(321);
});

it('Analyze tidak muncul di daftar KOL Analyzer', function () {
    Gate::before(fn() => true);

    Livewire::actingAs(analyzeUser())
        ->test(\App\Filament\Pages\KolAnalyzer::class)
        ->assertActionHidden('analyze');
});

it('halaman KOL Analyzer langsung menampilkan angka baru, tanpa reload', function () {
    Gate::before(fn() => true);
    fakeProfilInstagram(149_000);

    $kol = DataKol::create([
        'username' => 'fira_maringka',
        'channel' => 'Instagram',
        'kol_key' => 'fira_maringka',
        'link_userprofile' => 'https://instagram.com/fira_maringka',
        'followers' => 1,
    ]);

    Livewire::actingAs(analyzeUser())
        ->test(\App\Filament\Pages\KolAnalyzer::class, ['channelId' => $kol->id])
        ->callAction('analyze')
        // Computed property Livewire di-cache sepanjang request; tanpa dibuang,
        // render setelah scraping masih memakai model lama.
        ->assertSee('149.000')
        ->assertDontSee('Bio belum tersimpan');
});

it('mode rincian merender wadah modal aksi, kalau tidak tombolnya diklik tanpa hasil', function () {
    Gate::before(fn() => true);

    $kol = DataKol::create([
        'username' => 'fira_maringka', 'channel' => 'Instagram', 'kol_key' => 'fira_maringka',
        'link_userprofile' => 'https://instagram.com/fira_maringka', 'followers' => 149_000,
    ]);

    $daftar = Livewire::actingAs(analyzeUser())
        ->test(\App\Filament\Pages\KolAnalyzer::class)->html();

    $rincian = Livewire::actingAs(analyzeUser())
        ->test(\App\Filament\Pages\KolAnalyzer::class, ['channelId' => $kol->id])->html();

    /*
     * <x-filament-panels::page> TIDAK merender modal untuk halaman ber-HasTable —
     * Filament menyerahkannya ke komponen tabel. Di mode rincian tabelnya tidak
     * dirender, jadi wadahnya harus dipasang sendiri; tanpa itu Analyze, Buat
     * Kartu AI, dan Ambil Data Audiens diklik tanpa terjadi apa-apa.
     *
     * Tepat SATU di tiap mode: dua wadah berarti modalnya dirender dobel.
     */
    expect(substr_count($rincian, 'wire:partial="action-modals"'))->toBe(1)
        ->and(substr_count($daftar, 'wire:partial="action-modals"'))->toBe(1);
});
