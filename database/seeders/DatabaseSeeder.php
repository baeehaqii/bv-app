<?php

namespace Database\Seeders;

// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Database\Seeders\MasterPphSeeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call(ShieldSeeder::class);
        $this->command->info('Generating Shield permissions and policies...');
        \Illuminate\Support\Facades\Artisan::call('shield:generate', [
            '--all' => true,
            '--panel' => 'office',
            '--option' => '2',
        ]);
        $this->command->info('Shield generation completed!');

        // Sinkronisasi role matrix OD berbasis permission Shield
        $this->call(RolePermissionSeeder::class);
        $this->command->info('RolePermission seeding completed!');

        // Jalankan UserSeeder
        $this->call(UserSeeder::class);

        // Jalankan DataClientSeeder
        $this->call(DataClientSeeder::class);
        $this->command->info('DataClient seeding completed!');

        // Jalankan DataVendorSeeder
        $this->call(DataVendorSeeder::class);
        $this->command->info('DataVendor seeding completed!');

        $this->call(MasterPphSeeder::class);
        $this->command->info('MasterPph seeding completed!');

        $this->command->info('Database seeding completed successfully!');
    }
}
