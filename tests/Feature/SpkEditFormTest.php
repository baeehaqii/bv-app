<?php

use App\Filament\Resources\Spks\Pages\EditSpk;
use App\Models\BvSPK;
use App\Models\DataKol;
use App\Models\User;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;

/**
 * Halaman Edit SPK: Repeater `clauses` menyimpan map ber-kunci di DB tapi
 * bekerja sebagai list di form. Yang dijaga di sini: roundtrip lewat Livewire
 * benar-benar jalan — formatStateUsing/dehydrateStateUsing pada Repeater itu
 * jalur yang mudah patah dan tidak terlihat dari unit test model saja.
 */
beforeEach(function () {
    Role::firstOrCreate(['name' => 'super_admin']);
    $user = User::create([
        'name' => 'SPK Admin',
        'email' => 'spk-admin@bvnetwork.net',
        'password' => bcrypt('password'),
    ]);
    $user->syncRoles(['super_admin']);
    $this->actingAs($user);
    Gate::before(fn() => true);
});

function spkUntukEdit(array $clauses = []): BvSPK
{
    $kol = DataKol::create([
        'username' => 'justeenff',
        'channel' => 'TikTok',
        'link_userprofile' => 'https://tiktok.com/@justeenff',
        'full_name' => 'M. Farhan Fava Rizky',
        'nik' => '3201132001000006',
        'address' => 'Jl. Tugu 4 Kav. 321, Kota Bekasi',
        'bank_account_name' => 'M. Farhan Fava Rizky',
        'bank_account_number' => '901323084234',
        'bank_name' => 'SeaBank',
    ]);

    return BvSPK::create([
        'spk_number' => 'BVN/SPK/2026/08/010',
        'tanggal_perjanjian' => '2026-08-01',
        'data_kol_id' => $kol->id,
        'pihak_kedua_nama_lengkap' => 'M. Farhan Fava Rizky',
        'pihak_kedua_nama_akun' => 'justeenff (TikTok)',
        'pihak_kedua_nik' => $kol->nik,
        'pihak_kedua_alamat' => $kol->address,
        'nama_campaign' => 'GoPay Gamers',
        'timeline_kerja_sama' => 'Agustus 2026',
        'sow_disepakati' => '1x TikTok Video',
        'nominal_kesepakatan' => 516_000,
        'nominal_terbilang' => BvSPK::terbilang(516_000),
        'clauses' => $clauses ?: BvSPK::defaultClauses(),
        'status' => 'draft',
    ]);
}

it('mengisi Repeater klausul sebagai list lengkap dan urut sesuai CLAUSES', function () {
    $spk = spkUntukEdit();

    $state = Livewire::test(EditSpk::class, ['record' => $spk->getRouteKey()])
        ->assertFormExists()
        ->assertFormFieldExists('clauses')
        ->assertFormFieldExists('addons')
        ->get('data')['clauses'];

    // Repeater meng-key barisnya dengan UUID; yang penting isi & urutannya.
    expect(array_column(array_values($state), 'key'))->toBe(array_keys(BvSPK::CLAUSES));
});

it('menyimpan klausul dari Repeater kembali menjadi map ber-kunci di database', function () {
    $spk = spkUntukEdit();

    $page = Livewire::test(EditSpk::class, ['record' => $spk->getRouteKey()]);
    $clauses = $page->get('data')['clauses'];

    // Matikan eksklusivitas & ganti redaksi denda, seperti yang dilakukan user.
    foreach ($clauses as $uuid => $row) {
        if ($row['key'] === 'eksklusivitas') {
            $clauses[$uuid]['enabled'] = false;
        }
        if ($row['key'] === 'denda') {
            $clauses[$uuid]['text'] = 'Denda 2% per hari keterlambatan.';
        }
    }

    $page->set('data.clauses', $clauses)->call('save')->assertHasNoFormErrors();

    $spk->refresh();

    // Tersimpan sebagai map, bukan list bernomor.
    expect(array_keys($spk->clauses))->toBe(array_keys(BvSPK::CLAUSES))
        ->and($spk->clauseEnabled('eksklusivitas'))->toBeFalse()
        ->and($spk->clauseEnabled('denda'))->toBeTrue()
        ->and($spk->clauseText('denda'))->toBe('Denda 2% per hari keterlambatan.');
});

it('menghasilkan PDF tanpa ayat eksklusivitas setelah disimpan dari form', function () {
    $spk = spkUntukEdit();

    $page = Livewire::test(EditSpk::class, ['record' => $spk->getRouteKey()]);
    $clauses = $page->get('data')['clauses'];

    foreach ($clauses as $uuid => $row) {
        if ($row['key'] === 'eksklusivitas') {
            $clauses[$uuid]['enabled'] = false;
        }
    }

    $page->set('data.clauses', $clauses)->call('save')->assertHasNoFormErrors();

    $html = view('pdf.kol-contract',
        App\Http\Controllers\KolContractController::prepareData($spk->fresh()))->render();

    expect($html)->not->toContain('mempromosikan kompetitor');
});

it('tidak membocorkan kunci klausul asing yang disuntik lewat form', function () {
    $spk = spkUntukEdit();

    $page = Livewire::test(EditSpk::class, ['record' => $spk->getRouteKey()]);
    $clauses = $page->get('data')['clauses'];
    $clauses['disuntik'] = ['key' => 'klausul_palsu', 'enabled' => true, 'text' => 'Nakal'];

    $page->set('data.clauses', $clauses)->call('save')->assertHasNoFormErrors();

    expect(array_keys($spk->fresh()->clauses))->toBe(array_keys(BvSPK::CLAUSES));
});

it('menonaktifkan seluruh form ketika SPK sudah ditandatangani', function () {
    Storage::fake('public');

    $spk = spkUntukEdit();
    $spk->signByKol(
        'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mP8z8DwHwAFAAH/q842iQAAAABJRU5ErkJggg==',
        '127.0.0.1'
    );

    Livewire::test(EditSpk::class, ['record' => $spk->getRouteKey()])
        ->assertFormFieldIsDisabled('nominal_kesepakatan')
        ->assertFormFieldIsDisabled('sow_disepakati')
        ->assertFormFieldIsDisabled('clauses');
});
