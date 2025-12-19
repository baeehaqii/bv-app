<?php

namespace Database\Seeders;

use App\Models\MasterMargin;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class MasterMarginSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $margins = [
            [
                'name' => 'Low Budget',
                'min_amount' => 100000,
                'max_amount' => 2999999,
                'margin_percent' => 80.00,
                'order' => 1,
                'is_active' => true,
            ],
            [
                'name' => 'Medium Budget',
                'min_amount' => 3000000,
                'max_amount' => 50000000,
                'margin_percent' => 40.00,
                'order' => 2,
                'is_active' => true,
            ],
            [
                'name' => 'High Budget',
                'min_amount' => 50000001,
                'max_amount' => null, // Unlimited
                'margin_percent' => 30.00,
                'order' => 3,
                'is_active' => true,
            ],
        ];

        foreach ($margins as $margin) {
            MasterMargin::updateOrCreate(
                ['name' => $margin['name']],
                $margin
            );
        }
    }
}

