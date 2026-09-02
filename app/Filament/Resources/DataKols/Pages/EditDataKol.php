<?php

namespace App\Filament\Resources\DataKols\Pages;

use App\Filament\Concerns\MenyegarkanChannelKol;
use App\Filament\Resources\DataKols\DataKolResource;
use App\Models\DataKol;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;

class EditDataKol extends EditRecord
{
    use MenyegarkanChannelKol;

    protected static string $resource = DataKolResource::class;

    /**
     * Field form yang ikut berubah saat scraping ulang.
     *
     * Isinya HARUS irisan antara kolom yang ditulis KolProfileImporter::toRow()
     * dan field yang benar-benar ada di DataKolForm. Daftar lama menyebut
     * full_name/email/wa_number/contact/category — tidak satu pun disentuh
     * scraping — dan melewatkan followers, tier, ER, engagements, impressions:
     * angkanya berubah di database tapi form tetap menampilkan yang lama sampai
     * halamannya dimuat ulang.
     */
    private const FIELD_HASIL_SCRAPING = [
        'username', 'followers', 'tier', 'engagement_rate',
        'engagements', 'impressions', 'status', 'notes',
    ];

    protected function getHeaderActions(): array
    {
        return [
            Action::make('analyze')
                ->label('Analyze')
                ->icon('heroicon-o-arrow-path')
                ->color('info')
                ->requiresConfirmation()
                ->modalHeading('Analyze — Tarik Ulang Data Channel')
                ->modalDescription(fn() => $this->keteranganAnalyze($this->getRecord()->kol_key))
                ->modalSubmitActionLabel('Ya, ambil sekarang')
                ->action(fn() => $this->analyzeKol()),

            Action::make('save')
                ->label('Save Changes')
                ->action('save')
                ->keyBindings(['mod+s']),
        ];
    }

    /**
     * Scraping ulang SELURUH channel milik KOL ini.
     *
     * Halaman edit memang membuka satu baris channel, tapi yang dimaksud orang
     * dengan "perbarui data KOL" adalah orangnya — termasuk channel lain yang
     * tampil di tabel Social Media Data di bawah.
     */
    public function analyzeKol(): void
    {
        $this->segarkanSeluruhChannel($this->getRecord()->kol_key);

        $this->getRecord()->refresh();
        $this->refreshFormData(self::FIELD_HASIL_SCRAPING);
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

        $baru = $this->segarkanBarisKol($row);

        // Kalau yang di-scrape kebetulan baris yang sedang dibuka, isi form ikut basi.
        // Tabelnya sendiri query ulang tiap render, jadi tidak perlu redirect.
        if ($baru?->is($this->getRecord())) {
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
