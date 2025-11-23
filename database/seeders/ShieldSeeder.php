<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class ShieldSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create permissions for Filament Shield resources
        $permissions = [
            // DataKol Resource
            'view_any::datakol',
            'view::datakol',
            'create::datakol',
            'update::datakol',
            'delete::datakol',
            'restore::datakol',
            'force_delete::datakol',
            'force_delete_any::datakol',
            'restore_any::datakol',
            'replicate::datakol',
            'reorder::datakol',

            // Role Resource
            'view_any::role',
            'view::role',
            'create::role',
            'update::role',
            'delete::role',
            'restore::role',
            'force_delete::role',
            'force_delete_any::role',
            'restore_any::role',
            'replicate::role',
            'reorder::role',
        ];

        // Create or get permissions
        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission, 'guard_name' => 'web']);
        }

        $this->command->info('Shield permissions created successfully.');
    }
}
