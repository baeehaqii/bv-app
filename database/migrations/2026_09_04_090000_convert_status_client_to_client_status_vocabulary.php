<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Menyeragamkan data lama kolom data_clients.status_client ke kosakata
 * App\Enums\ClientStatus (dropdown kolom STATUS di sheet PIPELINE BD).
 *
 * Sebelum ini ada tiga kosakata untuk satu kolom: 'aktif'/'tidak_aktif' dari
 * form, 'won'/'lost'/'revision'/... dari importer CSV, dan teks mentah sheet
 * dari migrasi spreadsheet. Semuanya tidak ada di dropdown baru, jadi barisnya
 * tampil kosong saat dibuka.
 *
 * Petanya ditulis tetap di sini, tidak memanggil enum-nya: migrasi adalah
 * potret keputusan pada saat ini, dan tidak boleh berubah perilaku kalau
 * kelak label enum-nya diedit.
 */
return new class extends Migration {
    /** nilai lama (huruf kecil) => nilai ClientStatus */
    private const PETA = [
        // Kosakata form lama
        'aktif' => 'on_progress',
        'tidak_aktif' => 'hold',
        // Kosakata importer CSV lama
        'won' => 'won_on_going',
        'lost' => 'lost',
        'revision' => 'revision',
        'mediaplan' => 'on_progress',
        'awaiting' => 'awaiting_feedback',
        'invoicing' => 'won_on_going',
        // Teks mentah dari sheet
        'on progress' => 'on_progress',
        'sent parallel' => 'sent_parallel',
        'hold' => 'hold',
        'complete sent to client' => 'complete_sent_to_client',
        'complete - sent to client' => 'complete_sent_to_client',
        'awaiting feedback' => 'awaiting_feedback',
        'won - on going' => 'won_on_going',
        'won on going' => 'won_on_going',
        'finish' => 'finish',
    ];

    public function up(): void
    {
        $terkonversi = 0;

        foreach (self::PETA as $lama => $baru) {
            if ($lama === $baru) {
                continue;
            }

            $terkonversi += DB::table('data_clients')
                // Cocokkan tanpa peduli huruf besar-kecil & spasi berlebih.
                ->whereRaw('LOWER(TRIM(status_client)) = ?', [$lama])
                ->update(['status_client' => $baru]);
        }

        // Nilai yang tidak ada di peta DIBIARKAN, bukan dikosongkan: lebih baik
        // ketahuan aneh di layar daripada hilang tanpa jejak.
        $sisa = DB::table('data_clients')
            ->whereNotNull('status_client')
            ->whereNotIn('status_client', array_values(array_unique(self::PETA)))
            ->count();

        // Diam kalau tidak ada apa-apa: tabel kosong adalah keadaan normal saat
        // migrasi jalan di DB baru (dan di setiap test suite).
        if ($terkonversi > 0 || $sisa > 0) {
            echo "status_client: {$terkonversi} baris dikonversi";
            echo $sisa > 0 ? ", {$sisa} baris bernilai tak dikenali dibiarkan apa adanya.\n" : ".\n";
        }
    }

    public function down(): void
    {
        // Tidak bisa dibalik: beberapa nilai lama menyatu ke satu nilai baru
        // ('mediaplan' dan 'aktif' dua-duanya jadi 'on_progress').
    }
};
