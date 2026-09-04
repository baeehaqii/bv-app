<?php

use App\Enums\ClientStatus;
use App\Enums\SalesStatus;
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
        ->and($items[0]['status_client'])->toBe(ClientStatus::WON_ON_GOING->value)
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

it('menemukan baris judul walau tidak di baris pertama', function () {
    // Bentuk sheet (KOL) Project - Planning: judul besar, baris total, catatan,
    // baru judul kolomnya di baris keempat.
    $rows = [
        ['', '', 'PLANNING TRACKER - Q2'],
        ['', '', '', '', '', '', '', '', '', '', '', 'TOTAL', 2727500000],
        ['*Di isi oleh BD', '', '', '', '', '', '', '', '', 'After Discuss BD & KOL'],
        ['NO', 'Brief Dates', 'Brand', 'Brand/Agency Company', 'Campaign', 'Product Type', 'PIC BD', 'EXT Sheet', 'Core Services', 'DEADLINE SUBMIT', 'INT Sheet', 'Status KOL Teams', 'Budget Plan from Client'],
        ['', "May'26"],
        [1, 46147, 'Arummi', 'Direct', 'Social Media Content UGC', 'Food & Drinks', 'Gerry', '', '', '', '', 'WON - ON GOING', 15000000],
    ];

    $m = new \App\Service\PipelineSheetMigration();

    expect($m->headerRowIndex($rows))->toBe(3);

    $items = $m->parseRows($rows);
    $isi = collect($items)->firstWhere('event_name', 'Social Media Content UGC');

    expect($isi)->not->toBeNull()
        ->and($isi['company_name'])->toBe('Arummi')
        ->and($isi['status'])->toBe(\App\Enums\SalesStatus::CAMPAIGN_LIVE)
        ->and((float) $isi['budget_propose'])->toBe(15000000.0)
        // Nomor baris mengikuti tampilan Google Sheets, bukan indeks array.
        ->and($isi['_row'])->toBe(6);
});

it('mencatat status yang tidak dikenali alih-alih menebak kolom kanban', function () {
    $judul = ['Campaign', 'Brand', 'Status KOL Teams'];

    $m = new \App\Service\PipelineSheetMigration();
    $items = $m->parseRows([
        $judul,
        ['Campaign A', 'Arummi', 'ENTAH APA'],
        ['Campaign B', 'Arummi', 'LOST'],
    ]);

    expect($items[0]['status'])->toBeNull()
        ->and($items[1]['status'])->toBe(SalesStatus::CLOSE_LOSE)
        ->and($m->statusTakDikenal())->toBe(['ENTAH APA']);
});

it('sembilan nilai dropdown STATUS sheet BD semuanya punya kolom kanban', function () {
    $judul = ['Campaign', 'Brand', 'Brand/Agency Company', 'PIC BD', 'STATUS', 'Budget Plan from Clients', 'AMOUNT DEALS'];

    $m = new \App\Service\PipelineSheetMigration();
    $items = $m->parseRows([
        $judul,
        ['Blue Band Dracin', 'Blue Band', 'IPG', 'Gerry', 'FINISH', '42.200.000', '42.200.000'],
        ['Gimbory KOL May', 'Gimbory', 'CPXI', 'Gerry', 'LOST', '50.000.000', ''],
        ['Threads Campaign', 'GoFood', 'Curve', 'Wina', 'WON - ON GOING', '35.000.000', '35.000.000'],
        ['Belfoods Nugget', 'Belfoods', 'Direct', 'Wina', 'AWAITING FEEDBACK', '150.000.000', ''],
        ['Bir Kawan Senja', 'Bir Kawan', 'Direct', 'Wina', 'ON PROGRESS', '100.000.000', ''],
        ['KOL Listing', 'Sosis Eat', 'Anymind', 'Wina', 'SENT PARALLEL', '50.000.000', ''],
        ['Live Streaming', 'Natasha', 'Semut', 'Wina', 'HOLD', '64.000.000', ''],
        ['Pegadaian Visit', 'Pegadaian', 'Cognitiv', 'Wina', 'COMPLETE - SENT TO CLIENT', '30.000.000', ''],
        ['Talent Shoot', 'Planet Ban', 'Direct', 'Wina', 'REVISION', '5.000.000', ''],
    ]);

    expect(collect($items)->pluck('status')->all())->toBe([
        SalesStatus::REPORTING,        // FINISH — campaign kelar, pembayaran tidak dinyatakan sheet
        SalesStatus::CLOSE_LOSE,       // LOST
        SalesStatus::CAMPAIGN_LIVE,    // WON - ON GOING
        SalesStatus::NEGOTIATION,      // AWAITING FEEDBACK
        SalesStatus::PROPOSAL_BUILDING,// ON PROGRESS
        SalesStatus::NEGOTIATION,      // SENT PARALLEL
        SalesStatus::NEGOTIATION,      // HOLD
        SalesStatus::NEGOTIATION,      // COMPLETE - SENT TO CLIENT
        SalesStatus::PROPOSAL_BUILDING,// REVISION
    ])->and($m->statusTakDikenal())->toBe([]);

    // Lima kolom lain yang diminta ikut terisi, bukan cuma status.
    $m->persist($items);
    $blueBand = \App\Models\BvSales::where('event_name', 'Blue Band Dracin')->firstOrFail();

    expect($blueBand->company_name)->toBe('Blue Band')
        ->and($blueBand->related_client_name)->toBe('IPG')
        ->and($blueBand->salesList->nama_sales)->toBe('Gerry')
        ->and((float) $blueBand->budget_propose)->toBe(42_200_000.0)
        ->and((float) $blueBand->deal_value)->toBe(42_200_000.0)
        ->and($blueBand->status)->toBe(SalesStatus::REPORTING)
        // "Direct" tetap bukan nama agency
        ->and(\App\Models\BvSales::where('event_name', 'Talent Shoot')->value('related_client_name'))->toBeNull();
});

it('membedakan kolom rupiah dari kolom persen di sebelahnya', function () {
    // Dua judul yang menyusut jadi nyaris sama: yang rupiah masuk ke
    // projected_nett_margin, yang "%" ke margin (BvSales.margin menyimpan PERSEN).
    $judul = ['Campaign', 'Brand', 'Projected Nett Margin', 'Projected Nett Margin %'];

    $m = new \App\Service\PipelineSheetMigration();

    expect($m->mapHeaders($judul))->toBe([
        0 => 'event_name',
        1 => 'company_name',
        2 => 'projected_nett_margin',
        3 => 'margin',
    ])->and($m->unmappedHeaders($judul))->toBe([]);

    // Sheet menulis 0,6533; yang disimpan 65,33 persen.
    $items = $m->parseRows([$judul, ['Campaign A', 'Arummi', 9800000, 0.6533]]);

    expect((float) $items[0]['margin'])->toBe(65.33)
        ->and((float) $items[0]['projected_nett_margin'])->toBe(9_800_000.0);
});

it('satu field tetap hanya boleh diisi satu kolom', function () {
    $judul = ['Campaign', 'Status', 'Status KOL Teams'];

    $m = new \App\Service\PipelineSheetMigration();

    // Kolom paling kiri menang; sisanya dilaporkan sebagai tidak terpakai.
    expect($m->mapHeaders($judul))->toBe([0 => 'event_name', 1 => 'status'])
        ->and($m->unmappedHeaders($judul))->toBe(['Status KOL Teams']);
});

it('menawarkan spreadsheet yang di-share ke service account, bukan cuma tempel link', function () {
    Gate::before(fn() => true);
    \Illuminate\Support\Facades\Cache::forget('migrasi:daftar-spreadsheet');

    $this->mock(GoogleSheetReader::class, fn($mock) => $mock
        ->shouldReceive('listSpreadsheets')->once()->andReturn([
            '1AbCdEfGhIjKlMnOpQrStUvWxYz012345' => 'Sales Pipeline 2026',
            '1ZyXwVuTsRqPoNmLkJiHgFeDcBa543210' => 'KOL Planning',
        ])
        ->shouldReceive('sheetNames')->andReturn(['Pipeline']));

    $halaman = Livewire::actingAs(migrasiUser())
        ->test(MigrasiData::class)
        ->set('data.sumber', 'daftar');

    expect($halaman->get('daftarSpreadsheet'))->toHaveCount(2);

    // Memilih dari daftar mengisi sheetLink, jadi jalur selanjutnya sama persis
    // dengan mode tempel link.
    $halaman->set('data.spreadsheetId', '1AbCdEfGhIjKlMnOpQrStUvWxYz012345')
        ->assertSet('data.sheetLink', '1AbCdEfGhIjKlMnOpQrStUvWxYz012345');
});

it('memberi tahu kalau daftar spreadsheet tidak bisa diambil', function () {
    Gate::before(fn() => true);
    \Illuminate\Support\Facades\Cache::forget('migrasi:daftar-spreadsheet');

    $this->mock(GoogleSheetReader::class, fn($mock) => $mock
        ->shouldReceive('listSpreadsheets')->andThrow(new RuntimeException('Google Drive API has not been used')));

    Livewire::actingAs(migrasiUser())
        ->test(MigrasiData::class)
        ->set('data.sumber', 'daftar')
        ->assertSet('daftarSpreadsheet', [])
        ->assertSet('errorMessage', fn($v) => str_contains((string) $v, 'Drive API'));
});

/* -------------------------------------------------------------------------
 | KOL List → Media Plan Internal
 * ---------------------------------------------------------------------- */

/** Master PPh seperti di produksi; sheet menulis koefisien 0,98 + tax 0,11 = PT PKP. */
function masterPphSeperti(): void
{
    \App\Models\MasterPph::firstOrCreate(
        ['name' => 'Pribadi'],
        ['entity_type' => 'Pribadi', 'coefficient' => 0.975, 'include_ppn' => false],
    );
    \App\Models\MasterPph::firstOrCreate(
        ['name' => 'PT PKP'],
        ['entity_type' => 'PT PKP', 'coefficient' => 0.980, 'include_ppn' => true, 'ppn_percent' => 11],
    );
}

/**
 * Susunan sheet "[INT] ... - KOL List" seperti aslinya:
 * judul kolom di baris 2, sub-judul Qty/Item di baris 3, dan DUA blok scope of
 * work berdampingan — "Request Client" (N/O/P) dan rencana internal (T/U/V).
 * Kolom P berisi harga ke client, V berisi cost KOL; keduanya berjudul "Rate".
 */
function kolListRows(): array
{
    $kosong = array_fill(0, 30, '');

    $judul = $kosong;
    foreach ([0 => 'No', 1 => 'PIC', 2 => 'Status', 3 => 'Username', 4 => 'Link', 5 => 'Channel',
        6 => 'Categories', 7 => 'Followers', 8 => 'Tier', 9 => 'ER %', 10 => 'Avg Views', 11 => 'Engagement',
        12 => 'Scope of Work', 15 => 'Rate', 16 => 'TOP', 17 => 'DOM', 18 => 'Notes',
        19 => 'Scope of Work', 21 => 'Rate', 22 => 'Subtotal Rate', 23 => 'Gross Up PPH Coefficient',
        24 => 'Tax', 25 => 'MU PPh*', 26 => 'MU**', 27 => 'Published Rate***', 28 => 'Rounded',
        29 => 'Margin %'] as $k => $v) {
        $judul[$k] = $v;
    }

    $subJudul = $kosong;
    $subJudul[12] = 'Request Client';
    $subJudul[13] = 'Qty';
    $subJudul[14] = 'Item';
    $subJudul[19] = 'Qty';
    $subJudul[20] = 'Item';

    $baris = function (array $isi) use ($kosong) {
        $row = $kosong;
        foreach ($isi as $k => $v) {
            $row[$k] = $v;
        }

        return $row;
    };

    return [
        $baris([9 => 'MEDIA PLAN - Bir Kawan Senja']),
        $judul,
        $subJudul,
        // KOL pertama + SOW pertamanya. Kolom P (15) sengaja diisi angka lain:
        // itu harga ke client, dan TIDAK boleh terbaca sebagai rate KOL.
        $baris([0 => 1, 1 => 'Sheila', 2 => 'Approaching', 3 => 'Bagus Gandhi',
            4 => 'https://instagram.com/bagus', 5 => 'Instagram', 6 => 'Lifestyle', 7 => 12000,
            8 => 'Nano', 9 => 4.5, 10 => 8000, 11 => 540,
            13 => 1, 14 => 'IG Reels', 15 => 3400000, 16 => 20,
            19 => 1, 20 => 'IG Reels', 21 => 1500000, 22 => 1500000, 23 => 0.98, 24 => 0.11,
            25 => 1695612.24, 26 => 3391224.49, 27 => 3391224.49, 28 => 3500000, 29 => 0.5155]),
        $baris([13 => 1, 14 => 'IG Story with Link', 15 => 1200000,
            19 => 1, 20 => 'IG Story with Link', 21 => 500000, 22 => 500000, 23 => 0.98, 24 => 0.11,
            25 => 565204.08, 26 => 1130408.16, 27 => 1130408.16, 28 => 1200000, 29 => 0.5290]),
        $baris([19 => 1, 20 => 'Visit Store', 21 => 0, 22 => 0, 23 => 0.98, 24 => 0.11]),
        $baris([0 => 2, 1 => 'Salwa', 2 => 'Approaching', 3 => 'Ivan Wahyu',
            4 => 'https://instagram.com/ivan', 5 => 'Instagram', 6 => 'Food', 7 => 9000,
            8 => 'Nano', 9 => 3.2, 10 => 5000, 11 => 288,
            19 => 1, 20 => 'IG Reels', 21 => 1200000, 22 => 1200000, 23 => 0.98, 24 => 0.11]),
    ];
}

it('mengambil blok scope of work internal, bukan blok Request Client', function () {
    $m = new \App\Service\MediaPlanSheetMigration();
    $peta = $m->mapHeaders($m->headerRow(kolListRows()));

    // Tiga kolom tepat sebelum "Subtotal Rate" (indeks 22), bukan blok kiri.
    expect($peta[19])->toBe('sow_qty')
        ->and($peta[20])->toBe('sow_item')
        ->and($peta[21])->toBe('rate')
        ->and($peta)->not->toHaveKey(15);

    $items = $m->parseRows(kolListRows());
    // 1.500.000 = kolom V (cost KOL), bukan 3.400.000 dari kolom P (harga client).
    expect($items[0]['scope_items'][0]['rate'])->toBe(1500000.0);
});

it('menggabungkan baris scope of work ke KOL di atasnya', function () {
    $m = new \App\Service\MediaPlanSheetMigration();
    $items = $m->parseRows(kolListRows());

    // Enam baris data, tapi hanya DUA KOL — sisanya lanjutan SOW.
    expect($items)->toHaveCount(2);

    $bagus = $items[0];
    expect($bagus['name'])->toBe('Bagus Gandhi')
        ->and($bagus['channel'])->toBe('Instagram')
        ->and($bagus['followers'])->toBe(12000)
        ->and(collect($bagus['scope_items'])->pluck('item')->all())
        ->toBe(['IG Reels', 'IG Story with Link', 'Visit Store'])
        ->and($bagus['sow_ringkas'])->toContain('IG Story with Link');

    expect($items[1]['name'])->toBe('Ivan Wahyu')
        ->and($items[1]['scope_items'])->toHaveCount(1);
});

it('menyimpan KOL beserta total qty dan rate seluruh scope of work-nya', function () {
    masterPphSeperti();
    $sales = \App\Models\BvSales::create(['event_name' => 'Bir Kawan Senja', 'company_name' => 'Multi Bintang']);
    $plan = \App\Models\MediaPlan::create([
        'bv_sales_id' => $sales->id,
        'campaign_name' => 'Bir Kawan Senja',
        'brand' => 'Multi Bintang',
        'quotation_number' => 'BVN/QUOT/TEST/001',
    ]);

    $m = (new \App\Service\MediaPlanSheetMigration())->untukSales($sales->id);
    $hasil = $m->persist($m->parseRows(kolListRows()));

    expect($hasil['success'])->toBe(2)->and($hasil['failed'])->toBe(0);

    $kol = \App\Models\MediaPlanKol::where('name', 'Bagus Gandhi')->firstOrFail();

    expect($kol->media_plan_id)->toBe($plan->id)
        ->and($kol->pic)->toBe('Sheila')
        ->and($kol->scope_items)->toBe(['IG Reels', 'IG Story with Link', 'Visit Store']);

    // Idempoten: kunci nama + channel, jadi jalan ulang tidak menggandakan —
    // baik baris KOL-nya maupun budget item-nya.
    $m->persist($m->parseRows(kolListRows()));
    expect(\App\Models\MediaPlanKol::where('media_plan_id', $plan->id)->count())->toBe(2)
        ->and($kol->fresh()->internalBudgetItems()->count())->toBe(3);
});

it('mengisi KOL Data dan rate card dari sheet, bukan cuma baris Media Plan', function () {
    masterPphSeperti();
    $sales = \App\Models\BvSales::create(['event_name' => 'Bir Kawan Senja', 'company_name' => 'Multi Bintang']);

    $m = (new \App\Service\MediaPlanSheetMigration())->untukSales($sales->id);
    $m->persist($m->parseRows(kolListRows()));

    $dataKol = \App\Models\DataKol::where('username', 'Bagus Gandhi')->firstOrFail();

    expect($dataKol->channel)->toBe('Instagram')
        ->and((int) $dataKol->followers)->toBe(12000)
        // TOP dari kolom Q sheet — term of payment KOL, dalam hari.
        ->and((int) $dataKol->top)->toBe(20)
        ->and($dataKol->link_userprofile)->toBe('https://instagram.com/bagus')
        ->and((int) $dataKol->average_views)->toBe(8000);

    // Rate tiap SOW jadi rate card — dari sanalah budget item mengambil rate-nya.
    expect($dataKol->rateCards()->pluck('rate', 'sow')->map(fn($r) => (float) $r)->all())
        ->toBe(['IG Reels' => 1500000.0, 'IG Story with Link' => 500000.0]);

    // Baris Media Plan menunjuk ke baris KOL Data itu, bukan berdiri sendiri.
    expect(\App\Models\MediaPlanKol::where('name', 'Bagus Gandhi')->value('data_kol_id'))->toBe($dataKol->id);
});

it('memakai angka hasil hitungan sheet apa adanya, tidak menghitung ulang', function () {
    masterPphSeperti();
    $sales = \App\Models\BvSales::create(['event_name' => 'Bir Kawan Senja', 'company_name' => 'Multi Bintang']);

    $m = (new \App\Service\MediaPlanSheetMigration())->untukSales($sales->id);
    $m->persist($m->parseRows(kolListRows()));

    $kol = \App\Models\MediaPlanKol::where('name', 'Bagus Gandhi')->firstOrFail();
    $reels = $kol->internalBudgetItems()->where('scope_item', 'IG Reels')->firstOrFail();

    // Koefisien 0,98 + tax 0,11 di sheet = "PT PKP".
    expect($reels->vendor_tax_type)->toBe('PT PKP')
        // rate_base LANGSUNG dari kolom Rate blok internal, bukan blok client.
        ->and((float) $reels->rate_base)->toBe(1500000.0);

    // Rounded di sheet sengaja 3.500.000, sedangkan hitung ulang menghasilkan
    // 3.400.000. Yang tersimpan harus angka sheet — itu inti "ikuti spreadsheet".
    expect((float) $reels->rounded)->toBe(3500000.0)
        ->and(round((float) $reels->mu_pph, 2))->toBe(1695612.24)
        ->and(round((float) $reels->actual_margin_percent, 2))->toBe(51.55)
        ->and($reels->imported_at)->not->toBeNull();

    // SOW tanpa rate tetap jadi budget item, nilainya nol — bukan hilang.
    expect((float) $kol->internalBudgetItems()->where('scope_item', 'Visit Store')->value('rate_base'))->toBe(0.0);
});

it('baris migrasi yang disunting lewat sistem kembali ikut hitungan sistem', function () {
    masterPphSeperti();
    $sales = \App\Models\BvSales::create(['event_name' => 'Bir Kawan Senja', 'company_name' => 'Multi Bintang']);

    $m = (new \App\Service\MediaPlanSheetMigration())->untukSales($sales->id);
    $m->persist($m->parseRows(kolListRows()));

    $reels = \App\Models\MediaPlanKol::where('name', 'Bagus Gandhi')->firstOrFail()
        ->internalBudgetItems()->where('scope_item', 'IG Reels')->firstOrFail();

    // Menyimpan tanpa mengubah input: angka sheet tetap dipertahankan.
    $reels->update(['notes' => 'dicek tim']);
    expect((float) $reels->fresh()->rounded)->toBe(3500000.0);

    // Begitu rate-nya disunting, penanda lepas dan sistem menghitung ulang.
    $reels->update(['rate_base' => 2000000]);
    $reels->refresh();

    expect($reels->imported_at)->toBeNull()
        ->and(round((float) $reels->mu_pph, 2))->toBe(round(2000000 / 0.98 + 2000000 * 0.11, 2))
        ->and((float) $reels->rounded)->not->toBe(3500000.0);
});

it('menandai baris hasil migrasi supaya boleh ber-rate 0', function () {
    masterPphSeperti();
    $sales = \App\Models\BvSales::create(['event_name' => 'Bir Kawan Senja', 'company_name' => 'Multi Bintang']);

    $m = (new \App\Service\MediaPlanSheetMigration())->untukSales($sales->id);
    $m->persist($m->parseRows(kolListRows()));

    expect(\App\Models\MediaPlanKol::whereNull('imported_at')->count())->toBe(0)
        ->and(\App\Models\MediaPlanKol::where('name', 'Bagus Gandhi')->value('imported_at'))->not->toBeNull();
});

it('guard rate card melewati baris migrasi tapi tetap menahan baris manual', function () {
    // guardKolRateCards() protected; dibuka lewat subclass anonim seadanya.
    $halaman = new class extends \App\Filament\Resources\MediaPlans\Pages\EditMediaPlan
    {
        public array $ditahan = [];

        public function __construct() {}

        public function cek(array $kols): void
        {
            $this->guardKolRateCards($kols);
        }

        public function halt(bool $shouldRollbackDatabaseTransaction = false): void
        {
            $this->ditahan[] = true;
        }
    };

    // Baris dari spreadsheet: rate card belum ada, tapi tidak boleh menahan simpan.
    $halaman->cek([[
        'name' => 'Bagus Gandhi',
        'channel' => 'Instagram',
        'scope_items' => ['IG Reels'],
        'imported_at' => now(),
    ]]);

    expect($halaman->ditahan)->toBeEmpty();

    // Baris yang diinput manual dengan SOW tapi tanpa rate card: tetap ditahan.
    $halaman->cek([[
        'name' => 'Ivan Wahyu',
        'channel' => 'Instagram',
        'scope_items' => ['IG Reels'],
        'imported_at' => null,
    ]]);

    expect($halaman->ditahan)->toHaveCount(1);
});

it('memisahkan kolom yang sengaja dilewati dari yang benar-benar tidak dikenali', function () {
    $m = new \App\Service\MediaPlanSheetMigration();
    $pisah = $m->pisahHeader($m->headerRow(kolListRows()));

    // Blok Request Client + angka turunan: keputusan, bukan kegagalan baca.
    // Yang tersisa hanya blok "Request Client": scope of work versi client,
    // daftar yang berbeda dari rencana internal dan tidak punya kolom penampung.
    expect($pisah['diabaikan'])->toContain('Scope of Work', 'Qty', 'Item', 'Rate')
        // Angka hasil hitungan sheet kini DIAMBIL, jadi tidak lagi dilewati.
        ->and($pisah['diabaikan'])->not->toContain('Subtotal Rate', 'Rounded', 'Margin %')
        ->and($pisah['tidak_dikenali'])->not->toContain('Subtotal Rate');
});

it('menandai baris yang followers-nya tertulis di kolom Tier', function () {
    $rows = kolListRows();
    // Tiru cacat di sheet asli: kolom Followers (7) kosong, angkanya di Tier (8).
    $rows[3][7] = '';
    $rows[3][8] = 4133;

    $items = (new \App\Service\MediaPlanSheetMigration())->parseRows($rows);

    expect($items[0]['_note'])->toContain('kemungkinan tertukar kolom')
        // Ditandai saja, bukan ditebak lalu dipindahkan diam-diam.
        ->and($items[0]['followers'])->toBe(0)
        ->and($items[0]['tier'])->toBe(4133);
});

/* -------------------------------------------------------------------------
 | Tab Brief → FormBrief (tab Brief di Media Plan Internal)
 * ---------------------------------------------------------------------- */

/** Tab "Brief" bentuknya vertikal: label di kolom A, isinya di kolom B. */
function briefRows(): array
{
    return [
        ['Campaign Objective / Brief', "Bir Kawan Senja produk baru Multi Bintang.\n\nFokus Bali, awareness, softsell."],
        ['Creiteria of KOL', "Micro - Macro\n\npreferably male, TA beer drinker"],
        ['SOW', "- IG Reels\n- IG Story\n- Visit"],
        ['Budget', 'Open'],
        ['Deadline', 'Kamis in paralel'],
    ];
}

it('membaca tab Brief yang berbentuk vertikal, bukan tabel', function () {
    $m = new \App\Service\BriefSheetMigration();

    // Label ada di kolom A tiap baris, jadi indeks pemetaannya nomor BARIS.
    expect($m->mapHeaders($m->headerRow(briefRows())))
        ->toBe([0 => 'campaign_objective', 1 => 'criteria_of_kol', 2 => 'sow', 3 => 'budget', 4 => 'deadline']);

    $items = $m->parseRows(briefRows());

    // Satu sheet = satu brief, bukan lima baris.
    expect($items)->toHaveCount(1)
        ->and($items[0]['campaign_objective'])->toContain('Multi Bintang')
        ->and($items[0]['sow'])->toContain('IG Reels')
        ->and($items[0]['budget'])->toBe('Open')
        ->and($items[0]['deadline'])->toBe('Kamis in paralel');
});

it('menyimpan brief ke deal, dan tidak menggandakan saat diulang', function () {
    $sales = \App\Models\BvSales::create(['event_name' => 'Bir Kawan Senja', 'company_name' => 'Multi Bintang']);

    $m = (new \App\Service\BriefSheetMigration())->untukSales($sales->id);
    $hasil = $m->persist($m->parseRows(briefRows()));

    $brief = $sales->fresh()->formBrief;

    expect($hasil['success'])->toBe(1)
        ->and($brief)->not->toBeNull()
        // Dibuat lewat ensureFormBriefExists(), jadi judul & brand-nya terisi
        // sama seperti brief yang lahir dari alur normal.
        ->and($brief->campaign_name)->toBe('Bir Kawan Senja')
        ->and($brief->brand)->toBe('Multi Bintang')
        ->and($brief->criteria_of_kol)->toContain('beer drinker')
        ->and($brief->deadline)->toBe('Kamis in paralel');

    $m->persist($m->parseRows(briefRows()));
    expect(\App\Models\FormBrief::where('bv_sales_id', $sales->id)->count())->toBe(1);
});

it('sel kosong di tab Brief tidak menghapus isi yang sudah ada', function () {
    $sales = \App\Models\BvSales::create(['event_name' => 'Bir Kawan Senja', 'company_name' => 'Multi Bintang']);
    $sales->ensureFormBriefExists()->update(['budget' => 50000000, 'deadline' => 'Senin']);

    $rows = briefRows();
    $rows[4][1] = '';   // Deadline dikosongkan di sheet

    $m = (new \App\Service\BriefSheetMigration())->untukSales($sales->id);
    $m->persist($m->parseRows($rows));

    expect($sales->fresh()->formBrief->deadline)->toBe('Senin');
});

it('budget berupa teks dilaporkan, bukan dipaksa jadi nol', function () {
    $sales = \App\Models\BvSales::create(['event_name' => 'Bir Kawan Senja', 'company_name' => 'Multi Bintang']);

    $m = (new \App\Service\BriefSheetMigration())->untukSales($sales->id);
    // Sheet menulis "Open"; kolom form_briefs.budget bertipe angka.
    $hasil = $m->persist($m->parseRows(briefRows()));

    expect(collect($hasil['notes'])->contains(fn($n) => str_contains($n, 'berisi teks "Open"')))->toBeTrue()
        ->and($sales->fresh()->formBrief->budget)->toBeNull();

    // Angka yang benar-benar angka tetap masuk.
    $rows = briefRows();
    $rows[3][1] = 'Rp50.000.000';
    $m->persist($m->parseRows($rows));

    expect((int) $sales->fresh()->formBrief->budget)->toBe(50000000);
});

it('menolak menyimpan brief sebelum deal-nya dipilih', function () {
    $m = new \App\Service\BriefSheetMigration();
    $hasil = $m->persist($m->parseRows(briefRows()));

    expect($hasil['success'])->toBe(0)
        ->and($hasil['notes'][0])->toContain('belum dipilih')
        ->and(\App\Models\FormBrief::count())->toBe(0);
});

it('membaca judul kolom sheet PIPELINE BD: Brand/Agency Company, PIC BD, Product Type', function () {
    // Judul asli baris 4 sheet "PIPELINE BD - PROJECT - BVN 2026". Dulu hanya
    // kolom Brand & STATUS yang dikenali, sehingga semua agency (IPG, Curve,
    // CPXI, ...) hilang dan tiap baris jadi direct tanpa agency.
    $judul = [
        '', 'NO', 'Brief Dates', 'Brand', 'Brand/Agency Company', 'Campaign', 'Product Type',
        'PIC BD', 'EXT Sheet', 'Core Services', 'DEADLINE SUBMIT', 'INT Sheet', 'STATUS',
    ];

    $migrasi = new ClientSheetMigration();

    expect($migrasi->mapHeaders($judul))->toBe([
        3 => 'nama_brand',
        4 => 'agency_handled_by',
        6 => 'category',
        7 => 'pic_internal_sales',
        12 => 'status_client',
    ]);

    $migrasi->persist($migrasi->parseRows([
        ['', 'PIPELINE - Q1 - Q2 (H1 2026)'],
        ['', '', '', '', '', '', '', '', '', '', '', '', '', 'TOTAL'],
        ['', '', '', '', '', '', '', '', '', '', 'After Discuss BD'],
        $judul,
        ['', '1', '30 Jan 2026', 'Blue Band', 'IPG', 'Blue Band Dracin 5in1', 'FMCG', 'Gerry', '', 'KOL', '', '', 'FINISH'],
        ['', '5', '3 Mar 2026', 'Ofero', 'Ofero', 'Ofero Leasing', 'Automotive', 'Gerry', '', 'KOL', '', '', 'FINISH'],
        ['', '2', '12 May 2026', 'Gimbory', 'CPXI', 'Gimbory KOL May', 'Food & Drinks', 'Gerry', '', 'KOL', '', '', 'LOST'],
        ['', '6', '21 May 2026', 'GoFood', 'Curve', 'Threads Campaign', 'APP', 'Wina', '', 'KOL', '', '', 'FINISH'],
        ['', '5', '3 Mar 2026', 'Le Minerale', 'Direct', 'KOC Le Minerale', 'FMCG', 'Gerry', '', 'KOL', '', '', 'WON - ON GOING'],
    ]));

    $blueBand = DataClient::where('nama_brand', 'Blue Band')->firstOrFail();
    $goFood = DataClient::where('nama_brand', 'GoFood')->firstOrFail();
    $leMinerale = DataClient::where('nama_brand', 'Le Minerale')->firstOrFail();

    $ofero = DataClient::where('nama_brand', 'Ofero')->firstOrFail();

    expect(DataClient::where('type', 'agency')->pluck('nama_brand')->sort()->values()->all())
        // "Ofero / Ofero" = ditangani sendiri, bukan agency bernama sama
        ->toBe(['CPXI', 'Curve', 'IPG'])
        ->and($ofero->type)->toBe('direct')
        ->and($ofero->agency_client_id)->toBeNull()
        ->and($blueBand->agency_client_id)->toBe(DataClient::where('nama_brand', 'IPG')->value('id'))
        ->and($blueBand->category)->toBe('FMCG')
        ->and($blueBand->picInternalSales->nama_sales)->toBe('Gerry')
        ->and($goFood->picInternalSales->nama_sales)->toBe('Wina')
        // Nilai dropdown STATUS sheet masuk ke Status Client, apa adanya maknanya
        ->and($blueBand->status_client)->toBe(ClientStatus::FINISH->value)
        ->and($leMinerale->status_client)->toBe(ClientStatus::WON_ON_GOING->value)
        // Status Campaign TIDAK diisi dari sheet — itu tahap campaign internal
        ->and($leMinerale->status)->toBe(SalesStatus::NOT_STARTED->value)
        // "Direct" tetap bukan nama agency
        ->and($leMinerale->agency_client_id)->toBeNull()
        ->and(DataClient::where('nama_brand', 'Direct')->exists())->toBeFalse();
});

it('kosakata Status Client sama dengan dropdown sheet, termasuk penulisan pendeknya', function () {
    expect(ClientStatus::options())->toBe([
        'on_progress' => 'ON PROGRESS',
        'sent_parallel' => 'SENT PARALLEL',
        'hold' => 'HOLD',
        'complete_sent_to_client' => 'COMPLETE - SENT TO CLIENT',
        'revision' => 'REVISION',
        'awaiting_feedback' => 'AWAITING FEEDBACK',
        'lost' => 'LOST',
        'won_on_going' => 'WON - ON GOING',
        'finish' => 'FINISH',
    ]);

    // Variasi penulisan yang benar-benar muncul di sheet BD
    expect(ClientStatus::fromSheet('WON - ON GOING'))->toBe(ClientStatus::WON_ON_GOING)
        ->and(ClientStatus::fromSheet('WON – ON GOING'))->toBe(ClientStatus::WON_ON_GOING)
        ->and(ClientStatus::fromSheet('won'))->toBe(ClientStatus::WON_ON_GOING)
        ->and(ClientStatus::fromSheet('COMPLETE - SENT TO CLIENT'))->toBe(ClientStatus::COMPLETE_SENT_TO_CLIENT)
        ->and(ClientStatus::fromSheet('AWAITING FEEDBACK'))->toBe(ClientStatus::AWAITING_FEEDBACK)
        ->and(ClientStatus::fromSheet('On Progress'))->toBe(ClientStatus::ON_PROGRESS)
        ->and(ClientStatus::fromSheet('SENT PARALLEL'))->toBe(ClientStatus::SENT_PARALLEL)
        ->and(ClientStatus::fromSheet('ngawur'))->toBeNull()
        ->and(ClientStatus::fromSheet(''))->toBeNull();
});

it('blok angka sheet BD terbaca lengkap: Budget Plan, Plan COGS, Nett Margin rupiah & persen', function () {
    // Empat judul yang nyaris sama; "Projected Nett Margin" (rupiah) dan
    // "Projected Nett Margin %" harus mendarat di kolom yang berbeda.
    $judul = [
        'Campaign', 'Brand', 'PIC BD', 'STATUS',
        'Budget Plan from Clients', 'Plan COGS', 'Projected Nett Margin', 'Projected Nett Margin %',
        'AMOUNT DEALS',
    ];

    $m = new \App\Service\PipelineSheetMigration();

    expect($m->mapHeaders($judul))->toBe([
        0 => 'event_name',
        1 => 'company_name',
        2 => 'sales',
        3 => 'status',
        4 => 'budget_propose',
        5 => 'plan_cogs',
        6 => 'projected_nett_margin',
        7 => 'margin',
        8 => 'deal_value',
    ]);

    // Angka Planet Ban Agustus di sheet: 25jt budget, 20jt COGS, 5jt margin (20%).
    $items = $m->parseRows([
        $judul,
        ['Planet Ban IG Collab', 'Planet Ban', 'Sita', 'WON - ON GOING', '25.000.000', '20.000.000', '5.000.000', '20%', '25.000.000'],
    ]);
    $m->persist($items);

    $deal = \App\Models\BvSales::where('event_name', 'Planet Ban IG Collab')->firstOrFail();

    expect((float) $deal->budget_propose)->toBe(25_000_000.0)
        ->and((float) $deal->plan_cogs)->toBe(20_000_000.0)
        ->and((float) $deal->projected_nett_margin)->toBe(5_000_000.0)
        ->and((float) $deal->margin)->toBe(20.0)
        ->and((float) $deal->deal_value)->toBe(25_000_000.0)
        // Nett margin rupiah memang selisih budget dengan COGS
        ->and((float) $deal->projected_nett_margin)
        ->toBe((float) $deal->budget_propose - (float) $deal->plan_cogs);
});
