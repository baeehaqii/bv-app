<?php

use App\Filament\Resources\DataKols\Pages\ListDataKols;
use App\Models\DataKol;
use App\Models\User;
use Filament\Actions\Testing\TestAction;
use Illuminate\Support\Facades\Gate;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;

/**
 * Menautkan channel yang username-nya beda antar platform.
 *
 * Sebelum ada `kol_key`, satu KOL dikenali dari `username`, jadi orang yang
 * handle-nya beda tiap platform terbaca sebagai dua KOL berbeda.
 */
function kolBaris(string $username, string $channel, int $followers = 1_000): DataKol
{
    return DataKol::create([
        'username' => $username,
        'channel' => $channel,
        'followers' => $followers,
        'link_userprofile' => 'https://example.test/' . $username,
    ]);
}

function groupingUser(): User
{
    Role::firstOrCreate(['name' => 'super_admin']);

    return tap(User::create([
        'name' => 'Grouping Admin',
        'email' => 'grouping-' . uniqid() . '@bvnetwork.net',
        'password' => bcrypt('password'),
    ]))->syncRoles(['super_admin']);
}

it('mengisi kol_key dari username supaya pengelompokan lama tidak berubah', function () {
    $ig = kolBaris('winda', 'Instagram');
    $tt = kolBaris('winda', 'Tiktok');

    expect($ig->kol_key)->toBe('winda')
        ->and($ig->channels()->count())->toBe(2)
        ->and($tt->channels()->pluck('channel')->sort()->values()->all())->toBe(['Instagram', 'Tiktok']);
});

it('username beda antar platform tetap dua KOL sampai digabungkan', function () {
    $ig = kolBaris('windabasudara_', 'Instagram', 40_000);
    $tt = kolBaris('winda_basudara', 'Tiktok', 80_000);

    expect($ig->channels()->count())->toBe(1)
        ->and(DataKol::oneRowPerKol()->count())->toBe(2);

    // Digabungkan: kunci grup ikut baris dengan followers terbanyak.
    DataKol::whereIn('kol_key', ['windabasudara_', 'winda_basudara'])
        ->update(['kol_key' => 'winda_basudara']);

    expect($ig->fresh()->channels()->count())->toBe(2)
        ->and(DataKol::oneRowPerKol()->count())->toBe(1)
        // Username asli tiap channel TIDAK ikut berubah.
        ->and($ig->fresh()->username)->toBe('windabasudara_')
        ->and($ig->fresh()->crossChannelSummary()['followers'])->toBe(120_000);
});

it('aksi Gabungkan menyatukan baris terpilih di KOL Data', function () {
    Gate::before(fn() => true);

    $ig = kolBaris('windabasudara_', 'Instagram', 40_000);
    $tt = kolBaris('winda_basudara', 'Tiktok', 80_000);

    Livewire::actingAs(groupingUser())
        ->test(ListDataKols::class)
        ->set('selectedTableRecords', [$ig->id, $tt->id])
        ->callAction(TestAction::make('gabungkan')->table()->bulk());

    expect($ig->fresh()->kol_key)->toBe('winda_basudara')
        ->and($tt->fresh()->kol_key)->toBe('winda_basudara')
        ->and(DataKol::oneRowPerKol()->count())->toBe(1);
});

it('aksi Pisahkan mengembalikan channel jadi KOL sendiri', function () {
    Gate::before(fn() => true);

    $ig = kolBaris('windabasudara_', 'Instagram', 40_000);
    $tt = kolBaris('winda_basudara', 'Tiktok', 80_000);
    DataKol::query()->update(['kol_key' => 'winda_basudara']);

    Livewire::actingAs(groupingUser())
        ->test(ListDataKols::class)
        ->callAction(
            TestAction::make('pisahkan')->table($tt->fresh()),
            data: ['ids' => [$ig->id]],
        );

    expect($ig->fresh()->kol_key)->toBe('windabasudara_')
        ->and(DataKol::oneRowPerKol()->count())->toBe(2);
});
