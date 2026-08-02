<?php

namespace App\Filament\Resources\BvCampigns\Pages;

use App\Filament\Pages\CampaignSummaryList;
use App\Filament\Resources\BvCampigns\BvCampignResource;
use Filament\Resources\Pages\Concerns\InteractsWithRecord;
use Filament\Resources\Pages\Page;

/**
 * Rute lama KOL Performance — sekarang dialihkan ke menu Campaign Summary.
 *
 * Isinya pindah ke CampaignSummaryList supaya sidebar tetap menyorot "Campaign
 * Summary" saat ringkasan dibuka; halaman milik resource akan menyorot "Campaign
 * Ongoing Internal" dan terasa melempar user keluar dari menunya.
 *
 * Kelasnya dipertahankan (bukan dihapus) agar bookmark dan tautan lama ke
 * /campaign-ongoing-internal/{id}/kol-performance tetap mendarat di tempat benar.
 */
class KolPerformance extends Page
{
    use InteractsWithRecord;

    protected static string $resource = BvCampignResource::class;

    protected string $view = 'filament.pages.kol-performance';

    public function mount(int|string $record): void
    {
        $this->record = $this->resolveRecord($record);

        // $this->redirect(), bukan return redirect(): mount() Livewire tidak
        // mengembalikan response — nilai baliknya diabaikan dan halaman tetap dirender.
        $this->redirect(CampaignSummaryList::getUrl(['campaign' => $this->record->getKey()]));
    }
}
