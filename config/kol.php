<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Estimasi Rate Card
    |--------------------------------------------------------------------------
    |
    | ScrapeCreators tidak menyediakan harga, jadi estimasi di KOL Analyzer
    | dihitung dari followers × tarif per follower, lalu dikoreksi oleh
    | engagement rate terhadap benchmark channel-nya.
    |
    |   median = followers × rate_per_follower × (ER / benchmark_er)
    |   min    = median × spread.min
    |   max    = median × spread.max
    |
    | Angka-angka ini SENGAJA ditaruh di config: ini asumsi pasar yang berubah
    | tiap tahun dan per negara — tim boleh menyetelnya tanpa mengubah kode.
    | Sumber awal: rata-rata rate card KOL Indonesia 2026.
    |
    */

    'rate_estimate' => [

        // Rupiah per follower untuk satu postingan.
        'rate_per_follower' => [
            'Instagram' => 100,
            'Tiktok' => 75,
            'Youtube Channels' => 250,
            'Youtube Shorts' => 120,
            'Threads' => 40,
        ],

        // ER wajar per channel (%). Dipakai sebagai pembagi koreksi.
        'benchmark_er' => [
            'Instagram' => 1.5,
            'Tiktok' => 5.0,
            'Youtube Channels' => 2.0,
            'Youtube Shorts' => 3.0,
            'Threads' => 1.0,
        ],

        // Batas koreksi ER — supaya KOL ber-ER ekstrem tidak melahirkan angka absurd.
        'er_multiplier_min' => 0.5,
        'er_multiplier_max' => 2.0,

        // Rentang min–max di sekitar median.
        'spread' => [
            'min' => 0.7,
            'max' => 1.4,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Benchmark Penilaian Campaign
    |--------------------------------------------------------------------------
    |
    | Ambang "Excellent / Good / Bad" pada Metrics Overview di Campaign Summary.
    | ER & VTR: makin BESAR makin baik. CPE/CPV/CPM: makin KECIL makin baik
    | (angka di sini batas ATAS, dalam rupiah).
    |
    | Sama seperti estimasi rate card, ini asumsi pasar — taruh di config supaya
    | tim bisa menyetel tanpa mengubah kode.
    |
    */
    'campaign_benchmark' => [
        'er' => ['excellent' => 5.0, 'good' => 2.0],
        'vtr' => ['excellent' => 100.0, 'good' => 40.0],
        'cpe' => ['excellent' => 500, 'good' => 2_000],
        'cpv' => ['excellent' => 50, 'good' => 200],
        'cpm' => ['excellent' => 30_000, 'good' => 80_000],
    ],

];
