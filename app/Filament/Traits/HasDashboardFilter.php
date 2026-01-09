<?php

namespace App\Filament\Traits;

use Carbon\Carbon;
use Filament\Facades\Filament;
use Illuminate\Support\Facades\Session;

trait HasDashboardFilter
{
    /**
     * Get the filter key used for session storage
     */
    protected function getFilterSessionKey(): string
    {
        return 'dashboard_filter_period';
    }

    /**
     * Get the current filter period from session
     */
    protected function getFilterPeriod(): string
    {
        return Session::get($this->getFilterSessionKey(), 'monthly');
    }

    /**
     * Get available filter options
     */
    public static function getFilterOptions(): array
    {
        return [
            'daily' => 'Harian',
            'weekly' => 'Mingguan',
            'monthly' => 'Bulanan',
            'quarterly' => 'Per Kuartal',
        ];
    }

    /**
     * Get date range based on filter period
     * Returns [startDate, endDate, previousStartDate, previousEndDate, periodLabel]
     */
    protected function getDateRangeFromFilter(): array
    {
        $period = $this->getFilterPeriod();
        $now = Carbon::now();

        return match ($period) {
            'daily' => [
                'start' => $now->copy()->startOfDay(),
                'end' => $now->copy()->endOfDay(),
                'previousStart' => $now->copy()->subDay()->startOfDay(),
                'previousEnd' => $now->copy()->subDay()->endOfDay(),
                'label' => $now->translatedFormat('d F Y'),
                'previousLabel' => $now->copy()->subDay()->translatedFormat('d F Y'),
            ],
            'weekly' => [
                'start' => $now->copy()->startOfWeek(),
                'end' => $now->copy()->endOfWeek(),
                'previousStart' => $now->copy()->subWeek()->startOfWeek(),
                'previousEnd' => $now->copy()->subWeek()->endOfWeek(),
                'label' => 'Minggu ke-' . $now->weekOfYear . ' ' . $now->year,
                'previousLabel' => 'Minggu ke-' . $now->copy()->subWeek()->weekOfYear . ' ' . $now->copy()->subWeek()->year,
            ],
            'monthly' => [
                'start' => $now->copy()->startOfMonth(),
                'end' => $now->copy()->endOfMonth(),
                'previousStart' => $now->copy()->subMonth()->startOfMonth(),
                'previousEnd' => $now->copy()->subMonth()->endOfMonth(),
                'label' => $now->translatedFormat('F Y'),
                'previousLabel' => $now->copy()->subMonth()->translatedFormat('F Y'),
            ],
            'quarterly' => [
                'start' => $now->copy()->startOfQuarter(),
                'end' => $now->copy()->endOfQuarter(),
                'previousStart' => $now->copy()->subQuarter()->startOfQuarter(),
                'previousEnd' => $now->copy()->subQuarter()->endOfQuarter(),
                'label' => 'Q' . $now->quarter . ' ' . $now->year,
                'previousLabel' => 'Q' . $now->copy()->subQuarter()->quarter . ' ' . $now->copy()->subQuarter()->year,
            ],
            default => [
                'start' => $now->copy()->startOfMonth(),
                'end' => $now->copy()->endOfMonth(),
                'previousStart' => $now->copy()->subMonth()->startOfMonth(),
                'previousEnd' => $now->copy()->subMonth()->endOfMonth(),
                'label' => $now->translatedFormat('F Y'),
                'previousLabel' => $now->copy()->subMonth()->translatedFormat('F Y'),
            ],
        };
    }

    /**
     * Get comparison text based on period
     */
    protected function getComparisonText(): string
    {
        $period = $this->getFilterPeriod();

        return match ($period) {
            'daily' => 'dari kemarin',
            'weekly' => 'dari minggu lalu',
            'monthly' => 'dari bulan lalu',
            'quarterly' => 'dari kuartal lalu',
            default => 'dari periode sebelumnya',
        };
    }
}
