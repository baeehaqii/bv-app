<?php

namespace App\Exports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class KolImportChannelRefSheet implements FromCollection, WithHeadings, WithStyles, WithTitle, ShouldAutoSize
{
    public function title(): string
    {
        return 'Referensi Channel';
    }

    public function headings(): array
    {
        return ['nilai_channel', 'platform', 'tipe_konten', 'keterangan'];
    }

    public function collection(): Collection
    {
        return collect([
            // Instagram
            ['instagram_reels', 'Instagram', 'Reels', 'Video pendek vertikal di Instagram'],
            ['instagram_feed', 'Instagram', 'Feed', 'Foto atau video di feed Instagram'],
            ['instagram_story', 'Instagram', 'Story', 'Konten 24 jam di Instagram Stories'],
            // TikTok
            ['tiktok_video', 'TikTok', 'Video', 'Video di halaman FYP TikTok'],
            ['tiktok_story', 'TikTok', 'Story', 'Story 24 jam di TikTok'],
            ['tiktok_photos', 'TikTok', 'Photo Slide', 'Postingan foto/carousel di TikTok'],
            // YouTube
            ['youtube_short', 'YouTube', 'Shorts', 'Video vertikal pendek di YouTube Shorts'],
            ['youtube_video', 'YouTube', 'Video', 'Video panjang di YouTube'],
            // Threads
            ['threads_post', 'Threads', 'Post', 'Postingan teks/gambar di Threads'],
        ]);
    }

    public function styles(Worksheet $sheet): array
    {
        // 4 kolom: A–D
        $lastCol = 'D';

        $styles = [
            1 => [
                'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
                'fill' => [
                    'fillType' => Fill::FILL_SOLID,
                    'startColor' => ['rgb' => '6D28D9'],
                ],
            ],
        ];

        // Platform row ranges & warna
        $platformRanges = [
            ['start' => 2, 'end' => 4, 'color' => 'FCE7F3'], // Instagram
            ['start' => 5, 'end' => 7, 'color' => 'F0FDF4'], // TikTok
            ['start' => 8, 'end' => 9, 'color' => 'FEF3C7'], // YouTube
            ['start' => 10, 'end' => 10, 'color' => 'EFF6FF'], // Threads
        ];

        foreach ($platformRanges as $range) {
            $key = "A{$range['start']}:{$lastCol}{$range['end']}";
            $styles[$key] = [
                'fill' => [
                    'fillType' => Fill::FILL_SOLID,
                    'startColor' => ['rgb' => $range['color']],
                ],
            ];
        }

        return $styles;
    }
}
