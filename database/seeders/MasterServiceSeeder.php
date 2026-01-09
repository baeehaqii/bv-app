<?php

namespace Database\Seeders;

use App\Models\MasterService;
use Illuminate\Database\Seeder;

class MasterServiceSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $services = [
            [
                'nama_service' => 'Influencer',
                'kode_service' => 'INF',
                'deskripsi' => 'Layanan kolaborasi dengan influencer untuk promosi brand melalui konten sosial media.',
                'is_active' => true,
                'is_coming_soon' => false,
                'urutan' => 1,
            ],
            [
                'nama_service' => 'Affiliate',
                'kode_service' => 'AFF',
                'deskripsi' => 'Program affiliate marketing untuk meningkatkan penjualan melalui sistem komisi.',
                'is_active' => true,
                'is_coming_soon' => true, // Coming soon
                'urutan' => 2,
            ],
            [
                'nama_service' => 'SMM',
                'kode_service' => 'SMM',
                'deskripsi' => 'Social Media Management - Pengelolaan akun sosial media secara profesional.',
                'is_active' => true,
                'is_coming_soon' => false,
                'urutan' => 3,
            ],
            [
                'nama_service' => 'Tiktok Clippers',
                'kode_service' => 'TKC',
                'deskripsi' => 'Layanan pembuatan dan editing konten video pendek untuk TikTok.',
                'is_active' => true,
                'is_coming_soon' => false,
                'urutan' => 4,
            ],
            [
                'nama_service' => 'Digital Video',
                'kode_service' => 'DVP',
                'deskripsi' => 'Produksi konten video digital untuk berbagai platform dan kebutuhan marketing.',
                'is_active' => true,
                'is_coming_soon' => false,
                'urutan' => 5,
            ],
        ];

        foreach ($services as $service) {
            MasterService::updateOrCreate(
                ['kode_service' => $service['kode_service']],
                $service
            );
        }
    }
}
