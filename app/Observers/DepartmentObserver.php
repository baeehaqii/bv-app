<?php

namespace App\Observers;

use App\Models\Department;
use App\Models\Position;

class DepartmentObserver
{
    public function updated(Department $department): void
    {
        if (! $department->wasChanged('sync_type')) {
            return;
        }

        // Re-sync semua karyawan di departemen ini saat sync_type berubah
        Position::where('department_id', $department->id)
            ->with('employees')
            ->get()
            ->each(fn($position) => $position->employees->each->save());
    }
}
