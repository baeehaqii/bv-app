<?php

namespace App\Filament\Widgets;

use App\Models\BvCashflow;
use Carbon\Carbon;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Livewire\Attributes\On;

class RevenueStatsWidget extends BaseWidget
{
    protected static ?int $sort = 1;
    protected int|string|array $columnSpan = 'full';

    public string $dateFilter = 'today';

    #[On('executiveDashboardFilterChanged')]
    public function handleFilterChanged(string $dateFilter): void
    {
        $this->dateFilter = $dateFilter;
    }

    protected function getStats(): array
    {
        $range = $this->getDateRange($this->dateFilter);
        $prevRange = $this->getPreviousRange($this->dateFilter);

        // ── Revenue (income) ─────────────────────────────────────────────
        $revenue = (float) BvCashflow::where('type', 'income')->whereBetween('transaction_date', [$range['start'], $range['end']])->sum('amount');
        $prevRevenue = (float) BvCashflow::where('type', 'income')->whereBetween('transaction_date', [$prevRange['start'], $prevRange['end']])->sum('amount');

        // ── Expense ──────────────────────────────────────────────────────
        $expense = (float) BvCashflow::where('type', 'expense')->whereBetween('transaction_date', [$range['start'], $range['end']])->sum('amount');
        $prevExpense = (float) BvCashflow::where('type', 'expense')->whereBetween('transaction_date', [$prevRange['start'], $prevRange['end']])->sum('amount');

        // ── Gross Profit & Margin ────────────────────────────────────────
        $grossProfit = $revenue - $expense;
        $prevGrossProfit = $prevRevenue - $prevExpense;
        $profitMargin = $revenue > 0 ? ($grossProfit / $revenue) * 100 : 0;
        $prevProfitMargin = $prevRevenue > 0 ? ($prevGrossProfit / $prevRevenue) * 100 : 0;

        // ── % change ────────────────────────────────────────────────────
        $pct = fn(float $cur, float $prev): float => $prev != 0
            ? round((($cur - $prev) / abs($prev)) * 100, 1)
            : 0;

        $revChange = $pct($revenue, $prevRevenue);
        $gpChange = $pct($grossProfit, $prevGrossProfit);
        $marginChange = $pct($profitMargin, $prevProfitMargin);

        $desc = fn(float $change, string $suffix): string => ($change >= 0 ? '+' : '') . number_format($change, 1) . "% {$suffix}";
        $fmt = fn(float $v): string => 'Rp ' . number_format($v, 0, ',', '.');

        // ── Sparkline (up to 7 daily points within range) ────────────────
        $days = (int) $range['start']->diffInDays($range['end']) + 1;
        $pointCount = min($days, 7);
        $revenueChart = [];
        $gpChart = [];

        for ($i = $pointCount - 1; $i >= 0; $i--) {
            $dayStart = $range['end']->copy()->subDays($i)->startOfDay();
            $dayEnd = $range['end']->copy()->subDays($i)->endOfDay();
            $r = (float) BvCashflow::where('type', 'income')->whereBetween('transaction_date', [$dayStart, $dayEnd])->sum('amount');
            $e = (float) BvCashflow::where('type', 'expense')->whereBetween('transaction_date', [$dayStart, $dayEnd])->sum('amount');
            $revenueChart[] = $r / 1_000_000;
            $gpChart[] = ($r - $e) / 1_000_000;
        }

        return [
            Stat::make("Revenue {$range['label']}", $fmt($revenue))
                ->description($desc($revChange, $range['comparisonText']))
                ->descriptionIcon($revChange >= 0 ? 'heroicon-m-arrow-trending-up' : 'heroicon-m-arrow-trending-down')
                ->color($revChange >= 0 ? 'success' : 'danger')
                ->chart($revenueChart),

            Stat::make("Gross Profit {$range['label']}", $fmt($grossProfit))
                ->description($desc($gpChange, $range['comparisonText']))
                ->descriptionIcon($gpChange >= 0 ? 'heroicon-m-arrow-trending-up' : 'heroicon-m-arrow-trending-down')
                ->color($gpChange >= 0 ? 'success' : 'danger')
                ->chart($gpChart),

            Stat::make("Profit Margin {$range['label']}", number_format($profitMargin, 2) . '%')
                ->description($desc($marginChange, $range['comparisonText']))
                ->descriptionIcon($marginChange >= 0 ? 'heroicon-m-arrow-trending-up' : 'heroicon-m-arrow-trending-down')
                ->color($marginChange >= 0 ? 'success' : 'danger'),
        ];
    }

    private function getDateRange(string $filter): array
    {
        $now = Carbon::now();

        return match ($filter) {
            'yesterday' => [
                'start' => Carbon::yesterday()->startOfDay(),
                'end' => Carbon::yesterday()->endOfDay(),
                'label' => Carbon::yesterday()->translatedFormat('d F Y'),
                'comparisonText' => 'dari 2 hari lalu',
            ],
            '7d' => [
                'start' => $now->copy()->subDays(6)->startOfDay(),
                'end' => $now->copy()->endOfDay(),
                'label' => '7 Hari Terakhir',
                'comparisonText' => 'dari 7 hari sebelumnya',
            ],
            '30d' => [
                'start' => $now->copy()->subDays(29)->startOfDay(),
                'end' => $now->copy()->endOfDay(),
                'label' => '30 Hari Terakhir',
                'comparisonText' => 'dari 30 hari sebelumnya',
            ],
            '90d' => [
                'start' => $now->copy()->subDays(89)->startOfDay(),
                'end' => $now->copy()->endOfDay(),
                'label' => '90 Hari Terakhir',
                'comparisonText' => 'dari 90 hari sebelumnya',
            ],
            default => [
                'start' => Carbon::today()->startOfDay(),
                'end' => $now->copy()->endOfDay(),
                'label' => 'Hari Ini',
                'comparisonText' => 'dari kemarin',
            ],
        };
    }

    private function getPreviousRange(string $filter): array
    {
        $now = Carbon::now();

        return match ($filter) {
            'yesterday' => [
                'start' => $now->copy()->subDays(2)->startOfDay(),
                'end' => $now->copy()->subDays(2)->endOfDay(),
            ],
            '7d' => [
                'start' => $now->copy()->subDays(13)->startOfDay(),
                'end' => $now->copy()->subDays(7)->endOfDay(),
            ],
            '30d' => [
                'start' => $now->copy()->subDays(59)->startOfDay(),
                'end' => $now->copy()->subDays(30)->endOfDay(),
            ],
            '90d' => [
                'start' => $now->copy()->subDays(179)->startOfDay(),
                'end' => $now->copy()->subDays(90)->endOfDay(),
            ],
            default => [
                'start' => Carbon::yesterday()->startOfDay(),
                'end' => Carbon::yesterday()->endOfDay(),
            ],
        };
    }
}
