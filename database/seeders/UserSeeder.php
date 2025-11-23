<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Pastikan role super_admin sudah ada
        $superAdminRole = Role::firstOrCreate(['name' => 'super_admin']);

        // Assign semua permissions ke super_admin role
        $permissions = Permission::all();
        $superAdminRole->syncPermissions($permissions);

        // Data users
        $users = [
            [
                'name' => 'Baehaqi',
                'email' => 'baehaqi@bv.com',
                'password' => 'Ap4sihya',
            ],
            [
                'name' => 'Gerry',
                'email' => 'gerry@bv.com',
                'password' => 'Ap4sihya',
            ],
            [
                'name' => 'Syelind',
                'email' => 'syelind@bv.com',
                'password' => 'Ap4sihya',
            ],
            [
                'name' => 'Fajar',
                'email' => 'fajar@bv.com',
                'password' => 'Ap4sihya',
            ],
        ];

        // Buat atau update users dan assign role super_admin
        foreach ($users as $userData) {
            $user = User::updateOrCreate(
                ['email' => $userData['email']],
                [
                    'name' => $userData['name'],
                    'password' => Hash::make($userData['password']),
                    'email_verified_at' => now(),
                ]
            );

            // Assign role super_admin
            if (!$user->hasRole('super_admin')) {
                $user->assignRole($superAdminRole);
            }

            $this->command->info("User {$user->email} created/updated with super_admin role.");
        }

        $this->command->info("Super Admin role memiliki " . $superAdminRole->permissions->count() . " permissions.");
    }
}
