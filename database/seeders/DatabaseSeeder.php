<?php

namespace Database\Seeders;

// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Database\Seeders\MasterMarginSeeder;
use Database\Seeders\MasterPphSeeder;
use Database\Seeders\MediaPlanCalcSettingSeeder;

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

        // Client/Brand dari Sales Pipeline (untuk pasangan BvSales.company_name)
        $this->call(PipelineClientSeeder::class);
        $this->command->info('PipelineClient seeding completed!');

        // Data Sales Tracker (BvSales) dari sheet Pipeline
        $this->call(SalesTrackerSeeder::class);
        $this->command->info('SalesTracker seeding completed!');

        // Jalankan DataVendorSeeder
        $this->call(DataVendorSeeder::class);
        $this->command->info('DataVendor seeding completed!');

        $this->call(MasterPphSeeder::class);
        $this->command->info('MasterPph seeding completed!');

        // Master data rumus Media Plan Internal (acuan: sheet KOL List)
        $this->call(MasterMarginSeeder::class);
        $this->command->info('MasterMargin seeding completed!');

        $this->call(MediaPlanCalcSettingSeeder::class);
        $this->command->info('MediaPlanCalcSetting seeding completed!');

        // Jalankan MasterServiceSeeder (master data 7 layanan BV Network)
        $this->call(MasterServiceSeeder::class);
        $this->command->info('MasterService seeding completed!');

        // Jalankan MasterSowSeeder (master data SOW untuk Rate Card KOL)
        $this->call(MasterSowSeeder::class);
        $this->command->info('MasterSow seeding completed!');

        // Jalankan OrganizationStructureSeeder
        $this->call(OrganizationStructureSeeder::class);
        $this->command->info('OrganizationStructure seeding completed!');

        // Data karyawan dari form "List Data Karyawan" (butuh struktur org & user)
        $this->call(BvEmployeSeeder::class);
        $this->command->info('BvEmploye seeding completed!');

        // Target 2026 dari sheet "2026 Sales Target" (butuh user & bv_sales_lists)
        $this->call(SalesTarget2026Seeder::class);
        $this->command->info('SalesTarget2026 seeding completed!');

        $this->command->info('Database seeding completed successfully!');
    }
}
