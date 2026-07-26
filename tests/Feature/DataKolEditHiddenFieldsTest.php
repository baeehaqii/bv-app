<?php

use App\Filament\Resources\DataKols\Pages\EditDataKol;
use App\Models\DataKol;
use App\Models\User;
use Illuminate\Support\Facades\Gate;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;

/**
 * Field Channel..Tier disembunyikan di halaman Edit (sudah tampil di tabel "Data Per Channel").
 * Yang dijaga test ini: menyimpan form edit TIDAK mengosongkan kolom-kolom itu — field hidden
 * tidak di-dehydrate Filament, jadi kolomnya tidak ikut di-update.
 */
beforeEach(function () {
    Role::firstOrCreate(['name' => 'super_admin']);
    $user = User::create([
        'name' => 'Edit Admin',
        'email' => 'edit-admin@bvnetwork.net',
        'password' => bcrypt('password'),
    ]);
    $user->syncRoles(['super_admin']);
    $this->actingAs($user);
    Gate::before(fn () => true);
});

it('menyembunyikan field channel..tier di edit dan tidak mengosongkannya saat save', function () {
    $kol = DataKol::create([
        'channel' => 'Instagram',
        'link_userprofile' => 'https://instagram.com/budi',
        'username' => 'budi',
        'followers' => 856870,
        'tier' => 'Macro',
        'engagement_rate' => 3.37,
        'engagements' => 104224,
        'impressions' => 203573,
        'category' => ['Lifestyle'],
        'full_name' => 'Budi PIC',
    ]);

    Livewire::test(EditDataKol::class, ['record' => $kol->getRouteKey()])
        ->assertFormFieldIsHidden('channel')
        ->assertFormFieldIsHidden('link_userprofile')
        ->assertFormFieldIsHidden('username')
        ->assertFormFieldIsHidden('category')
        ->assertFormFieldIsHidden('engagement_rate')
        ->assertFormFieldIsHidden('engagements')
        ->assertFormFieldIsHidden('impressions')
        ->assertFormFieldIsHidden('followers')
        ->assertFormFieldIsHidden('tier')
        ->fillForm(['full_name' => 'Budi Ganti'])
        ->call('save')
        ->assertHasNoFormErrors();

    expect($kol->refresh())
        ->full_name->toBe('Budi Ganti')
        ->channel->toBe('Instagram')
        ->username->toBe('budi')
        ->followers->toBe(856870)
        ->tier->toBe('Macro')
        ->engagements->toBe(104224)
        ->impressions->toBe(203573);
});
