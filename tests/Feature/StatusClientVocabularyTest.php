<?php

use App\Enums\ClientStatus;
use App\Models\DataClient;

/**
 * Konversi kosakata lama data_clients.status_client ke ClientStatus.
 *
 * Migrasinya dijalankan ulang langsung di test — tabelnya sudah kosong saat
 * RefreshDatabase memigrasi, jadi jalur konversinya tidak pernah terlewati
 * kalau tidak dipanggil sendiri di sini.
 */
function jalankanKonversiStatusClient(): void
{
    ob_start();
    (include database_path('migrations/2026_09_04_090000_convert_status_client_to_client_status_vocabulary.php'))->up();
    ob_end_clean();
}

it('mengubah kosakata lama status_client jadi nilai ClientStatus', function () {
    $lama = [
        'aktif' => ClientStatus::ON_PROGRESS->value,
        'tidak_aktif' => ClientStatus::HOLD->value,
        'won' => ClientStatus::WON_ON_GOING->value,
        'awaiting' => ClientStatus::AWAITING_FEEDBACK->value,
        'invoicing' => ClientStatus::WON_ON_GOING->value,
        'WON - ON GOING' => ClientStatus::WON_ON_GOING->value,
        'COMPLETE - SENT TO CLIENT' => ClientStatus::COMPLETE_SENT_TO_CLIENT->value,
        'finish' => ClientStatus::FINISH->value,
    ];

    foreach (array_keys($lama) as $i => $nilai) {
        DataClient::create([
            'nama_brand' => 'Brand ' . $i,
            'type' => 'direct',
            'status_client' => $nilai,
        ]);
    }

    jalankanKonversiStatusClient();

    foreach (array_values($lama) as $i => $harusnya) {
        expect(DataClient::where('nama_brand', 'Brand ' . $i)->value('status_client'))->toBe($harusnya);
    }
});

it('membiarkan nilai yang tidak dikenali dan yang sudah benar', function () {
    DataClient::create(['nama_brand' => 'Sudah Benar', 'type' => 'direct', 'status_client' => ClientStatus::LOST->value]);
    DataClient::create(['nama_brand' => 'Aneh', 'type' => 'direct', 'status_client' => 'ENTAH APA']);
    DataClient::create(['nama_brand' => 'Kosong', 'type' => 'direct', 'status_client' => null]);

    jalankanKonversiStatusClient();
    jalankanKonversiStatusClient(); // idempoten

    expect(DataClient::where('nama_brand', 'Sudah Benar')->value('status_client'))->toBe(ClientStatus::LOST->value)
        // Dibiarkan, bukan dikosongkan — biar ketahuan dan bisa dibetulkan manual.
        ->and(DataClient::where('nama_brand', 'Aneh')->value('status_client'))->toBe('ENTAH APA')
        ->and(DataClient::where('nama_brand', 'Kosong')->value('status_client'))->toBeNull();
});
