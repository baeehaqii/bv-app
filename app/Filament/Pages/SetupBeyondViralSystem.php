<?php

namespace App\Filament\Pages;

use App\Filament\Resources\DataClients\DataClientResource;
use App\Filament\Resources\DataKols\DataKolResource;
use App\Filament\Resources\DataVendors\DataVendorResource;
use App\Filament\Resources\Divisions\DivisionResource;
use App\Filament\Resources\GrossProfitTargets\GrossProfitTargetResource;
use App\Filament\Resources\MasterMargins\MasterMarginResource;
use App\Filament\Resources\MasterPphs\MasterPphResource;
use App\Filament\Resources\MasterServices\MasterServiceResource;
use App\Filament\Resources\MasterSows\MasterSowResource;
use App\Filament\Resources\SalesTargets\SalesTargetResource;
use App\Models\DataClient;
use App\Models\DataKol;
use App\Models\DataVendor;
use App\Models\Division;
use App\Models\GrossProfitTarget;
use App\Models\MasterMargin;
use App\Models\MasterPph;
use App\Models\MasterService;
use App\Models\MasterSow;
use App\Models\SalesTarget;
use BackedEnum;
use Filament\Pages\Page;
use UnitEnum;

class SetupBeyondViralSystem extends Page
{
    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-rocket-launch';

    protected static ?string $navigationLabel = 'Setup Beyond Viral System';

    protected static string|UnitEnum|null $navigationGroup = 'Settings';

    protected static ?int $navigationSort = -100;

    protected string $view = 'filament.pages.setup-beyond-viral-system';

    public function getTitle(): string
    {
        return 'Setup Beyond Viral System';
    }

    /**
     * Daftar langkah setup awal. `done` dihitung langsung dari eksistensi data,
     * jadi status selalu real-time tanpa perlu tabel state tambahan.
     *
     * ponytail: cek `->exists()` saja — cukup untuk gate "sudah diisi / belum".
     * Kalau nanti butuh validasi kelengkapan (mis. 12 bulan penuh), naikkan di sini.
     *
     * @return array<int, array<string, mixed>>
     */
    public function getSteps(): array
    {
        $year = now()->year;

        return [
            [
                'label' => 'Master Margins',
                'desc' => 'Margin bertingkat per budget (Low/Medium/High). Dasar hitung harga jual.',
                'icon' => 'heroicon-o-calculator',
                'required' => true,
                'done' => MasterMargin::query()->exists(),
                'url' => MasterMarginResource::getUrl(),
            ],
            [
                'label' => 'Master PPH',
                'desc' => 'Koefisien pajak per entitas (Pribadi / PT PKP / Non-PKP + PPN). Dipakai hitung net/gross KOL.',
                'icon' => 'heroicon-o-receipt-percent',
                'required' => true,
                'done' => MasterPph::query()->exists(),
                'url' => MasterPphResource::getUrl(),
            ],
            [
                'label' => 'Master SOW',
                'desc' => 'Channel + deliverable (IG Feed, TikTok Video, dst) untuk Rate Card KOL.',
                'icon' => 'heroicon-o-clipboard-document-list',
                'required' => true,
                'done' => MasterSow::query()->exists(),
                'url' => MasterSowResource::getUrl(),
            ],
            [
                'label' => 'Master Service',
                'desc' => 'Jenis layanan (Influencer, SMM, dll) untuk kategori campaign & quotation.',
                'icon' => 'heroicon-o-wrench-screwdriver',
                'required' => true,
                'done' => MasterService::query()->exists(),
                'url' => MasterServiceResource::getUrl(),
            ],
            [
                'label' => 'Struktur Organisasi',
                'desc' => 'Divisi, Departemen, Jabatan. Opsional — hanya untuk PIC & HR.',
                'icon' => 'heroicon-o-building-office-2',
                'required' => false,
                'done' => Division::query()->exists(),
                'url' => DivisionResource::getUrl(),
            ],
            [
                'label' => 'Database Client',
                'desc' => 'Brand/klien sebagai lawan transaksi sales.',
                'icon' => 'heroicon-o-user-group',
                'required' => true,
                'done' => DataClient::query()->exists(),
                'url' => DataClientResource::getUrl(),
            ],
            [
                'label' => 'Database KOL',
                'desc' => 'Daftar KOL/influencer. Wajib agar Media Plan bisa diisi.',
                'icon' => 'heroicon-o-star',
                'required' => true,
                'done' => DataKol::query()->exists(),
                'url' => DataKolResource::getUrl(),
            ],
            [
                'label' => 'Database Vendor',
                'desc' => 'Daftar vendor untuk Campaign External.',
                'icon' => 'heroicon-o-building-storefront',
                'required' => true,
                'done' => DataVendor::query()->exists(),
                'url' => DataVendorResource::getUrl(),
            ],
            [
                'label' => 'Target Finance ' . $year,
                'desc' => "Target gross profit tahunan & bulanan untuk tahun {$year}.",
                'icon' => 'heroicon-o-arrow-trending-up',
                'required' => true,
                'done' => GrossProfitTarget::query()->where('year', $year)->exists(),
                'url' => GrossProfitTargetResource::getUrl(),
            ],
            [
                'label' => 'Target Sales ' . $year,
                'desc' => "Target deal per sales untuk tahun {$year}.",
                'icon' => 'heroicon-o-flag',
                'required' => true,
                'done' => SalesTarget::query()->where('year', $year)->exists(),
                'url' => SalesTargetResource::getUrl(),
            ],
        ];
    }

    /** @return array<string, int|bool> */
    public function getSummary(): array
    {
        $steps = $this->getSteps();
        $required = array_filter($steps, fn (array $s): bool => $s['required']);
        $requiredDone = array_filter($required, fn (array $s): bool => $s['done']);

        return [
            'total' => count($steps),
            'done' => count(array_filter($steps, fn (array $s): bool => $s['done'])),
            'requiredTotal' => count($required),
            'requiredDone' => count($requiredDone),
            'ready' => count($requiredDone) === count($required),
        ];
    }
}
