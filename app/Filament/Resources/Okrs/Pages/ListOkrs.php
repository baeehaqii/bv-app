<?php

namespace App\Filament\Resources\Okrs\Pages;

use App\Enums\OkrStatus;
use App\Filament\Resources\Okrs\OkrResource;
use App\Models\Okr;
use Filament\Actions\Action;
use Filament\Resources\Pages\Page;
use Illuminate\Support\Collection;
use Livewire\Attributes\Url;

/**
 * Daftar OKR dengan tata letak template OKR Confluence: satu baris per
 * Objective, kolom Key results / Owner / Partner with / skor / Current status.
 *
 * Bukan tabel Filament. Satu sel di sini memuat beberapa blok teks bertingkat
 * (Objective + owner + skor, status tiga bulan) — di Filament Table itu berarti
 * melawan strukturnya lewat HtmlString di tiap kolom, dan hasilnya lebih rumit
 * daripada satu blade tabel biasa.
 */
class ListOkrs extends Page
{
    protected static string $resource = OkrResource::class;

    protected string $view = 'filament.resources.okrs.list-okrs';

    // Tanpa ini judulnya jadi "List Okrs" — Filament menebak dari nama kelas,
    // dan tebakannya tidak tahu OKR itu akronim.
    protected static ?string $title = 'OKR';

    #[Url]
    public ?int $year = null;

    #[Url]
    public ?int $quarter = null;

    #[Url]
    public ?string $owner = null;

    public function mount(): void
    {
        $this->year ??= now()->year;
        $this->quarter ??= Okr::quarterFromMonth(now()->month);
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('tambah')
                ->label('Tambah OKR')
                ->icon('heroicon-m-plus')
                ->url(fn() => OkrResource::getUrl('create')),
        ];
    }

    /** @return Collection<int, Okr> */
    public function baris(): Collection
    {
        return Okr::query()
            ->forQuarter((int) $this->year, (int) $this->quarter)
            ->when($this->owner, fn($q) => $q->where('owner_name', $this->owner))
            // month null (target sekuartal) ditaruh paling atas: itu payung dari
            // objective bulanan di bawahnya, bukan sisa yang belum dijadwalkan.
            ->orderBy('owner_name')
            ->orderByRaw('month is not null, month')
            ->orderBy('id')
            ->get();
    }

    /** @return array{total: int, selesai: int, persen: int} */
    public function ringkasan(): array
    {
        $semua = $this->baris();
        $total = $semua->count();
        $selesai = $semua->where('status', OkrStatus::DONE)->count();

        return [
            'total' => $total,
            'selesai' => $selesai,
            'persen' => $total > 0 ? (int) round($selesai / $total * 100) : 0,
        ];
    }

    /** @return array<string, string> */
    public function pilihanPemilik(): array
    {
        return Okr::query()
            ->forQuarter((int) $this->year, (int) $this->quarter)
            ->distinct()
            ->orderBy('owner_name')
            ->pluck('owner_name', 'owner_name')
            ->all();
    }

    /** @return array<int, int> */
    public function pilihanTahun(): array
    {
        $tahun = Okr::query()->distinct()->orderByDesc('year')->pluck('year')->all();

        return $tahun ?: [now()->year];
    }
}
