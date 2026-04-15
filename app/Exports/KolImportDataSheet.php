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

class KolImportDataSheet implements FromCollection, WithHeadings, WithStyles, WithTitle, ShouldAutoSize
{
    public function title(): string
    {
        return 'Data KOL';
    }

    public function headings(): array
    {
        return ['creator_name', 'channel', 'url', 'price'];
    }

    public function collection(): Collection
    {
        return collect([
            ['@namakreator1', 'instagram_reels', 'https://instagram.com/p/xxx', '5000000'],
            ['@namakreator2', 'tiktok_video', 'https://tiktok.com/@user/video/123', '3000000'],
            ['@namakreator3', 'youtube_short', 'https://youtube.com/shorts/abc', '2000000'],
        ]);
    }

    public function styles(Worksheet $sheet): array
    {
        return [
            1 => [
                'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
                'fill' => [
                    'fillType' => Fill::FILL_SOLID,
                    'startColor' => ['rgb' => '6D28D9'],
                ],
            ],
        ];
    }
}
