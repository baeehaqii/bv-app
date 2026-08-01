<?php

namespace App\Filament\Resources\DataKols\Widgets;

use App\Models\DataKol;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Reactive;

class KolStatsWidget extends StatsOverviewWidget
{
    protected int|string|array $columnSpan = 'full';

    #[Reactive]
    public $dateFilter = 'all';

    protected function getStats(): array
    {
        // Get the date range based on filter
        $query = DataKol::query();

        if ($this->dateFilter !== 'all') {
            $days = match ($this->dateFilter) {
                'today' => 0,
                '7days' => 7,
                '14days' => 14,
                '30days' => 30,
                '60days' => 60,
                '90days' => 90,
                default => null,
            };

            if ($days !== null) {
                if ($days === 0) {
                    $query->whereDate('terakhir_update', today());
                } else {
                    $query->whereDate('terakhir_update', '>=', now()->subDays($days));
                }
            }
        }

        /*
         * 1 baris data_kols = 1 channel, jadi menghitung baris berarti menghitung
         * channel, bukan orang. Followers dijumlahkan per username dulu — angkanya
         * lalu dipakai untuk jumlah KOL sekaligus sebaran tier, supaya sama persis
         * dengan kolom Tier di tabel daftar.
         */
        $followersPerKol = (clone $query)
            ->selectRaw('username, SUM(followers) as total_followers')
            ->groupBy('username')
            ->pluck('total_followers', 'username');

        $totalKol = $followersPerKol->count();
        $tierCounts = $followersPerKol->countBy(fn($f) => DataKol::tierFor((int) $f));

        $megaCount = $tierCounts['Mega'] ?? 0;
        $macroCount = $tierCounts['Macro'] ?? 0;
        $microCount = $tierCounts['Micro'] ?? 0;
        $nanoCount = $tierCounts['Nano'] ?? 0;
        $miniCount = $tierCounts['Mini'] ?? 0;

        // Sebaran platform tetap dihitung per channel — memang jumlah akun, bukan orang.
        $tiktokCount = (clone $query)->where('channel', 'Tiktok')->count();
        $instagramCount = (clone $query)->where('channel', 'Instagram')->count();

        // Average Engagement Rate (convert string to float)
        $avgEngagementRate = (clone $query)->whereNotNull('engagement_rate')
            ->where('engagement_rate', '!=', '')
            ->get()
            ->map(function ($kol) {
                return (float) str_replace(['%', ','], ['', '.'], $kol->engagement_rate);
            })
            ->avg();

        // Total Followers (convert string to number)
        $totalFollowers = (clone $query)->whereNotNull('followers')
            ->where('followers', '!=', '')
            ->get()
            ->map(function ($kol) {
                return (float) str_replace([',', '.'], '', $kol->followers);
            })
            ->sum();

        // Total Engagements
        $totalEngagements = (clone $query)->whereNotNull('engagements')
            ->where('engagements', '!=', '')
            ->get()
            ->map(function ($kol) {
                return (float) str_replace([',', '.'], '', $kol->engagements);
            })
            ->sum();

        return [
            Stat::make('Total KOL Database', number_format($totalKol) . ' KOL')
                ->description('Total influencers in database')
                ->descriptionIcon('heroicon-m-users')
                ->color('success')
                ->chart([7, 12, 15, 18, 22, 25, $totalKol]),

            Stat::make('TikTok vs Instagram', $tiktokCount . ' : ' . $instagramCount)
                ->description('Channel platform distribution')
                ->descriptionIcon('heroicon-m-chart-bar')
                ->color('info'),

            Stat::make('Total Followers', $this->formatNumber($totalFollowers))
                ->description('Accumulated total followers')
                ->descriptionIcon('heroicon-m-user-group')
                ->color('warning'),

            Stat::make('Avg Engagement Rate', number_format($avgEngagementRate, 2) . '%')
                ->description('Average engagement rate')
                ->descriptionIcon('heroicon-m-chart-pie')
                ->color('success'),

            Stat::make('Total Engagements', $this->formatNumber($totalEngagements))
                ->description('Total interactions')
                ->descriptionIcon('heroicon-m-heart')
                ->color('danger'),

            Stat::make('KOL Tier Distribution', "Mega: {$megaCount} | Macro: {$macroCount}")
                ->description("Micro: {$microCount} | Nano: {$nanoCount} | Mini: {$miniCount}")
                ->descriptionIcon('heroicon-m-star')
                ->color('primary'),
        ];
    }

    /**
     * Format large numbers into readable format
     */
    private function formatNumber($number): string
    {
        if ($number >= 1000000000) {
            return number_format($number / 1000000000, 2) . 'B';
        } elseif ($number >= 1000000) {
            return number_format($number / 1000000, 2) . 'M';
        } elseif ($number >= 1000) {
            return number_format($number / 1000, 2) . 'K';
        }

        return number_format($number);
    }
}
