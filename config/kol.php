<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Benchmark Penilaian Campaign
    |--------------------------------------------------------------------------
    |
    | Ambang "Excellent / Good / Bad" pada Metrics Overview di Campaign Summary.
    | ER & VTR: makin BESAR makin baik. CPE/CPV/CPM: makin KECIL makin baik
    | (angka di sini batas ATAS, dalam rupiah).
    |
    | Ini asumsi pasar — taruh di config supaya tim bisa menyetel tanpa
    | mengubah kode.
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
