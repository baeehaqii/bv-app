<?php

use App\Service\KolProfileImporter;

/**
 * Regresi CI: suite Pest mati dengan "Maximum execution time of 60 seconds
 * exceeded" di test acak yang tidak bersalah. Penyebabnya set_time_limit(60)
 * di jalur scraping — di CLI max_execution_time bernilai 0 (tanpa batas), jadi
 * panggilan itu MEMBUAT batas 60 detik yang lalu menempel ke seluruh proses.
 *
 * Batas itu juga menimpa php.ini runner maupun <ini> di phpunit.xml, yang
 * membuat dua percobaan perbaikan pertama sia-sia.
 */
it('tidak memasang batas waktu proses saat memang tidak ada batasnya', function () {
    $awal = ini_get('max_execution_time');

    KolProfileImporter::perpanjangJatahWaktu();

    expect(ini_get('max_execution_time'))->toBe($awal)
        ->and((int) ini_get('max_execution_time'))->toBe(0);
});

it('tetap memperpanjang jatah waktu kalau batasnya memang ada (jalur web)', function () {
    ini_set('max_execution_time', 5);

    try {
        KolProfileImporter::perpanjangJatahWaktu();

        expect((int) ini_get('max_execution_time'))->toBe(KolProfileImporter::BATAS_WAKTU_PER_BARIS);
    } finally {
        ini_set('max_execution_time', 0);
    }
});
