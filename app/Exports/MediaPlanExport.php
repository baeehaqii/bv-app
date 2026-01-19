<?php

namespace App\Exports;

use App\Models\MediaPlan;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class MediaPlanExport implements FromCollection, WithHeadings, WithStyles, WithTitle, WithEvents, ShouldAutoSize
{
    protected MediaPlan $mediaPlan;
    protected array $rows = [];

    public function __construct(MediaPlan $mediaPlan)
    {
        $this->mediaPlan = $mediaPlan;
        $this->prepareData();
    }

    /**
     * Prepare data for export
     */
    protected function prepareData(): void
    {
        $this->mediaPlan->load([
            'kols' => function ($query) {
                $query->where('is_selected', true)
                    ->orderBy('row_number')
                    ->with(['dataKol', 'internalBudgetItems']);
            },
            'internalBudget.items'
        ]);

        $rowNumber = 1;
        foreach ($this->mediaPlan->kols as $kol) {
            $budgetItems = $kol->internalBudgetItems ?? collect([]);
            $links = is_array($kol->links) ? $kol->links : [];
            $category = $kol->categories ?? $kol->dataKol?->category ?? '-';
            $domisili = $kol->domisili ?? '-';

            if ($budgetItems->count() > 0) {
                // Multiple rows for budget items
                foreach ($budgetItems as $itemIndex => $budgetItem) {
                    $row = [];

                    if ($itemIndex === 0) {
                        // First row with KOL data
                        $row = [
                            'no' => $rowNumber,
                            'domisili' => $domisili,
                            'username' => $kol->name ?? '-',
                            'link' => $links[$itemIndex] ?? ($links[0] ?? '-'),
                            'channel' => $kol->channel ?? '-',
                            'categories' => $category,
                            'followers' => $kol->followers ?? 0,
                            'tier' => $kol->tier ?? '-',
                            'er_percent' => $kol->er_percent ?? 0,
                            'avg_views' => $kol->impression ?? 0,
                            'engagement' => $kol->engagement ?? 0,
                            'cpi_cpv' => $kol->cpi_cpv ?? 0,
                            'cpe' => $kol->cpe ?? 0,
                            'scope_of_work' => ($budgetItem->qty ?? 1) . 'x ' . ($budgetItem->scope_item ?? '-'),
                            'rate' => $budgetItem->rounded ?? 0,
                            'notes' => $kol->notes ?? '',
                        ];
                    } else {
                        // Additional rows for extra scope items
                        $row = [
                            'no' => '',
                            'domisili' => '',
                            'username' => '',
                            'link' => $links[$itemIndex] ?? '-',
                            'channel' => '',
                            'categories' => '',
                            'followers' => '',
                            'tier' => '',
                            'er_percent' => '',
                            'avg_views' => '',
                            'engagement' => '',
                            'cpi_cpv' => '',
                            'cpe' => '',
                            'scope_of_work' => ($budgetItem->qty ?? 1) . 'x ' . ($budgetItem->scope_item ?? '-'),
                            'rate' => $budgetItem->rounded ?? 0,
                            'notes' => '',
                        ];
                    }

                    $this->rows[] = $row;
                }
            } else {
                // Single row when no budget items
                $fallbackScopeItems = is_array($kol->scope_items) ? $kol->scope_items : [];

                $this->rows[] = [
                    'no' => $rowNumber,
                    'domisili' => $domisili,
                    'username' => $kol->name ?? '-',
                    'link' => $links[0] ?? '-',
                    'channel' => $kol->channel ?? '-',
                    'categories' => $category,
                    'followers' => $kol->followers ?? 0,
                    'tier' => $kol->tier ?? '-',
                    'er_percent' => $kol->er_percent ?? 0,
                    'avg_views' => $kol->impression ?? 0,
                    'engagement' => $kol->engagement ?? 0,
                    'cpi_cpv' => $kol->cpi_cpv ?? 0,
                    'cpe' => $kol->cpe ?? 0,
                    'scope_of_work' => count($fallbackScopeItems) > 0 ? implode(', ', $fallbackScopeItems) : '-',
                    'rate' => $kol->rate ?? 0,
                    'notes' => $kol->notes ?? '',
                ];
            }

            $rowNumber++;
        }
    }

    /**
     * @return Collection
     */
    public function collection(): Collection
    {
        return collect($this->rows);
    }

    /**
     * @return array
     */
    public function headings(): array
    {
        return [
            'No',
            'Domisili',
            'Username',
            'Link',
            'Channel',
            'Categories',
            'Followers',
            'Tier',
            'ER %',
            'Avg Views',
            'Engagement',
            'CPI/CPV',
            'CPE',
            'Scope of Work',
            'Rate',
            'Notes',
        ];
    }

    /**
     * @return string
     */
    public function title(): string
    {
        return 'Media Plan';
    }

    /**
     * @param Worksheet $sheet
     * @return array
     */
    public function styles(Worksheet $sheet): array
    {
        return [
            // Header row style
            1 => [
                'font' => [
                    'bold' => true,
                    'color' => ['rgb' => '581C87'], // Purple text
                ],
                'fill' => [
                    'fillType' => Fill::FILL_SOLID,
                    'startColor' => ['rgb' => 'D8B4FE'], // Light purple background
                ],
                'alignment' => [
                    'horizontal' => Alignment::HORIZONTAL_CENTER,
                    'vertical' => Alignment::VERTICAL_CENTER,
                ],
            ],
        ];
    }

    /**
     * @return array
     */
    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();
                $lastRow = count($this->rows) + 1;
                $lastColumn = 'P';

                // Set header row height
                $sheet->getRowDimension(1)->setRowHeight(25);

                // Apply borders to all cells
                $sheet->getStyle("A1:{$lastColumn}{$lastRow}")->applyFromArray([
                    'borders' => [
                        'allBorders' => [
                            'borderStyle' => Border::BORDER_THIN,
                            'color' => ['rgb' => 'D1D5DB'],
                        ],
                    ],
                ]);

                // Apply number format to currency columns (Rate, CPI/CPV, CPE)
                $sheet->getStyle("L2:M{$lastRow}")->getNumberFormat()
                    ->setFormatCode('#,##0');
                $sheet->getStyle("O2:O{$lastRow}")->getNumberFormat()
                    ->setFormatCode('"Rp "#,##0');

                // Apply number format to followers, views, engagement
                $sheet->getStyle("G2:G{$lastRow}")->getNumberFormat()
                    ->setFormatCode('#,##0');
                $sheet->getStyle("J2:K{$lastRow}")->getNumberFormat()
                    ->setFormatCode('#,##0');

                // Apply percentage format to ER column
                $sheet->getStyle("I2:I{$lastRow}")->getNumberFormat()
                    ->setFormatCode('0.00"%"');

                // Center align specific columns
                $centerColumns = ['A', 'E', 'H', 'I'];
                foreach ($centerColumns as $col) {
                    $sheet->getStyle("{$col}2:{$col}{$lastRow}")
                        ->getAlignment()
                        ->setHorizontal(Alignment::HORIZONTAL_CENTER);
                }

                // Left align text columns
                $leftColumns = ['B', 'C', 'D', 'F', 'N', 'P'];
                foreach ($leftColumns as $col) {
                    $sheet->getStyle("{$col}2:{$col}{$lastRow}")
                        ->getAlignment()
                        ->setHorizontal(Alignment::HORIZONTAL_LEFT);
                }

                // Right align number columns
                $rightColumns = ['G', 'J', 'K', 'L', 'M', 'O'];
                foreach ($rightColumns as $col) {
                    $sheet->getStyle("{$col}2:{$col}{$lastRow}")
                        ->getAlignment()
                        ->setHorizontal(Alignment::HORIZONTAL_RIGHT);
                }

                // Add summary section after data
                $summaryStartRow = $lastRow + 2;

                // Calculate totals
                $totalFollowers = $this->mediaPlan->kols->sum('followers');
                $totalImpressions = $this->mediaPlan->kols->sum('impression');
                $totalEngagement = $this->mediaPlan->kols->sum('engagement');
                $kolCount = $this->mediaPlan->kols->count();

                $totalRate = 0;
                $totalScopeItems = 0;
                foreach ($this->mediaPlan->kols as $kol) {
                    $kolBudgetItems = $kol->internalBudgetItems ?? collect([]);
                    $totalRate += $kolBudgetItems->sum('rounded');
                    $totalScopeItems += $kolBudgetItems->count();
                }

                if ($totalRate === 0) {
                    $totalRate = $this->mediaPlan->kols->sum('rate');
                }

                // Write summary
                $sheet->setCellValue("A{$summaryStartRow}", 'SUMMARY');
                $sheet->mergeCells("A{$summaryStartRow}:P{$summaryStartRow}");
                $sheet->getStyle("A{$summaryStartRow}")->applyFromArray([
                    'font' => ['bold' => true, 'color' => ['rgb' => 'DAFF01']],
                    'fill' => [
                        'fillType' => Fill::FILL_SOLID,
                        'startColor' => ['rgb' => '49009F'],
                    ],
                    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
                ]);

                $summaryData = [
                    ['Total KOLs', $kolCount . ' KOL(s)', 'Total Est. Views', number_format($totalImpressions)],
                    ['Total Followers', number_format($totalFollowers), 'Total Est. Engagement', number_format($totalEngagement)],
                    ['Total Scope Items', $totalScopeItems . ' Item(s)', 'Total Rate', 'Rp ' . number_format($totalRate)],
                ];

                foreach ($summaryData as $i => $row) {
                    $rowNum = $summaryStartRow + $i + 1;
                    $sheet->setCellValue("A{$rowNum}", $row[0]);
                    $sheet->setCellValue("C{$rowNum}", $row[1]);
                    $sheet->setCellValue("E{$rowNum}", $row[2]);
                    $sheet->setCellValue("G{$rowNum}", $row[3]);

                    $sheet->mergeCells("A{$rowNum}:B{$rowNum}");
                    $sheet->mergeCells("C{$rowNum}:D{$rowNum}");
                    $sheet->mergeCells("E{$rowNum}:F{$rowNum}");
                    $sheet->mergeCells("G{$rowNum}:P{$rowNum}");

                    $sheet->getStyle("A{$rowNum}:B{$rowNum}")->applyFromArray([
                        'font' => ['bold' => true],
                        'fill' => [
                            'fillType' => Fill::FILL_SOLID,
                            'startColor' => ['rgb' => 'F3F4F6'],
                        ],
                    ]);
                    $sheet->getStyle("E{$rowNum}:F{$rowNum}")->applyFromArray([
                        'font' => ['bold' => true],
                        'fill' => [
                            'fillType' => Fill::FILL_SOLID,
                            'startColor' => ['rgb' => 'F3F4F6'],
                        ],
                    ]);
                }

                // Add header info at the top (shift data down)
                // For now, we'll add a note in the summary section
                $infoRow = $summaryStartRow + 5;
                $sheet->setCellValue("A{$infoRow}", 'Campaign: ' . ($this->mediaPlan->campaign_name ?? '-'));
                $sheet->setCellValue("E{$infoRow}", 'Brand: ' . ($this->mediaPlan->brand ?? '-'));
                $sheet->setCellValue("I{$infoRow}", 'Quotation: ' . ($this->mediaPlan->quotation_number ?? '-'));
                $sheet->setCellValue("M{$infoRow}", 'Generated: ' . now()->format('d M Y H:i'));
            },
        ];
    }
}
