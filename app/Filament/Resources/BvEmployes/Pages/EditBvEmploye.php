<?php

namespace App\Filament\Resources\BvEmployes\Pages;

use App\Filament\Resources\BvEmployes\BvEmployeResource;
use App\Models\User;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use Spatie\Permission\Models\Role;

class EditBvEmploye extends EditRecord
{
    protected static string $resource = BvEmployeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }

    protected function afterSave(): void
    {
        $employe = $this->record->load('position.department.division');

        if (! $employe->user_id) {
            return;
        }

        $user = User::find($employe->user_id);
        if (! $user) {
            return;
        }

        // Sync nama dan email user mengikuti perubahan data karyawan
        $user->update([
            'name'  => $employe->nama_lengkap,
            'email' => $employe->email,
        ]);

        // Sync role mengikuti divisi jabatan terbaru
        $divisionName = $employe->position?->department?->division?->name;
        if ($divisionName) {
            $role = Role::firstOrCreate(['name' => $divisionName, 'guard_name' => 'web']);
            $user->syncRoles([$role]);
        }
    }
}
