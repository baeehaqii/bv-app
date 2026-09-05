<?php

use App\Enums\SalesStatus;
use App\Filament\Pages\SalesDashboard;
use App\Models\{BvSales, BvSalesList, SalesTarget, User};
use Illuminate\Support\Facades\Gate;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;

function sebagaiExecutive(): User
{
    Role::firstOrCreate(['name' => 'super_admin']);
    $u = User::create(['name' => 'Exec', 'email' => 'exec@bvnetwork.net', 'password' => bcrypt('x')]);
    $u->syncRoles(['super_admin']);
    test()->actingAs($u);
    Gate::before(fn () => true);

    return $u;
}

it('Target Saya mengambil angka dari Sales Targets, bukan nol', function () {
    $exec = sebagaiExecutive();
    $sales = BvSalesList::create(['nama_sales' => 'Wina', 'user_id' => $exec->id]);

    $now = now();
    SalesTarget::create([
        'bv_sales_list_id' => $sales->id, 'year' => $now->year, 'month' => $now->month,
        'target_amount' => 800_000_000,
    ]);
    SalesTarget::create([
        'bv_sales_list_id' => $sales->id, 'year' => $now->year, 'month' => $now->month === 12 ? 1 : $now->month + 1,
        'target_amount' => 200_000_000,
    ]);

    BvSales::create([
        'bv_sales_list_id' => $sales->id, 'event_name' => 'Deal A', 'company_name' => 'PT A',
        'status' => SalesStatus::PAID->value, 'deal_value' => 400_000_000, 'close_date' => $now->toDateString(),
    ]);

    $target = Livewire::test(SalesDashboard::class)->instance()->getMyTarget();

    expect($target['month']['has_target'])->toBeTrue()
        ->and((float) $target['month']['target'])->toBe(800_000_000.0)
        ->and((float) $target['month']['achieved'])->toBe(400_000_000.0)
        ->and((float) $target['month']['percent'])->toBe(50.0)
        // target tahunan = jumlah seluruh bulan yang diisi
        ->and((float) $target['year']['target'])->toBe(1_000_000_000.0);
});

it('membedakan target belum diatur dari target belum tercapai', function () {
    $exec = sebagaiExecutive();
    BvSalesList::create(['nama_sales' => 'Tanpa Target', 'user_id' => $exec->id]);

    $target = Livewire::test(SalesDashboard::class)->instance()->getMyTarget();

    expect($target['month']['has_target'])->toBeFalse()
        ->and($target['year']['has_target'])->toBeFalse();
});

it('dropdown monitoring menunjuk sales yang datanya sedang ditampilkan', function () {
    $exec = sebagaiExecutive();
    $wina = BvSalesList::create(['nama_sales' => 'Wina', 'user_id' => $exec->id]);
    BvSalesList::create(['nama_sales' => 'Aliy']);

    // Regresi: selectedSalesId dulu null saat render pertama, sementara
    // getSalesList() sudah memilih sales — dropdown dan angka bisa beda orang.
    Livewire::test(SalesDashboard::class)
        ->assertSet('selectedSalesId', $wina->id);
});

it('To Do mengambil deal yang menunggu tindakan dari Activity Tracker', function () {
    $exec = sebagaiExecutive();
    $sales = BvSalesList::create(['nama_sales' => 'Wina', 'user_id' => $exec->id]);

    $buat = fn (string $status, string $nama, ?string $close = null) => BvSales::create([
        'bv_sales_list_id' => $sales->id, 'event_name' => $nama, 'company_name' => 'PT X',
        'status' => $status, 'close_date' => $close,
    ]);

    $buat(SalesStatus::NOT_STARTED->value, 'Lead Baru', now()->addDays(5)->toDateString());
    $buat(SalesStatus::PROPOSAL_BUILDING->value, 'Proposal Telat', now()->subDays(2)->toDateString());
    $buat(SalesStatus::NEGOTIATION->value, 'Nego', now()->toDateString());
    // Yang sudah deal / kalah bukan urusan to-do sales.
    $buat(SalesStatus::PAID->value, 'Sudah Bayar');
    $buat(SalesStatus::CLOSE_LOSE->value, 'Kalah');
    $buat(SalesStatus::CAMPAIGN_LIVE->value, 'Sedang Jalan');

    $todos = Livewire::test(SalesDashboard::class)->instance()->getMyTodos();

    expect($todos['total'])->toBe(3)
        ->and(collect($todos['items'])->pluck('title')->all())
        // paling mendesak di atas
        ->toBe(['Proposal Telat', 'Nego', 'Lead Baru']);

    $telat = $todos['items'][0];
    expect($telat['action'])->toBe('Selesaikan proposal')
        ->and($telat['is_overdue'])->toBeTrue();

    expect($todos['items'][1]['is_today'])->toBeTrue()
        ->and($todos['items'][2]['action'])->toBe('Follow up lead baru')
        ->and($todos['items'][2]['is_new'])->toBeTrue();

    expect(collect($todos['groups'])->pluck('total', 'label')->all())
        ->toBe(['New Leads' => 1, 'Proposal Building' => 1, 'Negotiation/Submit' => 1]);
});

it('halaman Sales Dashboard ter-render dengan kartu To Do', function () {
    $exec = sebagaiExecutive();
    BvSalesList::create(['nama_sales' => 'Wina', 'user_id' => $exec->id]);

    // "Pipeline Aktif" adalah kartu lain yang memang tetap ada; yang diganti
    // hanya kartu "Pipeline" di bawah Target.
    $this->get('/office/sales-dashboard')
        ->assertSuccessful()
        ->assertSee('To Do')
        ->assertSee('Activity Tracker')
        ->assertSee('Tidak ada yang perlu ditindaklanjuti.')
        ->assertSee('Target Saya')
        ->assertSee('Atur di Sales Targets');
});
