<?php

namespace App\Filament\Concerns;

use App\Models\DataKol;
use App\Service\KolProfileImporter;
use Filament\Notifications\Notification;
use Throwable;

/**
 * Scraping ulang channel KOL, dipakai bersama oleh halaman edit KOL Data dan
 * KOL Analyzer.
 *
 * Dua halaman itu menampilkan angka yang sama persis dari baris yang sama, jadi
 * tombol "Analyze"-nya harus melakukan hal yang sama pula — bukan dua salinan
 * kode yang lambat laun berbeda.
 */
trait MenyegarkanChannelKol
{
    /**
     * Tarik ulang profil SELURUH channel milik satu KOL.
     *
     * Satu halaman memang membuka satu baris channel, tapi yang dimaksud orang
     * dengan "perbarui data KOL" adalah orangnya — termasuk channel lain miliknya.
     */
    protected function segarkanSeluruhChannel(string $kolKey): void
    {
        $channels = DataKol::where('kol_key', $kolKey)->orderByDesc('followers')->get();

        foreach ($channels as $channel) {
            $this->segarkanBarisKol($channel);
        }
    }

    /** Scraping ulang satu baris channel, lengkap dengan notifikasi hasilnya. */
    protected function segarkanBarisKol(DataKol $row): ?DataKol
    {
        $sebelum = (int) $row->followers;
        $importer = app(KolProfileImporter::class);

        // Profil + video = dua panggilan API berurutan; beri ruang di atas jumlah
        // timeout keduanya supaya yang menghentikan adalah timeout HTTP (bisa
        // ditangkap & dinotifikasi), bukan max_execution_time (fatal, layar 500).
        if (function_exists('set_time_limit')) {
            @set_time_limit(KolProfileImporter::BATAS_WAKTU_PER_BARIS);
        }

        try {
            // fetch & save dipisah supaya media_count bisa dibaca — itu yang
            // membedakan "akun tidak punya postingan" dari "API tidak memberi data".
            $profil = $importer->fetchProfile($row->channel, (string) $row->link_userprofile);
            $baru = $importer->save($profil, $row->channel, (string) $row->link_userprofile, $row->kol_key, $row);
        } catch (Throwable $e) {
            Notification::make()
                ->title("Gagal memperbarui {$row->channel}")
                ->body($e->getMessage())
                ->danger()
                ->persistent()
                ->send();

            return null;
        }

        $selisih = (int) $baru->followers - $sebelum;

        $ringkas = number_format((int) $baru->followers) . ' followers'
            . ($selisih !== 0 ? ' (' . ($selisih > 0 ? '+' : '−') . number_format(abs($selisih)) . ')' : ' (tidak berubah)')
            . ' · ER ' . number_format((float) $baru->engagement_rate, 2) . '%'
            . ' · ' . number_format((int) $baru->impressions) . ' avg impressions';

        /*
         * Engagement 0 punya DUA sebab yang harus dibedakan, karena tindak lanjutnya
         * beda jauh:
         *  - akun memang belum punya postingan → datanya benar, tidak ada yang rusak
         *  - akun punya postingan tapi engagement tetap 0 → API tidak memberi data
         *    per-post, angka 0-nya bukan hasil pengukuran dan jangan dipercaya
         */
        $jumlahPost = (int) ($profil['media_count'] ?? 0);
        $nolEngagement = (int) $baru->followers > 0 && (int) $baru->engagements === 0;

        $catatan = match (true) {
            ! $nolEngagement => '',
            $jumlahPost === 0 => ' — Akun ini belum punya postingan, jadi engagement & ER memang 0.',
            default => ' — Engagement & ER tidak terhitung: data per-post tidak tersedia dari API,'
                . ' angka 0 di sini bukan hasil pengukuran.',
        };

        $notifikasi = Notification::make()
            ->title("{$row->channel} diperbarui")
            ->body($ringkas . $catatan);

        // persistent() tidak menerima argumen — harus dicabang, bukan persistent($bool).
        $nolEngagement
            ? $notifikasi->warning()->persistent()
            : $notifikasi->success();

        $notifikasi->send();

        return $baru;
    }

    /** Keterangan modal konfirmasi — jumlah channel yang akan ditarik. */
    protected function keteranganAnalyze(?string $kolKey): string
    {
        $jumlah = $kolKey ? DataKol::where('kol_key', $kolKey)->count() : 0;

        return "Mengambil ulang profil {$jumlah} channel milik KOL ini dari ScrapeCreators: "
            . 'followers, following, jumlah post, bio, foto profil, ER, dan rata-rata '
            . 'like/komentar/views. Tiap channel memakai panggilan API berbayar, '
            . 'jadi tidak dijalankan otomatis.';
    }
}
