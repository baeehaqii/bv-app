<?php

namespace App\Filament\Resources\BvEmployes\Pages;

use App\Filament\Resources\BvEmployes\BvEmployeResource;
use App\Models\Position;
use App\Models\User;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

class CreateBvEmploye extends CreateRecord
{
    protected static string $resource = BvEmployeResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        unset($data['password'], $data['password_confirmation'], $data['_division_id']);

        if (!empty($data['position_id'])) {
            $position = Position::with('department.division')->find($data['position_id']);
            $data['divis'] = $position?->department?->division?->name;
        }

        return $data;
    }

    protected function afterCreate(): void
    {
        $employe = $this->record->load('position.department.division');

        // Ambil nama divisi dari jabatan → departemen → divisi
        $divisionName = $employe->position?->department?->division?->name;

        // Akunnya bisa saja sudah ada — orang yang sudah dibuatkan user (lewat
        // UserSeeder/BvTeamSeeder atau menu User) tapi barisnya belum ada di Data
        // Karyawan. Dulu di sini User::create langsung melempar duplicate key
        // users.email → 500 "Error while loading page", padahal baris karyawannya
        // sudah tersimpan. Sekarang akun lama diadopsi.
        $user = User::where('email', $employe->email)->first();
        $adopted = $user !== null;

        if (! $adopted) {
            $user = User::create([
                'name' => $employe->nama_lengkap,
                'email' => $employe->email,
                'password' => Hash::make($this->data['password']),
                'email_verified_at' => now(),
            ]);

            // Role menyusul divisi jabatannya. Untuk akun yang diadopsi, password
            // dan role-nya TIDAK disentuh: form karyawan bukan tempat mereset
            // kredensial orang lain.
            if ($divisionName) {
                $user->syncRoles([Role::firstOrCreate(['name' => $divisionName, 'guard_name' => 'web'])]);
            }
        }

        // Hubungkan user ke karyawan (gunakan update agar observer sync user_id ke sales list / BD)
        $employe->update(['user_id' => $user->id]);

        Notification::make()
            ->title($adopted ? 'Ditautkan ke akun yang sudah ada' : 'Akun berhasil dibuat')
            ->body($adopted
                ? "Akun {$employe->email} sudah ada, karyawan ini ditautkan ke akun itu. Password & role akun lama tidak diubah."
                : "Email: {$employe->email} | Role: " . ($divisionName ?? '–'))
            ->success()
            ->send();
    }
}
