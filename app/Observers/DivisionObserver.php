<?php

namespace App\Observers;

use App\Models\Division;
use Spatie\Permission\Models\Role;

class DivisionObserver
{
    public function created(Division $division): void
    {
        Role::firstOrCreate([
            'name'       => $division->name,
            'guard_name' => 'web',
        ]);
    }

    public function updated(Division $division): void
    {
        if (! $division->wasChanged('name')) {
            return;
        }

        $oldName = $division->getOriginal('name');
        $role    = Role::where('name', $oldName)->where('guard_name', 'web')->first();

        if ($role) {
            $role->update(['name' => $division->name]);
        } else {
            Role::firstOrCreate([
                'name'       => $division->name,
                'guard_name' => 'web',
            ]);
        }
    }
}
