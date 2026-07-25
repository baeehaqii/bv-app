<?php

use App\Models\DataClient;

/**
 * Brand yang di-handle agency (agency_brands JSON) harus jadi baris direct brand asli
 * supaya ikut muncul di tab Database Brand dan terhitung di widget.
 */

it('membuat baris direct brand dari daftar brand yang di-handle agency', function () {
    $agency = DataClient::create([
        'nama_brand' => 'We Thrive',
        'type' => 'agency',
        'agency_brands' => [
            ['nama_brand' => 'Page by Page', 'category' => 'FMCG', 'nama_pic' => 'Rina'],
        ],
    ]);

    $brand = DataClient::where('nama_brand', 'Page by Page')->first();

    expect($brand)->not->toBeNull()
        ->and($brand->type)->toBe('direct')
        ->and($brand->agency_client_id)->toBe($agency->id)
        ->and($brand->category)->toBe('FMCG')
        ->and(DataClient::where('type', 'direct')->count())->toBe(1);
});

it('menautkan brand direct yang sudah ada, bukan menduplikasi', function () {
    $brand = DataClient::create(['nama_brand' => 'Ofero', 'type' => 'direct', 'category' => 'Automotive']);
    $agency = DataClient::create([
        'nama_brand' => 'UM',
        'type' => 'agency',
        'agency_brands' => [['nama_brand' => 'Ofero']],
    ]);

    expect(DataClient::where('nama_brand', 'Ofero')->count())->toBe(1)
        ->and($brand->fresh()->agency_client_id)->toBe($agency->id);
});

it('memutus tautan (tanpa menghapus) saat brand dilepas dari agency', function () {
    $agency = DataClient::create([
        'nama_brand' => 'Curve',
        'type' => 'agency',
        'agency_brands' => [['nama_brand' => 'Nyam Nyam']],
    ]);

    $agency->agency_brands = [];
    $agency->save();

    $brand = DataClient::where('nama_brand', 'Nyam Nyam')->first();

    expect($brand)->not->toBeNull()
        ->and($brand->agency_client_id)->toBeNull();
});
