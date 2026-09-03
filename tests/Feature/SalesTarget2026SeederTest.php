<?php

use App\Models\BvSalesList;
use App\Models\GrossProfitTarget;
use App\Models\SalesTarget;
use App\Models\User;
use Database\Seeders\SalesTarget2026Seeder;

function seedUser(string $name, string $email): User
{
    return User::create([
        'name' => $name,
        'email' => $email,
        'password' => bcrypt('password'),
    ]);
}

it('menyeed Booked Revenue & GP 2026 sesuai sheet, dan idempoten', function () {
    seedUser('Wina', 'wina@bvnetwork.net');
    seedUser('Gerry', 'gerry@bvnetwork.net');

    $this->seed(SalesTarget2026Seeder::class);
    $this->seed(SalesTarget2026Seeder::class); // dijalankan dua kali: tidak boleh dobel

    $finance = GrossProfitTarget::forYear(2026)->get();

    expect($finance)->toHaveCount(12)
        // Total baris Booked Revenue & Booked GP Target di sheet
        ->and((float) $finance->sum('target_deal_revenue'))->toBe(9_717_419_355.0)
        ->and((float) $finance->sum('target_amount'))->toBe(3_012_400_000.0)
        // Bulan paling ekstrem di sheet: Feb 77.419.355 x 31% = 24.000.000
        ->and((float) $finance->firstWhere('month', 2)->target_amount)->toBe(24_000_000.0);
});

it('menautkan target per sales lewat email, Januari–Juni dibiarkan kosong seperti di sheet', function () {
    $wina = seedUser('Wina', 'wina@bvnetwork.net');
    $gerry = seedUser('Gerry', 'gerry@bvnetwork.net');

    $this->seed(SalesTarget2026Seeder::class);
    $this->seed(SalesTarget2026Seeder::class);

    $winaList = BvSalesList::where('user_id', $wina->id)->firstOrFail();
    $gerryList = BvSalesList::where('user_id', $gerry->id)->firstOrFail();

    expect(SalesTarget::forYear(2026)->count())->toBe(12)
        ->and(SalesTarget::totalForSalesYear($winaList->id, 2026))->toBe(4_795_000_000.0)
        ->and(SalesTarget::totalForSalesYear($gerryList->id, 2026))->toBe(2_055_000_000.0)
        // Jul: 770 + 330 juta = Booked Revenue bulan itu (1,1 M)
        ->and((float) SalesTarget::totalAllSalesForMonth(2026, 7))->toBe(1_100_000_000.0)
        ->and((float) GrossProfitTarget::dealRevenueForMonth(2026, 7))->toBe(1_100_000_000.0)
        // Sheet belum memecah Jan–Jun ke sales mana pun
        ->and((float) SalesTarget::totalAllSalesForMonth(2026, 6))->toBe(0.0);
});

it('melewati target sales kalau akun emailnya belum ada, tanpa error', function () {
    $this->seed(SalesTarget2026Seeder::class);

    expect(GrossProfitTarget::forYear(2026)->count())->toBe(12)
        ->and(SalesTarget::forYear(2026)->count())->toBe(0);
});
