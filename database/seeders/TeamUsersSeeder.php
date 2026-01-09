<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

class TeamUsersSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create roles if they don't exist
        $bdSalesRole = Role::firstOrCreate(['name' => 'BD & Sales']);
        $kolMediaPlanRole = Role::firstOrCreate(['name' => 'KOL Media Plan']);
        $accountManagementRole = Role::firstOrCreate(['name' => 'Account Management']);

        // Team members data
        $teamMembers = [
            [
                'name' => 'Karina',
                'email' => 'karina@beyondviral.id',
                'roles' => [$bdSalesRole],
            ],
            [
                'name' => 'Gerry',
                'email' => 'gerry@beyondviral.id',
                'roles' => [$bdSalesRole],
            ],
            [
                'name' => 'Syelind',
                'email' => 'syelind@beyondviral.id',
                'roles' => [$bdSalesRole],
            ],
            [
                'name' => 'Fajar',
                'email' => 'fajar@beyondviral.id',
                'roles' => [$bdSalesRole],
            ],
            [
                'name' => 'Nabila',
                'email' => 'nabila@beyondviral.id',
                'roles' => [$kolMediaPlanRole],
            ],
            [
                'name' => 'Dina',
                'email' => 'dina@beyondviral.id',
                'roles' => [$accountManagementRole],
            ],
        ];

        foreach ($teamMembers as $member) {
            $user = User::updateOrCreate(
                ['email' => $member['email']],
                [
                    'name' => $member['name'],
                    'password' => Hash::make('password123'), // Default password
                    'email_verified_at' => now(),
                ]
            );

            // Sync roles
            $user->syncRoles($member['roles']);

            $this->command->info("Created/Updated user: {$member['name']} ({$member['email']})");
        }

        $this->command->info('Team users seeded successfully!');
    }
}
