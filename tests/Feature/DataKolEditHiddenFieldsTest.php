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

it('memindahkan Additional Info ke modal detail per channel', function () {
    $kol = DataKol::create([
        'channel' => 'Instagram',
        'link_userprofile' => 'https://instagram.com/budi',
        'username' => 'budi',
        'followers' => 856870,
        'full_name' => 'Budi PIC',
        'email' => 'budi@example.test',
        'wa_number' => '08123456789',
        'notes' => "Bio: Halo saya Budi\nTier: Macro",
    ]);

    Livewire::test(EditDataKol::class, ['record' => $kol->getRouteKey()])
        ->assertSee('Additional Info')
        ->assertSee('Budi PIC')
        ->assertSee('budi@example.test')
        ->assertSee('08123456789')
        // Baris statistik di notes dibuang, bio-nya tetap tampil.
        ->assertSee('Bio: Halo saya Budi')
        ->assertDontSee('Tier: Macro');
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
        // full_name & kawan-kawannya ikut hilang bersama section "Additional Info";
        // sekarang cuma tampil di modal detail. Yang tersisa untuk diketik manusia
        // adalah data legal/rekening.
        ->assertFormFieldDoesNotExist('full_name')
        ->assertFormFieldDoesNotExist('notes')
        ->assertFormFieldExists('tipe_pajak_kol')   // pindah ke section Data Legal
        ->fillForm(['bank_name' => 'SeaBank'])
        ->call('save')
        ->assertHasNoFormErrors();

    expect($kol->refresh())
        ->bank_name->toBe('SeaBank')
        // Tidak lagi ada di form → save tidak boleh mengosongkannya.
        ->full_name->toBe('Budi PIC')
        ->channel->toBe('Instagram')
        ->username->toBe('budi')
        ->followers->toBe(856870)
        ->tier->toBe('Macro')
        ->engagements->toBe(104224)
        ->impressions->toBe(203573);
});
