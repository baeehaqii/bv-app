<?php

namespace App\Providers;

use Filament\Support\Assets\Css;
use Filament\Support\Facades\FilamentAsset;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        FilamentAsset::register([
            Css::make('custom-dashboard', asset('css/filament/custom.css')),
        ]);

        /*
         * Batas waktu default untuk SEMUA panggilan HTTP keluar.
         *
         * Tanpa ini Guzzle menunggu tanpa batas: service scraping pernah menggantung
         * sampai max_execution_time PHP habis, dan itu FatalError yang TIDAK bisa
         * ditangkap try/catch — halaman langsung 500, bukan notifikasi "gagal fetch".
         *
         * 25 detik, bukan angka kecil: respons profil Instagram bisa ~640 KB (berisi
         * edge_owner_to_timeline_media yang dipakai menghitung engagement, jadi tidak
         * bisa diringkas dengan trim=true). Di koneksi ~32 KB/dtk itu butuh ~20 detik.
         * Tetap muat di bawah KolProfileImporter::BATAS_WAKTU_PER_BARIS (60 dtk) untuk
         * dua panggilan berurutan. Yang butuh lebih lama boleh menimpa dengan
         * Http::timeout() sendiri.
         */
        Http::globalOptions([
            'timeout' => 25,
            'connect_timeout' => 5,
        ]);
    }
}
