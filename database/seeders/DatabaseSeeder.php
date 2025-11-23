<?php

namespace Database\Seeders;

use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Jalankan ShieldSeeder untuk membuat permissions
        $this->call(ShieldSeeder::class);

        // Generate Shield permissions dan policies untuk semua resources
        $this->command->info('Generating Shield permissions and policies...');
        \Illuminate\Support\Facades\Artisan::call('shield:generate', [
            '--all' => true,
            '--panel' => 'office',
            '--option' => '2', // Option 2 = Generate Policies & Permissions
        ]);
        $this->command->info('Shield generation completed!');

        // Kemudian jalankan UserSeeder
        $this->call(UserSeeder::class);

        $this->command->info('Database seeding completed successfully!');
    }
}
