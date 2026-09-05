<?php

namespace App\Filament\Concerns;

use App\Models\BvCampaignKol;
use App\Service\PostPerformanceService;
use Filament\Notifications\Notification;
use Illuminate\Support\Collection;
use Throwable;

/**
 * "Fetch All Performance" yang diproses BERTAHAP.
 *
 * Satu postingan butuh ~4,7 detik (panggilan API terukur 2,8-5,1 dtk + jeda
 * 0,5). Untuk 31 postingan itu ~2,5 menit dalam satu request — melewati
 * max_execution_time PHP maupun timeout nginx, dan request mati di tengah
 * jalan: sebagian KOL ter-update, sisanya tidak, tanpa keterangan mana yang
 * mana.
 *
 * Potongan kecil membuat tiap request selesai cepat, progresnya kelihatan, dan
 * satu postingan yang gagal tidak menjatuhkan sisanya. Perulangannya dijalankan
 * dari sisi klien (lihat partial `fetch-performa-bertahap`), pola yang sama
 * dengan halaman Migrasi Data.
 *
 * Pemakai trait ini WAJIB menyediakan antreanFetch().
 */
trait MenarikPerformaBertahap
{
    public const FETCH_CHUNK = 3;

    /** Nama event; komponen mendengarkannya dari partial progres. */
    public const FETCH_EVENT = 'fetch-performa-run';

    public bool $fetching = false;

    public bool $fetchFinished = false;

    public int $fetchTotal = 0;

    public int $fetchProcessed = 0;

    public int $fetchSuccess = 0;

    public int $fetchFailed = 0;

    /** @var array<int, string> */
    public array $fetchErrors = [];

    /** @var array<int, int> id postingan yang belum diproses */
    public array $fetchQueue = [];

    /**
     * Postingan yang akan ditarik ulang. Tiap komponen menentukan cakupannya
     * sendiri.
     *
     * @return Collection<int, BvCampaignKol>
     */
    abstract protected function antreanFetch(): Collection;

    public function startFetchAll(): void
    {
        $antrean = $this->antreanFetch();

        if ($antrean->isEmpty()) {
            Notification::make()->warning()->title('Belum ada postingan tayang')->send();

            return;
        }

        $this->fetchQueue = $antrean->pluck('id')->all();
        $this->fetchTotal = count($this->fetchQueue);
        $this->fetchProcessed = $this->fetchSuccess = $this->fetchFailed = 0;
        $this->fetchErrors = [];
        $this->fetchFinished = false;
        $this->fetching = true;

        $this->dispatch(self::FETCH_EVENT);
    }

    /**
     * Proses satu potongan antrean.
     *
     * @return bool true bila antrean sudah habis — penanda berhenti untuk
     *              perulangan di sisi klien.
     */
    public function fetchChunk(): bool
    {
        if (! $this->fetching) {
            return true;
        }

        $potongan = array_splice($this->fetchQueue, 0, self::FETCH_CHUNK);

        if ($potongan === []) {
            return $this->selesaikanFetch();
        }

        $service = new PostPerformanceService;
        $terakhir = array_key_last($potongan);

        foreach ($potongan as $i => $id) {
            $kol = BvCampaignKol::find($id);

            if (! $kol) {
                // Terhapus setelah antrean disusun — tetap dihitung selesai,
                // kalau tidak progresnya berhenti di angka yang tak tercapai.
                $this->fetchProcessed++;
                continue;
            }

            try {
                $service->fetchAndUpdateKol($kol);
                $this->fetchSuccess++;
            } catch (Throwable $e) {
                $this->fetchFailed++;
                $this->fetchErrors[] = "{$kol->creator_name}: {$e->getMessage()}";
            }

            $this->fetchProcessed++;

            // Jeda antar panggilan dalam satu potongan; setelah yang terakhir
            // tidak perlu — jeda antar request sudah jadi selanya sendiri.
            if ($i !== $terakhir) {
                usleep(500_000);
            }
        }

        $this->fetchErrors = array_slice($this->fetchErrors, -20);

        return $this->fetchQueue === [] ? $this->selesaikanFetch() : false;
    }

    public function getFetchPersenProperty(): int
    {
        return $this->fetchTotal > 0 ? (int) round($this->fetchProcessed / $this->fetchTotal * 100) : 0;
    }

    private function selesaikanFetch(): bool
    {
        $this->fetching = false;
        $this->fetchFinished = true;

        Notification::make()
            ->success()
            ->title('Performa diperbarui')
            ->body("{$this->fetchSuccess} dari {$this->fetchTotal} postingan berhasil"
                .($this->fetchFailed ? ", {$this->fetchFailed} gagal." : '.'))
            ->send();

        return true;
    }
}
