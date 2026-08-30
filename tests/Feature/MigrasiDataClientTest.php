<?php

use App\Filament\Pages\MigrasiDataClient;
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
        ->and($items[1]['_note'])->toContain('Nama brand kosong');
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

it('mencatat sales yang tidak ada di master, bukan menggagalkan barisnya', function () {
    $migrasi = new ClientSheetMigration();
    $hasil = $migrasi->persist($migrasi->parseRows(sheetRows([
        ['Garuda Food', 'direct', '', '', '', 'Sales Hantu', '', ''],
    ])));

    expect($hasil['success'])->toBe(1)
        ->and($hasil['notes'][0])->toContain('Sales Hantu')
        ->and(DataClient::where('nama_brand', 'Garuda Food')->value('pic_internal_sales_id'))->toBeNull();
});

it('preview memberi tahu saat tidak ada judul kolom yang dikenali', function () {
    Gate::before(fn() => true);

    $this->mock(GoogleSheetReader::class, fn($mock) => $mock
        ->shouldReceive('sheetNames')->andReturn(['Sheet1'])
        ->shouldReceive('readRows')->andReturn([['Kolom A', 'Kolom B'], ['x', 'y']]));

    Livewire::actingAs(migrasiUser())
        ->test(MigrasiDataClient::class)
        ->set('data.sheetLink', 'https://docs.google.com/spreadsheets/d/1AbCdEfGhIjKlMnOpQrStUvWxYz012345/edit')
        ->call('preview')
        ->assertSet('previewed', false)
        ->assertSet('errorMessage', fn($v) => str_contains((string) $v, 'Nama Brand'));
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
        ->test(MigrasiDataClient::class)
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
