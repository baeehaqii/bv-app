<?php

use App\Enums\SalesStatus;
use App\Filament\Pages\SalesTargetMatrix;
use App\Models\BvSales;
use App\Models\BvSalesList;
use App\Models\GrossProfitTarget;
use App\Models\SalesTarget;
use App\Models\User;
use Illuminate\Support\Facades\Gate;
use Livewire\Livewire;

beforeEach(function () {
    $this->actingAs(User::create([
        'name' => 'Matrix Admin',
        'email' => 'matrix-admin@bvnetwork.net',
        'password' => bcrypt('password'),
    ]));

    Gate::before(fn() => true);

    $this->wina = BvSalesList::create(['nama_sales' => 'Wina']);
    $this->gerry = BvSalesList::create(['nama_sales' => 'Vacant (Gerry)']);
});

it('kolom quarter & tahun dijumlahkan dari bulan, persentase dari totalnya', function () {
    // Angka Q3 dari sheet: Jul 770+330, Agu 700+300, Sep 805+345 = 3.250 juta
    $sheet = [
        7 => [770_000_000, 330_000_000],
        8 => [700_000_000, 300_000_000],
        9 => [805_000_000, 345_000_000],
    ];

    foreach ($sheet as $month => [$wina, $gerry]) {
        SalesTarget::create(['bv_sales_list_id' => $this->wina->id, 'year' => 2026, 'month' => $month, 'target_amount' => $wina]);
        SalesTarget::create(['bv_sales_list_id' => $this->gerry->id, 'year' => 2026, 'month' => $month, 'target_amount' => $gerry]);

        GrossProfitTarget::create([
            'year' => 2026,
            'month' => $month,
            'target_deal_revenue' => $wina + $gerry,
            'margin_benchmark_percent' => 31,
        ]);
    }

    // Realisasi: satu deal won Juli, margin 31% → persis benchmark
    BvSales::create([
        'event_name' => 'Deal Juli',
        'company_name' => 'Brand A',
        'bv_sales_list_id' => $this->wina->id,
        'status' => SalesStatus::PAID->value,
        'close_date' => '2026-07-20',
        'deal_value' => 550_000_000,
        'margin' => 31,
    ]);

    $rows = collect(Livewire::test(SalesTargetMatrix::class, ['year' => 2026])->instance()->rows())
        ->keyBy('label');

    expect($rows['Booked Revenue']['values']['q3'])->toBe(3_250_000_000.0)
        ->and($rows['Booked Revenue']['values']['year'])->toBe(3_250_000_000.0)
        // GP target Q3 = 31% x 3.250 juta = 1.007,5 juta (sesuai sheet)
        ->and($rows['Booked GP Target']['values']['q3'])->toBe(1_007_500_000.0)
        ->and($rows['Total Target Sales']['values']['q3'])->toBe(3_250_000_000.0)
        ->and($rows['Actual Booked Revenue']['values']['m7'])->toBe(550_000_000.0)
        ->and($rows['Actual Booked GP']['values']['m7'])->toBe(170_500_000.0)
        // 550 juta dari target Juli 1.100 juta = 50%
        ->and($rows['% of Sales Target Achievement']['values']['m7'])->toBe(50.0)
        ->and($rows['% of Profit Margin']['values']['m7'])->toBe(31.0)
        ->and($rows['% of Profit Margin Benchmark']['values']['q3'])->toBe(31.0);
});

it('menyimpan target per sales dari matriks dan menghapus sel yang dinolkan', function () {
    $existing = SalesTarget::create([
        'bv_sales_list_id' => $this->gerry->id,
        'year' => 2026,
        'month' => 7,
        'target_amount' => 330_000_000,
    ]);

    Livewire::test(SalesTargetMatrix::class, ['year' => 2026])
        ->set("cells.{$this->wina->id}.7", '770.000.000')
        ->set("cells.{$this->gerry->id}.7", '')
        ->call('save')
        ->assertHasNoErrors();

    expect((float) SalesTarget::forSales($this->wina->id)->forMonth(2026, 7)->value('target_amount'))
        ->toBe(770_000_000.0)
        ->and(SalesTarget::find($existing->id))->toBeNull();
});

it('% achievement memakai total target sales, Finance hanya dipakai kalau target sales kosong', function () {
    // November: target sales terisi, Target Finance beda angka — yang dipakai target sales.
    SalesTarget::create(['bv_sales_list_id' => $this->wina->id, 'year' => 2026, 'month' => 11, 'target_amount' => 400_000_000]);
    GrossProfitTarget::create(['year' => 2026, 'month' => 11, 'target_deal_revenue' => 1_000_000_000, 'margin_benchmark_percent' => 31]);

    // Desember: target sales kosong, jadi jatuh ke Booked Revenue Finance.
    GrossProfitTarget::create(['year' => 2026, 'month' => 12, 'target_deal_revenue' => 500_000_000, 'margin_benchmark_percent' => 31]);

    foreach ([11 => 200_000_000, 12 => 250_000_000] as $month => $dealValue) {
        BvSales::create([
            'event_name' => "Deal bulan {$month}",
            'company_name' => 'Brand A',
            'bv_sales_list_id' => $this->wina->id,
            'status' => SalesStatus::PAID->value,
            'close_date' => "2026-{$month}-10",
            'deal_value' => $dealValue,
            'margin' => 31,
        ]);
    }

    $rows = collect(Livewire::test(SalesTargetMatrix::class, ['year' => 2026])->instance()->rows())
        ->keyBy('label');

    expect($rows['% of Sales Target Achievement']['values']['m11'])->toBe(50.0)   // 200jt / 400jt target sales
        ->and($rows['% of Sales Target Achievement']['values']['m12'])->toBe(50.0); // 250jt / 500jt Finance (fallback)
});
