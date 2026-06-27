<?php

namespace Database\Seeders;

use App\Models\DataClient;
use Illuminate\Database\Seeder;

class PipelineClientSeeder extends Seeder
{
    /**
     * Client/Brand dari "Sales Pipeline & Dashboard - Beyond Viral.xlsx" (sheet Pipeline).
     * Dipakai agar BvSales.company_name punya pasangan DataClient.nama_brand yang cocok.
     * updateOrCreate by nama_brand → aman dijalankan ulang & tidak menabrak DataClientSeeder (leads).
     */
    public function run(): void
    {
        $clients = [
            ['nama_brand' => 'Arummi', 'type' => 'direct', 'has_agency' => false, 'agency_name' => [], 'account_owner' => 'Gerry'],
            ['nama_brand' => 'Bintang', 'type' => 'direct', 'has_agency' => false, 'agency_name' => [], 'account_owner' => 'Wina'],
            ['nama_brand' => 'Bintang Anggur Merah', 'type' => 'direct', 'has_agency' => false, 'agency_name' => [], 'account_owner' => 'Wina'],
            ['nama_brand' => 'Blue Band', 'type' => 'agency', 'has_agency' => true, 'agency_name' => ['UM IPG'], 'account_owner' => 'Gerry'],
            ['nama_brand' => 'Cedea', 'type' => 'agency', 'has_agency' => true, 'agency_name' => ['WebTVAsia'], 'account_owner' => 'Gerry'],
            ['nama_brand' => 'Cimory', 'type' => 'agency', 'has_agency' => true, 'agency_name' => ['Growmint'], 'account_owner' => 'Gerry'],
            ['nama_brand' => 'Fisher Price - Mattel', 'type' => 'agency', 'has_agency' => true, 'agency_name' => ['MBCS'], 'account_owner' => 'Gerry'],
            ['nama_brand' => 'Gimbory', 'type' => 'direct', 'has_agency' => false, 'agency_name' => [], 'account_owner' => 'Gerry'],
            ['nama_brand' => 'GoCar', 'type' => 'agency', 'has_agency' => true, 'agency_name' => ['Curve'], 'account_owner' => 'Wina'],
            ['nama_brand' => 'GoFood', 'type' => 'agency', 'has_agency' => true, 'agency_name' => ['Curve'], 'account_owner' => 'Wina'],
            ['nama_brand' => 'Golden Rama Tours', 'type' => 'agency', 'has_agency' => true, 'agency_name' => ['UM IPG'], 'account_owner' => 'Gerry'],
            ['nama_brand' => 'GoPay', 'type' => 'agency', 'has_agency' => true, 'agency_name' => ['Curve'], 'account_owner' => 'Wina'],
            ['nama_brand' => 'Heineken', 'type' => 'direct', 'has_agency' => false, 'agency_name' => [], 'account_owner' => 'Wina'],
            ['nama_brand' => 'Injourney', 'type' => 'agency', 'has_agency' => true, 'agency_name' => ['ITDC - Injourney'], 'account_owner' => 'Gerry'],
            ['nama_brand' => 'Le Minerale', 'type' => 'direct', 'has_agency' => false, 'agency_name' => [], 'account_owner' => 'Gerry'],
            ['nama_brand' => 'Manfa Indonesia', 'type' => 'direct', 'has_agency' => false, 'agency_name' => [], 'account_owner' => 'Wina'],
            ['nama_brand' => 'Mattel', 'type' => 'agency', 'has_agency' => true, 'agency_name' => ['MBCS'], 'account_owner' => 'Gerry'],
            ['nama_brand' => 'Michelin', 'type' => 'direct', 'has_agency' => false, 'agency_name' => [], 'account_owner' => 'Gerry'],
            ['nama_brand' => 'Michelin, BFGoodrich, Corsa', 'type' => 'direct', 'has_agency' => false, 'agency_name' => [], 'account_owner' => 'Gerry'],
            ['nama_brand' => 'Mobil1', 'type' => 'agency', 'has_agency' => true, 'agency_name' => ['TBWA'], 'account_owner' => 'Gerry'],
            ['nama_brand' => 'Nyam Nyam', 'type' => 'direct', 'has_agency' => false, 'agency_name' => [], 'account_owner' => 'Gerry'],
            ['nama_brand' => 'Ofero', 'type' => 'direct', 'has_agency' => false, 'agency_name' => [], 'account_owner' => 'Gerry'],
            ['nama_brand' => 'Page By Page', 'type' => 'agency', 'has_agency' => true, 'agency_name' => ['We Thrive'], 'account_owner' => 'Wina'],
            ['nama_brand' => 'Pizza Hut Indonesia', 'type' => 'direct', 'has_agency' => false, 'agency_name' => [], 'account_owner' => 'Wina'],
            ['nama_brand' => 'Planet Ban', 'type' => 'direct', 'has_agency' => false, 'agency_name' => [], 'account_owner' => 'Wina'],
            ['nama_brand' => 'Shopee', 'type' => 'direct', 'has_agency' => false, 'agency_name' => [], 'account_owner' => 'Gerry'],
            ['nama_brand' => 'Sony Pictures', 'type' => 'direct', 'has_agency' => false, 'agency_name' => [], 'account_owner' => 'Wina'],
            ['nama_brand' => 'Toys Kingdom', 'type' => 'direct', 'has_agency' => false, 'agency_name' => [], 'account_owner' => 'Wina'],
            ['nama_brand' => 'UIP Studio', 'type' => 'direct', 'has_agency' => false, 'agency_name' => [], 'account_owner' => 'Wina'],
            ['nama_brand' => 'Vitalis', 'type' => 'agency', 'has_agency' => true, 'agency_name' => ['WebTV Asia'], 'account_owner' => 'Gerry'],
            ['nama_brand' => 'XM', 'type' => 'agency', 'has_agency' => true, 'agency_name' => ['Agam'], 'account_owner' => 'Gerry'],
        ];

        foreach ($clients as $client) {
            DataClient::updateOrCreate(
                ['nama_brand' => $client['nama_brand']],
                $client
            );
        }
    }
}
