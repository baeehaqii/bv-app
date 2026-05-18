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

        // Data users — domain resmi @bvnetwork.com
        $users = [
            [
                'name' => 'CEO',
                'email' => 'ceo@bvnetwork.com',
                'password' => 'Ap4sihya',
                'role' => $superAdminRole,
            ],
            [
                'name' => 'COO',
                'email' => 'coo@bvnetwork.com',
                'password' => 'Ap4sihya',
                'role' => $superAdminRole,
            ],
            [
                'name' => 'Finance Team',
                'email' => 'finance@bvnetwork.com',
                'password' => 'Ap4sihya',
                'role' => $financeRole,
            ],
            [
                'name' => 'Sales',
                'email' => 'sales@bvnetwork.com',
                'password' => 'Ap4sihya',
                'role' => $salesBdRole,
            ],
            [
                'name' => 'BD Manager',
                'email' => 'bd.manager@bvnetwork.com',
                'password' => 'Ap4sihya',
                'role' => $salesBdRole,
            ],
            [
                'name' => 'Operation KOL Creative Team',
                'email' => 'operation.kol@bvnetwork.com',
                'password' => 'Ap4sihya',
                'role' => $operationRole,
            ],
            [
                'name' => 'Baehaqi',
                'email' => 'baehaqi@bvnetwork.com',
                'password' => 'Ap4sihya#@',
                'role' => $superAdminRole,
            ],
        ];

        // Migrasi email lama (@bv.com dan @bvnetwork tanpa .com) → @bvnetwork.com
        $oldDomains = ['@bv.com', '@bvnetwork'];
        foreach ($users as $userData) {
            $local = explode('@', $userData['email'])[0];
            foreach ($oldDomains as $oldDomain) {
                User::where('email', $local . $oldDomain)->update(['email' => $userData['email']]);
            }
        }

        // Buat atau update users dan assign role
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
