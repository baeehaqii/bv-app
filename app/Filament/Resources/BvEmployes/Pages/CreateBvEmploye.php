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

        // Pastikan role berdasarkan divisi sudah ada
        $role = $divisionName
            ? Role::firstOrCreate(['name' => $divisionName, 'guard_name' => 'web'])
            : null;

        // Buat akun user
        $user = User::create([
            'name' => $employe->nama_lengkap,
            'email' => $employe->email,
            'password' => Hash::make($this->data['password']),
            'email_verified_at' => now(),
        ]);

        if ($role) {
            $user->syncRoles([$role]);
        }

        // Hubungkan user ke karyawan (gunakan update agar observer sync user_id ke sales list / BD)
        $employe->update(['user_id' => $user->id]);

        Notification::make()
            ->title('Akun berhasil dibuat')
            ->body("Email: {$employe->email} | Role: " . ($divisionName ?? '–'))
            ->success()
            ->send();
    }
}
