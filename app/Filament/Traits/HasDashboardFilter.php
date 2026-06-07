<?php

namespace App\Filament\Traits;

use Carbon\Carbon;
use Livewire\Attributes\On;

/**
 * Filter periode bersama untuk widget Dashboard Executive.
 *
 * Menyediakan satu sumber kebenaran untuk range tanggal (today/yesterday/7d/30d/90d),
 * sinkronisasi nilai awal dari URL, dan listener event dari halaman dashboard.
 */
trait HasDashboardFilter
{
    public string $dateFilter = 'today';

    /** Sinkronkan nilai awal dari query string saat halaman dibuka dengan ?dateFilter=... */
    public function mountHasDashboardFilter(): void
    {
        $filter = request()->query('dateFilter');

        if (is_string($filter) && array_key_exists($filter, self::dashboardFilterOptions())) {
            $this->dateFilter = $filter;
        }
    }

    #[On('executiveDashboardFilterChanged')]
    public function applyDashboardFilter(string $dateFilter): void
    {
        $this->dateFilter = $dateFilter;
    }

    public static function dashboardFilterOptions(): array
    {
        return [
            'today' => 'Hari Ini',
            'yesterday' => 'Kemarin',
            '7d' => '7 Hari',
            '30d' => '30 Hari',
            '90d' => '90 Hari',
        ];
    }

    /** Range periode aktif: start, end, label, comparison. */
    protected function dashboardDateRange(): array
    {
        $now = Carbon::now();

        return match ($this->dateFilter) {
            'yesterday' => [
                'start' => Carbon::yesterday()->startOfDay(),
                'end' => Carbon::yesterday()->endOfDay(),
                'label' => Carbon::yesterday()->translatedFormat('d F Y'),
                'comparison' => 'dari 2 hari lalu',
            ],
            '7d' => [
                'start' => $now->copy()->subDays(6)->startOfDay(),
                'end' => $now->copy()->endOfDay(),
                'label' => '7 Hari Terakhir',
                'comparison' => 'dari 7 hari sebelumnya',
            ],
            '30d' => [
                'start' => $now->copy()->subDays(29)->startOfDay(),
                'end' => $now->copy()->endOfDay(),
                'label' => '30 Hari Terakhir',
                'comparison' => 'dari 30 hari sebelumnya',
            ],
            '90d' => [
                'start' => $now->copy()->subDays(89)->startOfDay(),
                'end' => $now->copy()->endOfDay(),
                'label' => '90 Hari Terakhir',
                'comparison' => 'dari 90 hari sebelumnya',
            ],
            default => [
                'start' => Carbon::today()->startOfDay(),
                'end' => $now->copy()->endOfDay(),
                'label' => 'Hari Ini',
                'comparison' => 'dari kemarin',
            ],
        };
    }

    /** Range periode sebelumnya untuk perhitungan persentase perubahan: start, end. */
    protected function dashboardPreviousRange(): array
    {
        $now = Carbon::now();

        return match ($this->dateFilter) {
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

    protected function dashboardPeriodLabel(): string
    {
        return $this->dashboardDateRange()['label'];
    }
}
