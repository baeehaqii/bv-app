<?php

use App\Filament\Resources\BvEmployes\Pages\EditBvEmploye;
use App\Filament\Resources\BvEmployes\Pages\ListBvEmployes;
use App\Filament\Resources\Users\Pages\EditUser;
use App\Models\BvEmploye;
use App\Models\User;
use App\Models\BvSalesList;
use App\Models\Division;
use App\Models\Position;
use Database\Seeders\BvEmployeSeeder;
use Database\Seeders\OrganizationStructureSeeder;
use Illuminate\Support\Facades\Gate;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;

/**
 * Field "Karyawan" di form User memindahkan bv_employes.user_id — bukan kolom
 * milik tabel users — jadi penyimpanannya lewat saveRelationshipsUsing.
 */
beforeEach(function () {
    Role::firstOrCreate(['name' => 'super_admin']);
    $admin = User::create([
        'name' => 'Link Admin',
        'email' => 'link-admin@bvnetwork.net',
        'password' => bcrypt('password'),
    ]);
    $admin->syncRoles(['super_admin']);
    $this->actingAs($admin);
    Gate::before(fn () => true);
});

it('menautkan dan melepas data karyawan dari form user', function () {
    $user = User::create([
        'name' => 'Tanpa Karyawan',
        'email' => 'tanpa-karyawan@bvnetwork.net',
        'password' => bcrypt('password'),
    ]);
    $employe = BvEmploye::create([
        'nama_lengkap' => 'Karyawan Uji',
        'email' => 'karyawan-uji@bvnetwork.net',
        'alamat' => 'Jl. Uji No. 1',
        'kota' => 'Jakarta Selatan',
        'provinsi' => 'DKI Jakarta',
        'kode_pos' => '12345',
    ]);

    Livewire::test(EditUser::class, ['record' => $user->getRouteKey()])
        ->fillForm(['bv_employe_id' => $employe->id])
        ->call('save')
        ->assertHasNoFormErrors();

    expect($employe->fresh()->user_id)->toBe($user->id);

    Livewire::test(EditUser::class, ['record' => $user->getRouteKey()])
        ->assertFormSet(['bv_employe_id' => $employe->id])
        ->fillForm(['bv_employe_id' => null])
        ->call('save')
        ->assertHasNoFormErrors();

    expect($employe->fresh()->user_id)->toBeNull();
});

it('seeder karyawan idempoten dan tiap baris punya akun user', function () {
    (new BvEmployeSeeder())->run();
    (new BvEmployeSeeder())->run();

    expect(BvEmploye::count())->toBe(10)
        ->and(BvEmploye::whereNull('user_id')->count())->toBe(0)
        ->and(BvEmploye::where('email', 'gressita@bvnetwork.net')->value('nama_lengkap'))
        ->toBe('Gressita Melli Aryati');
});

it('menyimpan nomor rekening & BPJS berawalan nol apa adanya', function () {
    (new BvEmployeSeeder())->run();

    $fahma = BvEmploye::where('email', 'fahma@bvnetwork.net')->first();

    expect($fahma->bank)->toBe('Mandiri')
        ->and($fahma->no_rekening)->toBe('0060010552572')
        ->and($fahma->bpjs_kesehatan)->toBe('0000055638562')
        // Form hanya mengunggah file NPWP, nomornya tidak ada.
        ->and($fahma->npwp)->toBeNull();
});

it('menyimpan NPWP sebagai angka saja dan menampilkan kolom payroll di tabel', function () {
    (new OrganizationStructureSeeder())->run();

    $employe = BvEmploye::create([
        'nama_lengkap' => 'Karyawan Payroll',
        'email' => 'karyawan-payroll@bvnetwork.net',
        'whatsapp' => '08123456789',
        'alamat' => 'Jl. Uji No. 2',
        'kota' => 'Jakarta Selatan',
        'provinsi' => 'DKI Jakarta',
        'kode_pos' => '12345',
        'position_id' => Position::where('name', 'KOL Specialist')->value('id'),
    ]);

    Livewire::test(EditBvEmploye::class, ['record' => $employe->getRouteKey()])
        ->fillForm(['npwp' => '09.254.294.5-407.000'])
        ->call('save')
        ->assertHasNoFormErrors();

    expect($employe->fresh()->npwp)->toBe('092542945407000');

    // Kolomnya ada di tabel daftar karyawan (default-nya disembunyikan,
    // jadi yang dicek keberadaannya, bukan hasil render-nya).
    Livewire::test(ListBvEmployes::class)
        ->assertTableColumnExists('bank')
        ->assertTableColumnExists('no_rekening')
        ->assertTableColumnExists('npwp')
        ->assertTableColumnExists('bpjs_kesehatan');
});

it('mengadopsi baris sales list lama, bukan bikin PIC kembar', function () {
    (new OrganizationStructureSeeder())->run();
    Division::where('name', 'Sales')->update(['sync_type' => 'sales']);

    $user = User::create([
        'name' => 'Gressita',
        'email' => 'gressita@bvnetwork.net',
        'password' => bcrypt('password'),
    ]);
    // Baris lama ala BvTeamSeeder: nama depan seperti yang tertulis di sheet.
    $lama = BvSalesList::create(['nama_sales' => 'Gressita', 'user_id' => $user->id]);

    $employe = BvEmploye::create([
        'nama_lengkap' => 'Gressita Melli Aryati',
        'email' => 'gressita@bvnetwork.net',
        'alamat' => 'Jl. Uji No. 3',
        'kota' => 'Jakarta Selatan',
        'provinsi' => 'DKI Jakarta',
        'kode_pos' => '12760',
        'user_id' => $user->id,
        'position_id' => Position::where('name', 'Account Manager Staff')->value('id'),
    ]);

    expect(BvSalesList::count())->toBe(1)
        ->and($lama->fresh()->bv_employe_id)->toBe($employe->id)
        // Nama depan dipertahankan: itu kunci pencocokan PIC dari sheet.
        ->and($lama->fresh()->nama_sales)->toBe('Gressita');

    // Karyawan tanpa baris lama tetap dapat baris baru.
    BvEmploye::create([
        'nama_lengkap' => 'Karyawan Baru',
        'email' => 'karyawan-baru@bvnetwork.net',
        'alamat' => 'Jl. Uji No. 4',
        'kota' => 'Bandung',
        'provinsi' => 'Jawa Barat',
        'kode_pos' => '40191',
        'position_id' => Position::where('name', 'Account Manager Staff')->value('id'),
    ]);

    expect(BvSalesList::count())->toBe(2);
});
