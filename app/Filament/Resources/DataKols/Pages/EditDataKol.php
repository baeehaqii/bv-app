<?php

namespace App\Filament\Resources\DataKols\Pages;

use App\Filament\Resources\DataKols\DataKolResource;
use App\Models\DataKol;
use App\Service\KolProfileImporter;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Throwable;

class EditDataKol extends EditRecord
{
    protected static string $resource = DataKolResource::class;

    /** Kolom yang ikut berubah saat scraping ulang, untuk menyegarkan isi form. */
    private const FIELD_HASIL_SCRAPING = [
        'notes', 'terakhir_update', 'full_name', 'email', 'wa_number', 'contact', 'category',
    ];

    protected function getHeaderActions(): array
    {
        return [
            Action::make('save')
                ->label('Save Changes')
                ->action('save')
                ->keyBindings(['mod+s']),
        ];
    }

    protected function getFormActions(): array
    {
        return [];
    }

    /**
     * Scraping ulang satu baris channel, dipanggil dari tombol refresh di tabel
     * "Social Media Data" lewat wire:click. Tabel itu Placeholder blade biasa,
     * jadi tidak bisa memakai Action Filament per baris.
     */
    public function refreshChannel(int $id): void
    {
        // Hanya channel milik KOL yang sedang dibuka — id dari klien tidak dipercaya.
        $row = DataKol::query()
            ->where('kol_key', $this->getRecord()->kol_key)
            ->findOrFail($id);

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

            return;
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

        // Kalau yang di-scrape kebetulan baris yang sedang dibuka, isi form ikut basi.
        // Tabelnya sendiri query ulang tiap render, jadi tidak perlu redirect.
        if ($baru->is($this->getRecord())) {
            $this->getRecord()->refresh();
            $this->refreshFormData(self::FIELD_HASIL_SCRAPING);
        }
    }

    /**
     * Hapus satu baris channel. Rate card-nya ikut terhapus (cascade), tapi SPK
     * TIDAK — FK-nya nullOnDelete, jadi kontrak yang sudah terbit akan kehilangan
     * rujukan ke KOL-nya tanpa jejak. Karena itu channel ber-SPK ditolak.
     */
    public function deleteChannel(int $id): void
    {
        $record = $this->getRecord();

        $row = DataKol::query()
            ->withCount(['spks', 'rateCards'])
            ->where('username', $record->username)
            ->findOrFail($id);

        if ($row->spks_count > 0) {
            Notification::make()
                ->title("{$row->channel} tidak bisa dihapus")
                ->body("Channel ini dipakai {$row->spks_count} SPK. Menghapusnya membuat kontrak itu kehilangan rujukan KOL.")
                ->danger()
                ->persistent()
                ->send();

            return;
        }

        $sedangDibuka = $row->is($record);
        $channel = $row->channel;
        $jumlahRateCard = $row->rate_cards_count;

        $row->delete();

        Notification::make()
            ->title("Channel {$channel} dihapus")
            ->body($jumlahRateCard > 0 ? "{$jumlahRateCard} rate card ikut terhapus." : 'Tidak ada rate card yang ikut terhapus.')
            ->success()
            ->send();

        if (! $sedangDibuka) {
            return;
        }

        // Baris yang sedang dibuka lenyap → pindah ke channel lain milik KOL yang
        // sama; kalau habis, KOL-nya memang sudah tidak ada isinya lagi.
        $lain = DataKol::where('username', $record->username)->orderByDesc('followers')->first();

        $this->redirect(
            $lain
                ? DataKolResource::getUrl('edit', ['record' => $lain])
                : DataKolResource::getUrl('index'),
            navigate: true,
        );
    }
}
