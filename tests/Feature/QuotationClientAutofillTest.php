<?php

use App\Models\DataClient;
use App\Models\User;
use App\Models\InternalBudget;
use App\Models\MediaPlan;
use App\Support\MotuScenarioData as Motu;
use Database\Seeders\SonyPicturesScenarioSeeder;

/**
 * Section "Detail Quotation": tipe client, brand, email PIC & alamat harus ikut
 * terisi dari Database Client — bukan cuma nama client.
 */
it('mengambil tipe, brand, email & alamat dari client direct', function () {
    $client = DataClient::create([
        'nama_brand' => 'Brand Langsung',
        'type' => 'direct',
        'alamat' => 'Jl. Sudirman No. 1, Jakarta',
        'pic_clients' => [
            ['name' => 'PIC Satu', 'email' => 'satu@brand.com'],
            ['name' => 'PIC Leads', 'email' => 'leads@brand.com', 'is_leads' => true],
        ],
    ]);

    expect($client->quotationFields())->toBe([
        'client_type' => 'direct',
        'client_brand' => 'Brand Langsung',
        'client_email' => 'leads@brand.com', // PIC leads diprioritaskan
        'client_address' => 'Jl. Sudirman No. 1, Jakarta',
    ]);
});

it('untuk agency, brand diambil dari brand campaign bukan nama agency', function () {
    $agency = DataClient::create([
        'nama_brand' => 'Agency Keren',
        'type' => 'agency',
        'alamat' => 'Jl. Gatot Subroto No. 2',
        'pic_clients' => [['name' => 'PIC Agency', 'email' => 'pic@agency.com']],
    ]);

    expect($agency->quotationFields('Brand Yang Dihandel'))->toBe([
        'client_type' => 'agency',
        'client_brand' => 'Brand Yang Dihandel',
        'client_email' => 'pic@agency.com',
        'client_address' => 'Jl. Gatot Subroto No. 2',
    ]);
});

it('generate quotation dari budget ikut mengisi tipe, brand, email & alamat', function () {
    // generateQuotation() menyimpan user pembuat (auth) — dipanggil dari panel.
    $this->actingAs(User::create([
        'name' => 'Pembuat',
        'email' => 'pembuat-' . uniqid() . '@bvnetwork.net',
        'password' => bcrypt('password'),
    ]));
    $this->seed(SonyPicturesScenarioSeeder::class);
    $mediaPlan = MediaPlan::where('campaign_name', Motu::CAMPAIGN_NAME)->firstOrFail();
    $budget = $mediaPlan->internalBudget;

    $client = $mediaPlan->bvSales->client;
    $client->update([
        'alamat' => 'Jl. Testing No. 9, Bandung',
        'pic_clients' => [['name' => 'PIC Client', 'email' => 'pic@client.com', 'is_leads' => true]],
    ]);

    $quotation = $budget->generateQuotation();

    expect($quotation->client_type)->toBe($client->type)
        ->and($quotation->client_email)->toBe('pic@client.com')
        ->and($quotation->client_address)->toBe('Jl. Testing No. 9, Bandung')
        ->and($quotation->client_brand)->not->toBeNull();
});

it('tidak menimpa data quotation yang sudah diisi manual dengan nilai kosong', function () {
    // generateQuotation() menyimpan user pembuat (auth) — dipanggil dari panel.
    $this->actingAs(User::create([
        'name' => 'Pembuat',
        'email' => 'pembuat-' . uniqid() . '@bvnetwork.net',
        'password' => bcrypt('password'),
    ]));
    $this->seed(SonyPicturesScenarioSeeder::class);
    $mediaPlan = MediaPlan::where('campaign_name', Motu::CAMPAIGN_NAME)->firstOrFail();
    $budget = $mediaPlan->internalBudget;

    // Client tanpa alamat & tanpa PIC.
    $mediaPlan->bvSales->client->update(['alamat' => null, 'pic_clients' => []]);

    $quotation = $budget->generateQuotation();
    $quotation->update(['client_address' => 'Alamat diisi manual', 'client_email' => 'manual@client.com']);

    $budget->refresh()->generateQuotation();

    expect($quotation->refresh()->client_address)->toBe('Alamat diisi manual')
        ->and($quotation->client_email)->toBe('manual@client.com');
});
