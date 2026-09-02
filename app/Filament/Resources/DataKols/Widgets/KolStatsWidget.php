<?php

namespace App\Filament\Resources\DataKols\Widgets;

use App\Models\DataKol;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Livewire\Attributes\Reactive;

class KolStatsWidget extends StatsOverviewWidget
{
    protected int|string|array $columnSpan = 'full';

    #[Reactive]
    public $dateFilter = 'all';

    /**
     * Channel yang ditampilkan, beserta warnanya.
     *
     * Tanpa ikon: judul kartunya sudah menyebut channel-nya, dan ikon apa pun
     * di situ menempel pada baris persentase — terbaca seolah menerangkan
     * angkanya, padahal tidak.
     *
     * @var array<string, string>
     */
    private const CHANNEL = [
        'Instagram' => 'danger',
        'Tiktok'    => 'gray',
        'Threads'   => 'info',
        'Youtube'   => 'danger',
        'X'         => 'gray',
    ];

    protected function getStats(): array
    {
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

        // 1 baris data_kols = 1 channel; satu KOL dikenali dari kol_key. Dua kolom
        // saja yang ditarik, dan pengelompokannya dikerjakan di PHP: satu KOL bisa
        // punya "Youtube Channels" DAN "Youtube Shorts", dan GROUP BY channel akan
        // menghitungnya dua kali di kartu YouTube.
        $baris = (clone $query)->get(['channel', 'kol_key']);

        $perChannel = [];
        $takDikenali = [];

        foreach ($baris as $row) {
            $bucket = self::channelUntuk($row->channel);

            if ($bucket === null) {
                $takDikenali[trim((string) $row->channel)] = true;
                continue;
            }

            $perChannel[$bucket][$row->kol_key] = true;
        }

        $totalKol = $baris->pluck('kol_key')->unique()->count();

        $stats = [
            Stat::make('Total KOL', number_format($totalKol) . ' KOL')
                ->description($takDikenali
                    ? count($takDikenali) . ' channel belum dikenali: ' . implode(', ', array_keys($takDikenali))
                    : 'Dihitung per orang, bukan per akun')
                ->descriptionIcon($takDikenali ? 'heroicon-m-exclamation-triangle' : 'heroicon-m-users')
                ->color($takDikenali ? 'warning' : 'success'),
        ];

        foreach (self::CHANNEL as $nama => $warna) {
            $jumlah = count($perChannel[$nama] ?? []);

            $stats[] = Stat::make("KOL Channel {$nama}", number_format($jumlah) . ' KOL')
                // Channel kosong dibuat abu-abu supaya tidak terbaca seperti angka penting.
                ->color($jumlah > 0 ? $warna : 'gray');
        }

        return $stats;
    }

    /**
     * Channel apa adanya di database dipetakan ke salah satu kartu.
     *
     * Datanya tidak seragam — "Thread" (tunggal) dari migrasi spreadsheet,
     * "Youtube Channels" & "Youtube Shorts" dari form. Mencocokkan string persis
     * akan membuat kartunya 0 padahal datanya ada.
     */
    private static function channelUntuk(?string $channel): ?string
    {
        $c = strtolower(trim((string) $channel));

        return match (true) {
            $c === '' => null,
            $c === 'ig' || str_contains($c, 'instagram') => 'Instagram',
            str_contains($c, 'tiktok') || str_contains($c, 'tik tok') => 'Tiktok',
            str_contains($c, 'thread') => 'Threads',
            str_contains($c, 'youtube') || str_contains($c, 'you tube') => 'Youtube',
            $c === 'x' || str_contains($c, 'twitter') => 'X',
            default => null,
        };
    }
}
