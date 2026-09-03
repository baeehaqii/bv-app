<?php

namespace App\Filament\Widgets\Sales;

use App\Models\BvSales;
use App\Models\BvSalesList;
use App\Models\SalesTarget;
use Filament\Widgets\ChartWidget;
use Livewire\Attributes\On;

/**
 * Diagram batang target vs realisasi per sales, selebar kontainer.
 *
 * Satuan sengaja dalam JUTA rupiah: sumbu Chart.js di Filament tidak bisa diberi
 * formatter JS (opsinya JSON), jadi angka miliaran mentah bikin sumbunya tak terbaca.
 */
class SalesTargetAchievementChart extends ChartWidget
{
    protected static ?int $sort = 1;

    protected int|string|array $columnSpan = 'full';

    protected ?string $maxHeight = '340px';

    protected static bool $isLazy = false;

    public ?int $year = null;

    #[On('salesTargetYearChanged')]
    public function setYear(int $year): void
    {
        $this->year = $year;
    }

    public function getHeading(): ?string
    {
        return 'Target vs Realisasi per Sales — ' . ($this->year ?? now()->year);
    }

    public function getDescription(): ?string
    {
        return 'Dalam juta rupiah. Realisasi dihitung dari deal berstatus won (Campaign Live s/d Paid).';
    }

    protected function getData(): array
    {
        $year = $this->year ?? now()->year;

        $targets = SalesTarget::forYear($year)
            ->selectRaw('bv_sales_list_id, SUM(target_amount) as total')
            ->groupBy('bv_sales_list_id')
            ->pluck('total', 'bv_sales_list_id');

        $actual = BvSales::wonTotalsForYear($year)['sales'];
        $names = BvSalesList::orderBy('nama_sales')->pluck('nama_sales', 'id');

        $labels = [];
        $targetData = [];
        $actualData = [];

        foreach ($names as $salesId => $name) {
            $target = (float) ($targets[$salesId] ?? 0);
            $realized = (float) ($actual[$salesId]['revenue'] ?? 0);

            // Sales tanpa target dan tanpa realisasi hanya jadi batang kosong.
            if ($target <= 0 && $realized <= 0) {
                continue;
            }

            $labels[] = $name;
            $targetData[] = round($target / 1_000_000, 2);
            $actualData[] = round($realized / 1_000_000, 2);
        }

        return [
            'datasets' => [
                [
                    'label' => 'Target (Rp juta)',
                    'data' => $targetData,
                    'backgroundColor' => 'rgba(148, 163, 184, 0.55)',
                    'borderColor' => 'rgb(100, 116, 139)',
                    'borderWidth' => 1,
                ],
                [
                    'label' => 'Realisasi (Rp juta)',
                    'data' => $actualData,
                    'backgroundColor' => 'rgba(72, 0, 159, 0.75)',
                    'borderColor' => 'rgb(72, 0, 159)',
                    'borderWidth' => 1,
                ],
            ],
            'labels' => $labels,
        ];
    }

    protected function getType(): string
    {
        return 'bar';
    }

    protected function getOptions(): array
    {
        return [
            'maintainAspectRatio' => false,
            'plugins' => [
                'legend' => ['position' => 'top'],
            ],
            'scales' => [
                'y' => ['beginAtZero' => true],
            ],
        ];
    }
}
