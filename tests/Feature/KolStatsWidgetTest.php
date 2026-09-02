<?php

use App\Filament\Resources\DataKols\Widgets\KolStatsWidget;
use App\Models\DataKol;
use App\Models\User;
use Illuminate\Support\Facades\Gate;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;

/**
 * Widget ringkasan di halaman KOL Data: total KOL + sebaran per channel.
 *
 * data_kols menyimpan 1 BARIS PER CHANNEL; satu KOL dikenali dari kol_key.
 * Jadi "berapa KOL di Instagram" bukan sekadar menghitung baris.
 */
function kolStatBaris(string $username, string $channel, ?string $kolKey = null): DataKol
{
    return DataKol::create([
        'username' => $username,
        'channel' => $channel,
        'kol_key' => $kolKey ?? $username,
        'link_userprofile' => 'https://example.test/' . $username,
        'followers' => 1000,
    ]);
}

function statKolUser(): User
{
    Role::firstOrCreate(['name' => 'super_admin']);

    return tap(User::create([
        'name' => 'Widget Admin',
        'email' => 'widget-' . uniqid() . '@bvnetwork.net',
        'password' => bcrypt('password'),
    ]))->syncRoles(['super_admin']);
}

/** @return array<string, string> label => nilai */
function statKolWidget(): array
{
    $widget = Livewire::actingAs(statKolUser())->test(KolStatsWidget::class)->instance();

    $stats = tap(new ReflectionMethod($widget, 'getStats'), fn($m) => $m->setAccessible(true))
        ->invoke($widget);

    return collect($stats)->mapWithKeys(fn($s) => [$s->getLabel() => $s->getValue()])->all();
}

it('menampilkan total KOL dan lima channel', function () {
    Gate::before(fn() => true);

    expect(array_keys(statKolWidget()))->toBe([
        'Total KOL',
        'KOL Channel Instagram',
        'KOL Channel Tiktok',
        'KOL Channel Threads',
        'KOL Channel Youtube',
        'KOL Channel X',
    ]);
});

it('menghitung KOL per channel, bukan jumlah baris', function () {
    Gate::before(fn() => true);

    kolStatBaris('adip', 'Instagram');
    kolStatBaris('adliyy', 'Instagram');
    kolStatBaris('rio', 'Tiktok');

    // Satu orang dengan dua channel: 3 baris, tapi tetap 1 KOL di YouTube.
    kolStatBaris('sastra_yt', 'Youtube Channels', kolKey: 'sastra');
    kolStatBaris('sastra_shorts', 'Youtube Shorts', kolKey: 'sastra');
    kolStatBaris('sastra_ig', 'Instagram', kolKey: 'sastra');

    $stat = statKolWidget();

    expect($stat['Total KOL'])->toBe('4 KOL')
        ->and($stat['KOL Channel Instagram'])->toBe('3 KOL')
        ->and($stat['KOL Channel Tiktok'])->toBe('1 KOL')
        ->and($stat['KOL Channel Youtube'])->toBe('1 KOL')
        ->and($stat['KOL Channel X'])->toBe('0 KOL');
});

it('mengenali penulisan channel yang tidak seragam', function () {
    Gate::before(fn() => true);

    // "Thread" tunggal datang dari migrasi spreadsheet, "Threads" dari form.
    kolStatBaris('a', 'Thread');
    kolStatBaris('b', 'Threads');
    kolStatBaris('c', 'TikTok');
    kolStatBaris('d', 'Tik Tok');
    kolStatBaris('e', 'X');
    kolStatBaris('f', 'Twitter');

    $stat = statKolWidget();

    expect($stat['KOL Channel Threads'])->toBe('2 KOL')
        ->and($stat['KOL Channel Tiktok'])->toBe('2 KOL')
        ->and($stat['KOL Channel X'])->toBe('2 KOL');
});

it('melaporkan channel yang tidak masuk kartu mana pun', function () {
    Gate::before(fn() => true);

    kolStatBaris('a', 'Instagram');
    kolStatBaris('b', 'Snapchat');

    $widget = Livewire::actingAs(statKolUser())->test(KolStatsWidget::class)->instance();
    $stats = tap(new ReflectionMethod($widget, 'getStats'), fn($m) => $m->setAccessible(true))
        ->invoke($widget);

    // Dibunyikan, bukan didiamkan: kalau tidak, channel salah ketik hilang
    // begitu saja dan totalnya tidak pernah cocok dengan jumlah kartu.
    expect($stats[0]->getDescription())->toContain('Snapchat');
});

it('daftar KOL diurutkan dari yang terbaru masuk ke sistem', function () {
    Gate::before(fn() => true);

    // created_at sengaja sama persis: 176 baris hasil migrasi masuk dalam satu
    // batch, jadi urutannya harus tetap pasti lewat pemecah seri id.
    $waktu = now()->subDay();
    foreach (['lama_a', 'lama_b'] as $u) {
        kolStatBaris($u, 'Instagram')->forceFill(['created_at' => $waktu])->saveQuietly();
    }
    $baru = kolStatBaris('paling_baru', 'Instagram');
    $baru->forceFill(['created_at' => now()])->saveQuietly();

    $urutan = Livewire::actingAs(statKolUser())
        ->test(\App\Filament\Resources\DataKols\Pages\ListDataKols::class)
        ->instance()
        ->getFilteredSortedTableQuery()
        ->pluck('username')
        ->all();

    expect($urutan)->toBe(['paling_baru', 'lama_b', 'lama_a']);
});

it('KOL Analyzer tidak punya tombol aksi di baris — barisnya sendiri tautannya', function () {
    Gate::before(fn() => true);
    $kol = kolStatBaris('coba', 'Instagram');

    $tabel = Livewire::actingAs(statKolUser())
        ->test(\App\Filament\Pages\KolAnalyzer::class)
        ->instance()
        ->getTable();

    // Tombol bernama "Analyze" di sini pernah bikin salah paham: yang men-scrape
    // adalah Analyze di halaman edit KOL, yang ini cuma membuka tampilan analisis.
    expect($tabel->getRecordActions())->toBe([])
        ->and($tabel->getRecordUrl($kol))->toContain('channelId=' . $kol->id);
});
