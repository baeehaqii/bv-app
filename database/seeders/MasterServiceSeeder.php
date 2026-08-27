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
                'nama_service' => 'Creative Insights & Strategy',
                'kode_service' => 'CIS',
                'deskripsi' => 'Riset dan strategi kreatif berbasis data untuk mendukung perencanaan campaign.',
                'is_active' => true,
                'is_coming_soon' => false,
                'urutan' => 1,
            ],
            [
                'nama_service' => 'Creator Connect / KOL & KOC',
                'kode_service' => 'KOL',
                'deskripsi' => 'Layanan kolaborasi dengan KOL dan KOC untuk promosi brand melalui konten sosial media.',
                'is_active' => true,
                'is_coming_soon' => false,
                'urutan' => 2,
            ],
            [
                'nama_service' => 'Content Production & Solution',
                'kode_service' => 'CPS',
                'deskripsi' => 'Produksi konten dan solusi kreatif untuk berbagai platform dan kebutuhan marketing.',
                'is_active' => true,
                'is_coming_soon' => false,
                'urutan' => 3,
            ],
            [
                'nama_service' => 'Tiktok Clipper & Affiliate',
                'kode_service' => 'TCA',
                'deskripsi' => 'Layanan pembuatan konten clipper TikTok dan program affiliate marketing.',
                'is_active' => true,
                'is_coming_soon' => false,
                'urutan' => 4,
            ],
            [
                'nama_service' => 'Community Building & Event Management',
                'kode_service' => 'CBE',
                'deskripsi' => 'Pengelolaan komunitas dan penyelenggaraan event untuk memperkuat engagement brand.',
                'is_active' => true,
                'is_coming_soon' => false,
                'urutan' => 5,
            ],
            [
                'nama_service' => 'Media & Talent Management',
                'kode_service' => 'MTM',
                'deskripsi' => 'Manajemen media dan talent untuk mendukung eksekusi campaign secara menyeluruh.',
                'is_active' => true,
                'is_coming_soon' => false,
                'urutan' => 6,
            ],
            [
                'nama_service' => 'Performance Marketing & OOH',
                'kode_service' => 'PMO',
                'deskripsi' => 'Performance marketing digital dan Out-of-Home advertising untuk hasil terukur.',
                'is_active' => true,
                'is_coming_soon' => false,
                'urutan' => 7,
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
