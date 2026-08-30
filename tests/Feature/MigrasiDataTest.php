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
        ['Campaign A', 'Arummi', 'HOLD'],
        ['Campaign B', 'Arummi', 'LOST'],
    ]);

    expect($items[0]['status'])->toBeNull()
        ->and($items[1]['status'])->toBe(\App\Enums\SalesStatus::CLOSE_LOSE)
        ->and($m->statusTakDikenal())->toBe(['HOLD']);
});

it('membedakan kolom rupiah dari kolom persen di sebelahnya', function () {
    // BvSales.margin menyimpan PERSEN, jadi yang harus terbaca kolom "%",
    // bukan kolom rupiah yang judulnya nyaris sama.
    $judul = ['Campaign', 'Brand', 'Projected Nett Margin', 'Projected Nett Margin %'];

    $m = new \App\Service\PipelineSheetMigration();

    expect($m->mapHeaders($judul))->toBe([0 => 'event_name', 1 => 'company_name', 3 => 'margin'])
        ->and($m->unmappedHeaders($judul))->toBe(['Projected Nett Margin']);

    // Sheet menulis 0,6533; yang disimpan 65,33 persen.
    $items = $m->parseRows([$judul, ['Campaign A', 'Arummi', 9800000, 0.6533]]);
    expect((float) $items[0]['margin'])->toBe(65.33);
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
            13 => 1, 14 => 'IG Reels', 15 => 3400000,
            19 => 1, 20 => 'IG Reels', 21 => 1500000, 22 => 1500000, 23 => 0.98, 24 => 0.11]),
        $baris([13 => 1, 14 => 'IG Story with Link', 15 => 1200000,
            19 => 1, 20 => 'IG Story with Link', 21 => 500000, 22 => 500000, 23 => 0.98, 24 => 0.11]),
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
        ->and($dataKol->link_userprofile)->toBe('https://instagram.com/bagus')
        ->and((int) $dataKol->average_views)->toBe(8000);

    // Rate tiap SOW jadi rate card — dari sanalah budget item mengambil rate-nya.
    expect($dataKol->rateCards()->pluck('rate', 'sow')->map(fn($r) => (float) $r)->all())
        ->toBe(['IG Reels' => 1500000.0, 'IG Story with Link' => 500000.0]);

    // Baris Media Plan menunjuk ke baris KOL Data itu, bukan berdiri sendiri.
    expect(\App\Models\MediaPlanKol::where('name', 'Bagus Gandhi')->value('data_kol_id'))->toBe($dataKol->id);
});

it('menghitung sendiri kolom subtotal sampai margin, tidak mengambil angka sheet', function () {
    masterPphSeperti();
    $sales = \App\Models\BvSales::create(['event_name' => 'Bir Kawan Senja', 'company_name' => 'Multi Bintang']);

    $m = (new \App\Service\MediaPlanSheetMigration())->untukSales($sales->id);
    $m->persist($m->parseRows(kolListRows()));

    $kol = \App\Models\MediaPlanKol::where('name', 'Bagus Gandhi')->firstOrFail();
    $reels = $kol->internalBudgetItems()->where('scope_item', 'IG Reels')->firstOrFail();

    // Koefisien 0,98 + tax 0,11 di sheet = "PT PKP".
    expect($reels->vendor_tax_type)->toBe('PT PKP')
        ->and($reels->masterPph->name)->toBe('PT PKP');

    // rate_base LANGSUNG dari kolom Rate di sheet — saat migrasi KOL-nya memang
    // belum punya rate card, jadi tidak lewat computeRateFromSow().
    expect((float) $reels->rate_base)->toBe(1500000.0)
        ->and((float) $reels->subtotal)->toBe(1500000.0);

    // Cost harus sama persis dengan rumus sheet: Z = (W/0,98) + (W x 0,11).
    $costSheet = 1500000 / 0.98 + 1500000 * 0.11;
    expect(round((float) $reels->mu_pph, 2))->toBe(round($costSheet, 2))
        // AA = Z/0,5 lalu AC = ROUNDUP(AB, -5) — margin 50% dari Master Margin.
        ->and((float) $reels->rounded)->toBe((float) (ceil(($costSheet / 0.5) / 100000) * 100000))
        ->and((float) $reels->actual_margin_percent)->toBeGreaterThan(0);

    // SOW tanpa rate tetap jadi budget item, nilainya nol — bukan hilang.
    $visit = $kol->internalBudgetItems()->where('scope_item', 'Visit Store')->firstOrFail();
    expect((float) $visit->rate_base)->toBe(0.0);
});

it('menolak migrasi KOL List sebelum deal-nya dipilih', function () {
    $m = new \App\Service\MediaPlanSheetMigration();
    $hasil = $m->persist($m->parseRows(kolListRows()));

    expect($hasil['success'])->toBe(0)
        ->and($hasil['skipped'])->toBe(2)
        ->and($hasil['notes'][0])->toContain('belum dipilih')
        ->and(\App\Models\MediaPlanKol::count())->toBe(0);
});

it('membuatkan Media Plan bila deal-nya belum punya', function () {
    masterPphSeperti();
    $sales = \App\Models\BvSales::create(['event_name' => 'Bir Kawan Senja', 'company_name' => 'Multi Bintang']);

    expect($sales->mediaPlan()->exists())->toBeFalse();

    $m = (new \App\Service\MediaPlanSheetMigration())->untukSales($sales->id);
    $hasil = $m->persist($m->parseRows(kolListRows()));

    $plan = $sales->fresh()->mediaPlan;

    expect($hasil['success'])->toBe(2)
        // Dibuat lewat BvSales::ensureMediaPlanExists(), jadi isinya sama dengan
        // Media Plan yang lahir dari alur normal.
        ->and($plan)->not->toBeNull()
        ->and($plan->campaign_name)->toBe('Bir Kawan Senja')
        ->and(\App\Models\MediaPlanKol::where('media_plan_id', $plan->id)->count())->toBe(2);
});
