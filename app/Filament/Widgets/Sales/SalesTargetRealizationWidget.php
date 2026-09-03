<?php

namespace App\Filament\Widgets\Sales;

use App\Models\BvSales;
use App\Models\SalesTarget;
use Carbon\Carbon;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Livewire\Attributes\On;

/**
 * Empat stat realisasi vs target sales untuk satu tahun:
 * target, realisasi, % pencapaian, dan sisa target.
 */
class SalesTargetRealizationWidget extends BaseWidget
{
    protected static ?int $sort = 0;

    protected int|string|array $columnSpan = 'full';

    protected int|array|null $columns = 4;

    protected static bool $isLazy = false;

    public ?int $year = null;

    #[On('salesTargetYearChanged')]
    public function setYear(int $year): void
    {
        $this->year = $year;
    }

    protected function getStats(): array
    {
        $year = $this->year ?? now()->year;
        $now = Carbon::now();

        $fmt = fn(float $value): string => 'Rp ' . number_format($value, 0, ',', '.');

        $target = (float) SalesTarget::forYear($year)->sum('target_amount');
        $monthTarget = (float) SalesTarget::forMonth($year, $now->month)->sum('target_amount');

        $actual = BvSales::wonTotalsForYear($year);
        $actualRevenue = array_sum(array_column($actual['months'], 'revenue'));
        $monthlyRevenue = array_values(array_map(
            fn(array $month) => round($month['revenue'] / 1_000_000),
            $actual['months']
        ));

        $percent = $target > 0 ? round($actualRevenue / $target * 100, 2) : 0.0;
        $gap = max($target - $actualRevenue, 0);

        // Sisa bulan hanya relevan untuk tahun berjalan.
        $monthsLeft = $year === $now->year ? max(12 - $now->month + 1, 1) : 0;
        $gapDesc = match (true) {
            $target <= 0 => 'Target tahun ini belum diset',
            $gap <= 0 => 'Target tahunan sudah terlampaui 🎉',
            $monthsLeft > 0 => 'Butuh ' . $fmt($gap / $monthsLeft) . "/bulan ({$monthsLeft} bulan tersisa)",
            default => 'Tahun sudah lewat — kekurangan realisasi',
        };

        return [
            Stat::make("🎯 Target Sales {$year}", $fmt($target))
                ->description($monthTarget > 0
                    ? 'Bulan ini: ' . $fmt($monthTarget)
                    : 'Target bulan ini belum diset')
                ->descriptionIcon('heroicon-m-flag')
                ->color('info'),

            Stat::make('✅ Realisasi Deal Won', $fmt($actualRevenue))
                ->description('Deal Campaign Live s/d Paid, per close date')
                ->descriptionIcon('heroicon-m-banknotes')
                ->color('primary')
                ->chart($monthlyRevenue),

            Stat::make('📊 % Pencapaian', number_format($percent, 2, ',', '.') . '%')
                ->description($target > 0
                    ? 'Realisasi dibagi target ' . $year
                    : 'Belum bisa dihitung — target kosong')
                ->descriptionIcon($percent >= 100 ? 'heroicon-m-arrow-trending-up' : 'heroicon-m-arrow-trending-down')
                ->color(match (true) {
                    $percent >= 100 => 'success',
                    $percent >= 70 => 'warning',
                    default => 'danger',
                }),

            Stat::make('⏳ Sisa Target', $fmt($gap))
                ->description($gapDesc)
                ->descriptionIcon('heroicon-m-clock')
                ->color($gap > 0 ? 'warning' : 'success'),
        ];
    }
}
