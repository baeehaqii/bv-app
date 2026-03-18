<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Pastikan role OD sudah ada
        $superAdminRole = Role::firstOrCreate(['name' => 'super_admin']);
        $financeRole = Role::firstOrCreate(['name' => 'Finance']);
        $salesBdRole = Role::firstOrCreate(['name' => 'Sales/BD']);
        $operationRole = Role::firstOrCreate(['name' => 'Operation KOL & Creative']);

        // Data users
        $users = [
            [
                'name' => 'CEO',
                'email' => 'ceo@bv.com',
                'password' => 'Ap4sihya',
                'role' => $superAdminRole,
            ],
            [
                'name' => 'COO',
                'email' => 'coo@bv.com',
                'password' => 'Ap4sihya',
                'role' => $superAdminRole,
            ],
            [
                'name' => 'Finance Team',
                'email' => 'finance@bv.com',
                'password' => 'Ap4sihya',
                'role' => $financeRole,
            ],
            [
                'name' => 'Sales',
                'email' => 'sales@bv.com',
                'password' => 'Ap4sihya',
                'role' => $salesBdRole,
            ],
            [
                'name' => 'BD Manager',
                'email' => 'bd.manager@bv.com',
                'password' => 'Ap4sihya',
                'role' => $salesBdRole,
            ],
            [
                'name' => 'Operation KOL Creative Team',
                'email' => 'operation.kol@bv.com',
                'password' => 'Ap4sihya',
                'role' => $operationRole,
            ],
        ];

        // Buat atau update users dan assign role sesuai matrix OD
        foreach ($users as $userData) {
            $user = User::updateOrCreate(
                ['email' => $userData['email']],
                [
                    'name' => $userData['name'],
                    'password' => Hash::make($userData['password']),
                    'email_verified_at' => now(),
                ]
            );

            $user->syncRoles([$userData['role']]);

            $this->command->info("User {$user->email} created/updated with role {$userData['role']->name}.");
        }
    }
}
