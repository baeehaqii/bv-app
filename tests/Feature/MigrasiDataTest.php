<?php

use App\Filament\Pages\MigrasiData;
use App\Models\BvSalesList;
use App\Models\DataClient;
use App\Models\User;
use App\Service\ClientSheetMigration;
use App\Service\GoogleSheetReader;
use Illuminate\Support\Facades\Gate;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;

/**
 * Migrasi Data Client dari Google Spreadsheet.
 *
 * Panggilan ke Google tidak pernah dilakukan sungguhan: yang diuji parsing,
 * pemetaan judul kolom, dan penyimpanannya — bagian yang benar-benar bisa salah.
 */
function sheetRows(array $baris, ?array $judul = null): array
{
    return array_merge(
        [$judul ?? ['Nama Brand', 'Tipe', 'Kategori', 'Prioritas', 'Status Client', 'PIC Internal', 'Tanggal Outreach', 'Website']],
        $baris,
    );
}

function migrasiUser(): User
{
    Role::firstOrCreate(['name' => 'super_admin']);

    return tap(User::create([
        'name' => 'Migrasi Admin',
        'email' => 'migrasi-' . uniqid() . '@bvnetwork.net',
        'password' => bcrypt('password'),
    ]))->syncRoles(['super_admin']);
}

it('memetakan judul kolom apa pun urutannya, dan melaporkan yang tidak dikenali', function () {
    $migrasi = new ClientSheetMigration();

    // Urutan sengaja diacak + satu kolom yang tidak ada padanannya.
    $peta = $migrasi->mapHeaders(['Website', 'NAMA BRAND', 'Warna Favorit', 'kategori']);

    expect($peta)->toBe([0 => 'website', 1 => 'nama_brand', 3 => 'category'])
        ->and($migrasi->unmappedHeaders(['Website', 'NAMA BRAND', 'Warna Favorit', 'kategori']))
        ->toBe(['Warna Favorit']);
});

it('mengubah serial tanggal Google jadi Y-m-d dan menolak sel rusak', function () {
    // 45000 = 2023-03-15 pada kalender serial Google (hari 0 = 1899-12-30).
    expect(ClientSheetMigration::toDate(45000))->toBe('2023-03-15')
        ->and(ClientSheetMigration::toDate('2026-01-15'))->toBe('2026-01-15')
        // Angka telanjang sisa kolom lain, bukan tanggal.
        ->and(ClientSheetMigration::toDate(10))->toBeNull()
        ->and(ClientSheetMigration::toDate('bukan tanggal'))->toBeNull()
        ->and(ClientSheetMigration::toDate(''))->toBeNull();
});

it('melewati baris kosong dan menandai baris tanpa nama brand', function () {
    $items = (new ClientSheetMigration())->parseRows(sheetRows([
        ['Garuda Food', 'direct', 'FMCG', 'P0', 'won', '', '', ''],
        ['', '', '', '', '', '', '', ''],                          // pemisah, dilewati diam-diam
        ['', 'direct', 'FMCG', '', '', '', '', ''],                // ada isi tapi tanpa brand
    ]));

    expect($items)->toHaveCount(2)
        ->and($items[0]['nama_brand'])->toBe('Garuda Food')
        ->and($items[0]['_row'])->toBe(2)
        ->and($items[1]['_note'])->toContain('Kolom wajib kosong');
});

it('menormalkan tipe, prioritas, dan status client', function () {
    $items = (new ClientSheetMigration())->parseRows(sheetRows([
        ['IDEA Imaji', 'Agency', '', '2', 'WON', '', '', ''],
        ['Garuda Food', 'brand langsung', '', 'P0', 'ngawur', '', '', ''],
    ]));

    expect($items[0]['type'])->toBe('agency')
        ->and($items[0]['priority'])->toBe('P2')
        ->and($items[0]['status_client'])->toBe('won')
        ->and($items[1]['type'])->toBe('direct')
        // Status di luar daftar yang sah dibuang, bukan disimpan mentah.
        ->and($items[1]['status_client'])->toBeNull();
});

it('menyimpan baris dan tidak menggandakan saat dijalankan ulang', function () {
    $sales = BvSalesList::create(['nama_sales' => 'Budi Santoso']);

    $migrasi = new ClientSheetMigration();
    $items = $migrasi->parseRows(sheetRows([
        ['Garuda Food', 'direct', 'FMCG', 'P0', 'won', 'Budi Santoso', 45000, 'https://garudafood.com'],
    ]));

    $hasil = $migrasi->persist($items);

    expect($hasil['success'])->toBe(1)->and($hasil['failed'])->toBe(0);

    $client = DataClient::where('nama_brand', 'Garuda Food')->firstOrFail();
    expect($client->type)->toBe('direct')
        ->and($client->category)->toBe('FMCG')
        ->and($client->pic_internal_sales_id)->toBe($sales->id)
        ->and((string) $client->date_outreach)->toBe('2023-03-15');

    // Jalankan ulang: baris yang sama diperbarui, bukan dibuat lagi.
    $migrasi->persist($items);
    expect(DataClient::where('nama_brand', 'Garuda Food')->count())->toBe(1);
});

it('sel kosong tidak menghapus data yang sudah ada', function () {
    DataClient::create(['nama_brand' => 'Garuda Food', 'type' => 'direct', 'category' => 'FMCG']);

    $migrasi = new ClientSheetMigration();
    $migrasi->persist($migrasi->parseRows(sheetRows([
        ['Garuda Food', 'direct', '', 'P1', '', '', '', ''],
    ])));

    $client = DataClient::where('nama_brand', 'Garuda Food')->firstOrFail();
    expect($client->category)->toBe('FMCG')->and($client->priority)->toBe('P1');
});

it('membuat sales yang belum ada di master lalu menautkannya', function () {
    $migrasi = new ClientSheetMigration();
    $hasil = $migrasi->persist($migrasi->parseRows(sheetRows([
        ['Garuda Food', 'direct', '', '', '', 'Salwa', '', ''],
    ])));

    $sales = BvSalesList::where('nama_sales', 'Salwa')->first();

    expect($hasil['success'])->toBe(1)
        ->and($sales)->not->toBeNull()
        ->and($hasil['notes'][0])->toContain('Sales baru dibuat')
        ->and(DataClient::where('nama_brand', 'Garuda Food')->value('pic_internal_sales_id'))->toBe($sales->id);
});

it('kolom agency berisi nama agency: barisnya dibuat lalu ditautkan, "Direct" diabaikan', function () {
    $judul = ['Client/Brand', 'Brand / Agency', 'PIC Internal', 'Months'];

    $migrasi = new ClientSheetMigration();
    $migrasi->persist($migrasi->parseRows([
        $judul,
        ['Ofero', 'Direct', 'Gress', 'July 2025'],
        ['Wardah', 'TBWA', 'Aliy', 'Aug 2025'],
    ]));

    $ofero = DataClient::where('nama_brand', 'Ofero')->firstOrFail();
    $wardah = DataClient::where('nama_brand', 'Wardah')->firstOrFail();
    $tbwa = DataClient::where('nama_brand', 'TBWA')->first();

    expect($ofero->has_agency)->toBeFalsy()
        // "Direct" bukan nama agency — jangan sampai terbuat baris agency bernama itu.
        ->and(DataClient::where('nama_brand', 'Direct')->exists())->toBeFalse()
        ->and($tbwa?->type)->toBe('agency')
        ->and($wardah->agency_client_id)->toBe($tbwa->id)
        ->and($wardah->has_agency)->toBeTrue()
        // Bulan dari kolom Months jadi tanggal awal bulan itu.
        ->and((string) $wardah->date_outreach)->toBe('2025-08-01')
        // Daftar "Brand yang Di-handle" milik agency ikut terisi — itu yang
        // dihitung kolom Brand Di-handle di tab Agency, bukan agency_client_id.
        ->and(collect($tbwa->fresh()->agency_brands)->pluck('nama_brand')->all())->toBe(['Wardah']);
});

it('brand yang sama tidak masuk dua kali ke daftar agency', function () {
    $judul = ['Client/Brand', 'Brand / Agency', 'Months'];

    $migrasi = new ClientSheetMigration();
    // Satu brand, dua baris campaign, agency yang sama.
    $migrasi->persist($migrasi->parseRows([
        $judul,
        ['Wardah', 'TBWA', 'July 2025'],
        ['Wardah', 'TBWA', 'Aug 2025'],
        ['Mobil1', 'TBWA', 'Aug 2025'],
    ]));

    $tbwa = DataClient::where('nama_brand', 'TBWA')->where('type', 'agency')->firstOrFail();

    expect(collect($tbwa->agency_brands)->pluck('nama_brand')->sort()->values()->all())
        ->toBe(['Mobil1', 'Wardah'])
        ->and(DataClient::where('nama_brand', 'Wardah')->count())->toBe(1);
});

it('satu brand di banyak bulan menyimpan bulan paling awal', function () {
    $judul = ['Client/Brand', 'Months'];

    $migrasi = new ClientSheetMigration();
    // Urutan sengaja tidak menaik, supaya yang diuji benar-benar "paling awal".
    $migrasi->persist($migrasi->parseRows([
        $judul,
        ['Ofero', 'Oct 2025'],
        ['Ofero', 'July 2025'],
        ['Ofero', 'Sept 2025'],
    ]));

    expect(DataClient::where('nama_brand', 'Ofero')->count())->toBe(1)
        ->and((string) DataClient::where('nama_brand', 'Ofero')->value('date_outreach'))->toBe('2025-07-01');
});

it('preview memberi tahu saat tidak ada judul kolom yang dikenali', function () {
    Gate::before(fn() => true);

    $this->mock(GoogleSheetReader::class, fn($mock) => $mock
        ->shouldReceive('sheetNames')->andReturn(['Sheet1'])
        ->shouldReceive('readRows')->andReturn([['Kolom A', 'Kolom B'], ['x', 'y']]));

    Livewire::actingAs(migrasiUser())
        ->test(MigrasiData::class)
        ->set('data.sheetLink', 'https://docs.google.com/spreadsheets/d/1AbCdEfGhIjKlMnOpQrStUvWxYz012345/edit')
        ->call('preview')
        ->assertSet('previewed', false)
        ->assertSet('errorMessage', fn($v) => str_contains((string) $v, 'Tidak ada judul kolom yang dikenali'));
});

it('preview lalu migrasi per-chunk sampai habis', function () {
    Gate::before(fn() => true);

    $baris = [];
    for ($i = 1; $i <= 30; $i++) {
        $baris[] = ["Brand {$i}", 'direct', 'FMCG', 'P1', 'won', '', '', ''];
    }

    $this->mock(GoogleSheetReader::class, fn($mock) => $mock
        ->shouldReceive('sheetNames')->andReturn(['Sheet1'])
        ->shouldReceive('readRows')->andReturn(sheetRows($baris)));

    $halaman = Livewire::actingAs(migrasiUser())
        ->test(MigrasiData::class)
        ->set('data.sheetLink', 'https://docs.google.com/spreadsheets/d/1AbCdEfGhIjKlMnOpQrStUvWxYz012345/edit')
        ->call('preview')
        ->assertSet('previewed', true)
        ->assertSet('totalItems', 30)
        ->call('startMigration');

    // 30 baris / chunk 25 → dua panggilan; yang kedua menutup migrasi.
    expect($halaman->call('processChunk')->get('processed'))->toBe(25);

    $halaman->call('processChunk')
        ->assertSet('finished', true)
        ->assertSet('success', 30);

    expect(DataClient::count())->toBe(30);
});

/* -------------------------------------------------------------------------
 | Pipeline → BvSales (Sales Activity Tracker)
 * ---------------------------------------------------------------------- */

it('memigrasikan Pipeline jadi baris Sales Activity Tracker', function () {
    $judul = ['Months', 'Client/Brand', 'Brand / Agency', 'Campagin Name', 'PIC', 'Stage/Status', 'Amount_IDR', 'Deadline Date', 'Amount_Deals'];

    $m = new \App\Service\PipelineSheetMigration();
    $hasil = $m->persist($m->parseRows([
        $judul,
        ['July 2025', 'Ofero', 'Direct', 'Ofero x PRJ 2025', 'Gerry', 'Finish / Paid', 500000000, 45839, 508000000],
        ['Sept 2025', 'Mobil1', 'TBWA', 'Mobil1 Visit Care Car', 'Wina', 'Lost', 6000000, '', ''],
    ]));

    expect($hasil['success'])->toBe(2)->and($hasil['failed'])->toBe(0);

    $ofero = \App\Models\BvSales::where('event_name', 'Ofero x PRJ 2025')->firstOrFail();

    expect($ofero->company_name)->toBe('Ofero')
        // "Direct" bukan nama agency.
        ->and($ofero->related_client_name)->toBeNull()
        ->and($ofero->status)->toBe(\App\Enums\SalesStatus::PAID)
        ->and((float) $ofero->deal_value)->toBe(508000000.0)
        ->and($ofero->campaign_month)->toBe(7)
        ->and($ofero->campaign_year)->toBe(2025)
        ->and($ofero->salesList->nama_sales)->toBe('Gerry');

    $mobil = \App\Models\BvSales::where('event_name', 'Mobil1 Visit Care Car')->firstOrFail();
    expect($mobil->related_client_name)->toBe('TBWA')
        ->and($mobil->status)->toBe(\App\Enums\SalesStatus::CLOSE_LOSE);

    // Idempoten: dijalankan ulang tidak menggandakan.
    $m->persist($m->parseRows([$judul, ['July 2025', 'Ofero', 'Direct', 'Ofero x PRJ 2025', 'Gerry', 'Finish / Paid', 500000000, 45839, 508000000]]));
    expect(\App\Models\BvSales::where('event_name', 'Ofero x PRJ 2025')->count())->toBe(1);
});

/* -------------------------------------------------------------------------
 | Campaigns → BvCampign
 * ---------------------------------------------------------------------- */

it('menautkan campaign ke DataClient yang sudah ada', function () {
    $client = DataClient::create(['nama_brand' => 'Ofero', 'type' => 'direct']);

    $judul = ['Campaign_Name', 'Client', 'Brand/Agency', 'PIC', 'Start_Date', 'End_Date', 'Budget Deals_IDR', 'Real Cost_IDR', 'Status Campaign'];

    $m = new \App\Service\CampaignSheetMigration();
    $m->persist($m->parseRows([
        $judul,
        ['Ofero PRJ x Ride & Fest', 'Ofero', 'Direct', 'Gerry', 45829, 45935, 508000000, 242075374, 'Finish/Paid'],
    ]));

    $campaign = \App\Models\BvCampign::where('campaign_name', 'Ofero PRJ x Ride & Fest')->firstOrFail();

    expect($campaign->client_id)->toBe($client->id)
        // Relasi dua arah: client ini punya campaign tersebut.
        ->and($client->fresh()->campaigns()->pluck('campaign_name')->all())->toBe(['Ofero PRJ x Ride & Fest'])
        ->and($campaign->campaign_type)->toBe(\App\Models\BvCampign::TYPE_INTERNAL)
        ->and($campaign->status)->toBe('completed')
        ->and((float) $campaign->deal_value)->toBe(508000000.0)
        ->and((float) $campaign->total_cost)->toBe(242075374.0)
        ->and((string) $campaign->start_date)->toContain('2025-06-21');
});

it('memperingatkan nama client yang cuma beda tipis, tanpa menggabung diam-diam', function () {
    DataClient::create(['nama_brand' => 'ITDC - Injourney', 'type' => 'agency']);

    $judul = ['Campaign_Name', 'Client'];

    $m = new \App\Service\CampaignSheetMigration();
    $hasil = $m->persist($m->parseRows([
        $judul,
        ['MotoGP 2025', 'ITDC - Injouney'],   // kurang huruf 'r'
    ]));

    expect(collect($hasil['notes'])->contains(fn($n) => str_contains($n, 'mirip sekali')))->toBeTrue()
        // Tetap dibuat, bukan ditebak-tebak — user yang memutuskan digabung atau tidak.
        ->and(DataClient::where('nama_brand', 'ITDC - Injouney')->exists())->toBeTrue();
});
