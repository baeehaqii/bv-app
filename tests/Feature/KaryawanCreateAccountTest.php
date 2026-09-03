<?php

use App\Filament\Resources\BvEmployes\Pages\CreateBvEmploye;
use App\Models\BvEmploye;
use App\Models\Department;
use App\Models\Division;
use App\Models\Position;
use App\Models\User;
use Illuminate\Support\Facades\Gate;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;

beforeEach(function () {
    Role::firstOrCreate(['name' => 'super_admin']);

    $admin = User::create([
        'name' => 'HC Admin',
        'email' => 'hc-admin@bvnetwork.net',
        'password' => bcrypt('password'),
    ]);
    $admin->syncRoles(['super_admin']);
    $this->actingAs($admin);
    Gate::before(fn() => true);

    $division = Division::create(['name' => 'Operation KOL & Creative', 'slug' => 'operation-kol']);
    $department = Department::create([
        'division_id' => $division->id,
        'name' => 'KOL',
        'slug' => 'kol',
    ]);
    $this->position = Position::create([
        'department_id' => $department->id,
        'name' => 'KOL Specialist',
        'level' => 'staff',
    ]);
});

function karyawanFormData(array $override = []): array
{
    return array_merge([
        'nama_lengkap' => 'Karyawan Baru',
        'email' => 'karyawan.baru',
        'whatsapp' => '081200000001',
        'alamat' => 'Jl. Mawar 1',
        'kota' => 'Bogor',
        'provinsi' => 'Jawa Barat',
        'kode_pos' => '16911',
        'password' => 'RahasiaKuat123',
        'password_confirmation' => 'RahasiaKuat123',
    ], $override);
}

it('membuat karyawan sekaligus akun user baru', function () {
    Livewire::test(CreateBvEmploye::class)
        ->fillForm(karyawanFormData(['position_id' => $this->position->id]))
        ->call('create')
        ->assertHasNoFormErrors();

    $karyawan = BvEmploye::where('email', 'karyawan.baru@bvnetwork.net')->firstOrFail();

    expect($karyawan->user_id)->not->toBeNull()
        ->and($karyawan->user->email)->toBe('karyawan.baru@bvnetwork.net')
        ->and($karyawan->user->hasRole('Operation KOL & Creative'))->toBeTrue();
});

it('menautkan akun user yang sudah ada, bukan gagal duplikat email', function () {
    // Kasus nyata: orangnya sudah punya akun (dari UserSeeder/BvTeamSeeder) tapi
    // belum ada barisnya di Data Karyawan. User::create dulu melempar duplicate
    // key di sini → 500 → "Error while loading page", padahal baris karyawannya
    // sudah tersimpan.
    $existing = User::create([
        'name' => 'Salwa',
        'email' => 'salwa@bvnetwork.net',
        'password' => bcrypt('password-lama'),
    ]);
    $existing->syncRoles(['super_admin']);

    Livewire::test(CreateBvEmploye::class)
        ->fillForm(karyawanFormData([
            'nama_lengkap' => 'Salwa',
            'email' => 'salwa',
            'position_id' => $this->position->id,
        ]))
        ->call('create')
        ->assertHasNoFormErrors();

    $karyawan = BvEmploye::where('email', 'salwa@bvnetwork.net')->firstOrFail();

    expect(User::where('email', 'salwa@bvnetwork.net')->count())->toBe(1)
        ->and($karyawan->user_id)->toBe($existing->id)
        // Password & role akun lama tidak boleh ditimpa dari form karyawan.
        ->and(Hash::check('password-lama', $existing->fresh()->password))->toBeTrue()
        ->and($existing->fresh()->hasRole('super_admin'))->toBeTrue();
});

it('menolak WhatsApp yang sudah dipakai karyawan lain lewat validasi, bukan error 500', function () {
    BvEmploye::create([
        'nama_lengkap' => 'Karyawan Lama',
        'email' => 'karyawan.lama@bvnetwork.net',
        'whatsapp' => '081200000001',
        'alamat' => 'Jl. Lama',
        'kota' => 'Bogor',
        'provinsi' => 'Jawa Barat',
        'kode_pos' => '16911',
    ]);

    Livewire::test(CreateBvEmploye::class)
        ->fillForm(karyawanFormData(['position_id' => $this->position->id]))
        ->call('create')
        ->assertHasFormErrors(['whatsapp']);

    expect(BvEmploye::where('email', 'karyawan.baru@bvnetwork.net')->exists())->toBeFalse();
});
