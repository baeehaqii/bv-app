<?php

use App\Filament\Resources\DataKols\Pages\ListDataKols;
use App\Models\BvSPK;
use App\Models\DataKol;
use App\Models\KolRateCard;
use App\Models\User;
use Filament\Actions\Testing\TestAction;
use Filament\Facades\Filament;
use Illuminate\Support\Facades\Gate;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;

/**
 * data_kols menyimpan 1 BARIS PER CHANNEL. Halaman daftar harus menampilkan
 * 1 baris per ORANG (username) dengan angka gabungan — itu yang mudah patah
 * kalau query/agregatnya diubah, jadi dijaga di sini.
 */
beforeEach(function () {
    Role::firstOrCreate(['name' => 'super_admin']);
    $user = User::create([
        'name' => 'KOL Admin',
        'email' => 'kol-admin@bvnetwork.net',
        'password' => bcrypt('password'),
    ]);
    $user->syncRoles(['super_admin']);
    $this->actingAs($user);
    Gate::before(fn() => true);
});

/** KOL 2 channel (IG 3,5jt + TikTok 5jt) + 1 KOL single-channel sebagai pembanding. */
function kolMultiChannel(): DataKol
{
    DataKol::create([
        'username' => 'windahbasudara',
        'channel' => 'Instagram',
        'link_userprofile' => 'https://instagram.com/windahbasudara',
        'followers' => 3_500_000,
        'tier' => 'Mega',
        'engagement_rate' => 3.20,
        'engagements' => 112_000,
        'impressions' => 1_250_000,
        'category' => ['Gamers & Lifestyle'],
        'terakhir_update' => '2026-07-01',
    ]);

    $tiktok = DataKol::create([
        'username' => 'windahbasudara',
        'channel' => 'Tiktok',
        'link_userprofile' => 'https://tiktok.com/@windahbasudara',
        'followers' => 5_000_000,
        'tier' => 'Mega',
        'engagement_rate' => 6.50,
        'engagements' => 88_000,
        'impressions' => 3_200_000,
        'category' => ['Comedy'],
        'terakhir_update' => '2026-07-25',
    ]);

    DataKol::create([
        'username' => 'awkarin',
        'channel' => 'Instagram',
        'link_userprofile' => 'https://instagram.com/awkarin',
        'followers' => 2_100_000,
        'tier' => 'Mega',
        'engagement_rate' => 2.80,
        'engagements' => 40_000,
        'impressions' => 640_000,
        'terakhir_update' => '2026-07-10',
    ]);

    return $tiktok;
}

it('menampilkan satu baris per KOL, bukan per channel', function () {
    kolMultiChannel();

    expect(DataKol::count())->toBe(3);                    // 3 baris channel…
    expect(DataKol::oneRowPerKol()->count())->toBe(2);    // …tapi 2 orang

    Livewire::test(ListDataKols::class)
        ->assertCanSeeTableRecords(DataKol::oneRowPerKol()->get())
        ->assertCountTableRecords(2);
});

it('menjumlahkan followers & engagements lintas channel di baris KOL', function () {
    kolMultiChannel();

    $windah = DataKol::oneRowPerKol()
        ->withSum('channels', 'followers')
        ->withSum('channels', 'engagements')
        ->withAvg('channels', 'engagement_rate')
        ->withAvg('channels', 'impressions')
        ->withMax('channels', 'terakhir_update')
        ->where('username', 'windahbasudara')
        ->first();

    expect((int) $windah->channels_sum_followers)->toBe(8_500_000)
        ->and((int) $windah->channels_sum_engagements)->toBe(200_000)
        ->and(round((float) $windah->channels_avg_engagement_rate, 2))->toBe(4.85)
        // Impressions DIRATA-RATA, bukan dijumlah: (1.250.000 + 3.200.000) / 2.
        ->and((int) $windah->channels_avg_impressions)->toBe(2_225_000)
        // Last Update = update TERBARU di antara channel-nya.
        ->and(substr((string) $windah->channels_max_terakhir_update, 0, 10))->toBe('2026-07-25');
});

it('memilih channel dengan followers terbanyak sebagai baris yang dibuka Detail', function () {
    kolMultiChannel();

    // TikTok (5jt) dibuat SETELAH Instagram (3,5jt) — kalau wakilnya dipilih
    // dari id terbesar hasilnya kebetulan sama, jadi dibalik di KOL kedua ini:
    // channel besar dibuat duluan, channel kecil menyusul.
    $besar = DataKol::create([
        'username' => 'urutanterbalik',
        'channel' => 'Instagram',
        'link_userprofile' => 'https://instagram.com/urutanterbalik',
        'followers' => 900_000,
    ]);

    DataKol::create([
        'username' => 'urutanterbalik',
        'channel' => 'Tiktok',
        'link_userprofile' => 'https://tiktok.com/@urutanterbalik',
        'followers' => 12_000,
    ]);

    $wakil = DataKol::oneRowPerKol()->where('username', 'urutanterbalik')->get();

    expect($wakil)->toHaveCount(1)
        ->and($wakil->first()->id)->toBe($besar->id)
        ->and($wakil->first()->channel)->toBe('Instagram');

    expect(DataKol::oneRowPerKol()->where('username', 'windahbasudara')->first()->channel)
        ->toBe('Tiktok');   // 5jt > 3,5jt
});

it('menghitung tier dari followers gabungan, bukan per channel', function () {
    // 2 channel @600rb: per channel keduanya Macro, digabung jadi Mega.
    foreach (['Instagram', 'Tiktok'] as $channel) {
        DataKol::create([
            'username' => 'duamacro',
            'channel' => $channel,
            'link_userprofile' => 'https://example.test/duamacro',
            'followers' => 600_000,
            'tier' => 'Macro',
        ]);
    }

    $kol = DataKol::oneRowPerKol()->withSum('channels', 'followers')->first();

    expect((int) $kol->channels_sum_followers)->toBe(1_200_000)
        ->and(DataKol::tierFor((int) $kol->channels_sum_followers))->toBe('Mega');
});

it('memetakan followers ke tier sesuai ambang service scraping', function (int $followers, string $tier) {
    expect(DataKol::tierFor($followers))->toBe($tier);
})->with([
    [0, 'Mini'],
    [999, 'Mini'],
    [1_000, 'Nano'],
    [9_999, 'Nano'],
    [10_000, 'Micro'],
    [99_999, 'Micro'],
    [100_000, 'Macro'],
    [999_999, 'Macro'],
    [1_000_000, 'Mega'],
]);

it('mengumpulkan SPK dan rate card dari semua channel milik KOL yang sama', function () {
    $tiktok = kolMultiChannel();
    $instagram = DataKol::where('username', 'windahbasudara')->where('channel', 'Instagram')->first();

    KolRateCard::create(['data_kol_id' => $tiktok->id, 'channel' => 'Tiktok', 'rate' => 25_000_000, 'valid_from' => now()]);
    KolRateCard::create(['data_kol_id' => $instagram->id, 'channel' => 'Instagram', 'rate' => 15_000_000, 'valid_from' => now()]);

    BvSPK::create([
        'spk_number' => 'BVN/SPK/2026/08/090',
        'tanggal_perjanjian' => now()->toDateString(),
        'data_kol_id' => $instagram->id,
        'pihak_kedua_nama_lengkap' => 'Windah Basudara',
        'nominal_kesepakatan' => 15_000_000,
        'status' => 'draft',
    ]);

    $baris = DataKol::oneRowPerKol()
        ->with(['channels.rateCards', 'channels.spks'])
        ->where('username', 'windahbasudara')
        ->first();

    // SPK menempel di baris channel Instagram, tapi harus ikut terhitung
    // walau baris wakil di daftar adalah channel TikTok (followers terbanyak).
    expect($baris->channels->flatMap->spks)->toHaveCount(1)
        ->and($baris->channels->flatMap->rateCards)->toHaveCount(2);

    // Tombolnya benar-benar ada di baris tabel…
    Livewire::test(ListDataKols::class)
        ->assertActionExists(TestAction::make('spk')->table($baris))
        ->assertActionExists(TestAction::make('rate_card')->table($baris));

    // …dan isi modalnya ter-render (blade-nya penuh loop & pemanggilan getUrl).
    Filament::setCurrentPanel('office');

    $spkHtml = view('filament.data-kols.spk-list', [
        'spks' => $baris->channels->flatMap->spks->sortByDesc('tanggal_perjanjian'),
    ])->render();

    $rateHtml = view('filament.data-kols.rate-cards', ['record' => $baris])->render();

    expect($spkHtml)->toContain('BVN/SPK/2026/08/090')->toContain('Rp15.000.000')
        ->and($rateHtml)->toContain('Rp25.000.000')->toContain('Rp15.000.000');
});

it('memfilter berdasarkan channel yang dimiliki, bukan baris wakilnya', function () {
    kolMultiChannel();

    // awkarin cuma punya Instagram, windah punya keduanya.
    Livewire::test(ListDataKols::class)
        ->filterTable('channel', ['Tiktok'])
        ->assertCountTableRecords(1)
        ->filterTable('channel', ['Instagram'])
        ->assertCountTableRecords(2);

    // Baris wakil windahbasudara adalah TikTok (followers terbanyak), tapi filter Instagram
    // tetap harus memunculkannya karena dia punya channel itu.
    Livewire::test(ListDataKols::class)
        ->filterTable('channel', ['Instagram'])
        ->assertCanSeeTableRecords(DataKol::oneRowPerKol()->where('username', 'windahbasudara')->get());
});

it('memfilter tier memakai followers gabungan', function () {
    kolMultiChannel();

    DataKol::create(['username' => 'kecil', 'channel' => 'Instagram', 'link_userprofile' => 'https://instagram.com/kecil', 'followers' => 5_000, 'tier' => 'Nano']);

    Livewire::test(ListDataKols::class)
        ->filterTable('tier', ['Mega'])
        ->assertCountTableRecords(2)          // windah (8,5jt) + awkarin (2,1jt)
        ->filterTable('tier', ['Nano'])
        ->assertCountTableRecords(1);
});
