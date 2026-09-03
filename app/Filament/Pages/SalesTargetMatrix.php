<?php

namespace App\Filament\Pages;

use App\Filament\Widgets\Sales\SalesTargetAchievementChart;
use App\Filament\Widgets\Sales\SalesTargetRealizationWidget;
use App\Models\BvSales;
use App\Models\BvSalesList;
use App\Models\GrossProfitTarget;
use App\Models\SalesTarget;
use Carbon\Carbon;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Livewire\Attributes\Url;

/**
 * Sales Target Matrix — tampilan setahun penuh seperti sheet "2026 Sales Target":
 * bulan sebagai KOLOM (plus kolom Q1–Q4 dan total tahun), baris berisi Booked
 * Revenue, target tiap sales, target GP, realisasi, dan persentasenya.
 *
 * Hanya baris target per sales yang bisa diedit — sisanya turunan:
 *   Booked Revenue & Booked GP Target  → dari Target Finance (gross_profit_targets)
 *   Actual Booked Revenue / GP         → dari deal BvSales berstatus won
 */
class SalesTargetMatrix extends Page
{
    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-table-cells';

    protected static string|\UnitEnum|null $navigationGroup = 'Sales';

    protected static ?string $navigationLabel = 'Sales Target Matrix';

    protected static ?string $title = 'Sales Target Matrix';

    protected static ?int $navigationSort = 2;

    protected static ?string $slug = 'sales-target-matrix';

    protected string $view = 'filament.pages.sales-target-matrix';

    private const EXECUTIVE_ROLES = ['super_admin', 'superadmin', 'Super Admin', 'CEO', 'COO', 'Finance'];

    private const SALES_ROLES = ['Sales/BD', 'sales', 'bd', 'Sales', 'BD', 'Business Development'];

    #[Url]
    public ?int $year = null;

    /** Nilai input target per sales: cells[bv_sales_list_id][bulan] = "1.100.000.000" */
    public array $cells = [];

    public static function shouldRegisterNavigation(): bool
    {
        return auth()->check() && auth()->user()->hasRole([...self::SALES_ROLES, ...self::EXECUTIVE_ROLES]);
    }

    public function mount(): void
    {
        $this->year ??= now()->year;
        $this->loadCells();
    }

    public function updatedYear(): void
    {
        $this->loadCells();
        $this->dispatch('salesTargetYearChanged', year: (int) $this->year);
    }

    // -------------------------------------------------------
    // Data
    // -------------------------------------------------------

    /** Kolom matriks, urut seperti sheet: total tahun, bulan, lalu quarter di ujung tiap kuartal. */
    public function columns(): array
    {
        $columns = [[
            'key' => 'year',
            'label' => (string) $this->year,
            'kind' => 'year',
        ]];

        foreach (range(1, 12) as $month) {
            $columns[] = [
                'key' => "m{$month}",
                'label' => Carbon::createFromDate($this->year, $month, 1)->translatedFormat('M Y'),
                'kind' => 'month',
                'month' => $month,
            ];

            if ($month % 3 === 0) {
                $quarter = intdiv($month, 3);
                $columns[] = [
                    'key' => "q{$quarter}",
                    'label' => "Q{$quarter} {$this->year}",
                    'kind' => 'quarter',
                    'months' => SalesTarget::quarterMonths($quarter),
                ];
            }
        }

        return $columns;
    }

    public function salesRows(): array
    {
        return BvSalesList::orderBy('nama_sales')->pluck('nama_sales', 'id')->all();
    }

    public function yearOptions(): array
    {
        $current = now()->year;
        $years = range($current - 2, $current + 2);

        return array_combine($years, array_map('strval', $years));
    }

    /**
     * Seluruh baris matriks siap render.
     *
     * @return array<int, array{label: string, kind: string, values: array<string, float>, sales_id?: int}>
     */
    public function rows(): array
    {
        $year = (int) $this->year;

        $finance = GrossProfitTarget::forYear($year)->get()->keyBy('month');
        $targets = SalesTarget::forYear($year)->get()->groupBy('bv_sales_list_id');
        $actual = BvSales::wonTotalsForYear($year);

        $perMonth = function (callable $value): array {
            $months = [];
            foreach (range(1, 12) as $month) {
                $months[$month] = (float) $value($month);
            }

            return $months;
        };

        $bookedRevenue = $perMonth(fn(int $m) => $finance->get($m)?->target_deal_revenue ?? 0);
        $bookedGp = $perMonth(fn(int $m) => $finance->get($m)?->target_amount ?? 0);
        $benchmark = $perMonth(fn(int $m) => $finance->get($m)?->margin_benchmark_percent ?? 0);
        $actualRevenue = $perMonth(fn(int $m) => $actual['months'][$m]['revenue']);
        $actualGp = $perMonth(fn(int $m) => $actual['months'][$m]['gp']);

        $rows = [[
            'label' => 'Booked Revenue',
            'kind' => 'money',
            'values' => $this->spread($bookedRevenue),
        ]];

        $salesTotal = array_fill_keys(range(1, 12), 0.0);

        foreach ($this->salesRows() as $salesId => $name) {
            $byMonth = ($targets[$salesId] ?? collect())->keyBy('month');
            $months = $perMonth(fn(int $m) => $byMonth->get($m)?->target_amount ?? 0);

            foreach ($months as $m => $amount) {
                $salesTotal[$m] += $amount;
            }

            $rows[] = [
                'label' => $name,
                'kind' => 'input',
                'sales_id' => $salesId,
                'values' => $this->spread($months),
            ];
        }

        $rows[] = [
            'label' => 'Total Target Sales',
            'kind' => 'money',
            'values' => $this->spread($salesTotal),
            // Beda dengan Booked Revenue = ada target yang belum terdistribusi ke sales.
            'compare' => $this->spread($bookedRevenue),
        ];

        $rows[] = [
            'label' => 'Booked GP Target',
            'kind' => 'money',
            'values' => $this->spread($bookedGp),
        ];

        $rows[] = [
            'label' => 'Actual Booked Revenue',
            'kind' => 'money',
            'values' => $this->spread($actualRevenue),
        ];

        $rows[] = [
            'label' => 'Actual Booked GP',
            'kind' => 'money',
            'values' => $this->spread($actualGp),
        ];

        // Dasarnya target per sales — itu yang dimaksud "Sales Target" di sheet, dan di
        // sana Booked Revenue memang jumlah baris sales. Booked Revenue dari Finance
        // hanya dipakai untuk bulan yang target salesnya belum diisi.
        $achievementBase = $perMonth(
            fn(int $m) => $salesTotal[$m] > 0 ? $salesTotal[$m] : $bookedRevenue[$m]
        );

        $rows[] = [
            'label' => '% of Sales Target Achievement',
            'kind' => 'percent',
            'values' => $this->ratio($actualRevenue, $achievementBase),
        ];

        $rows[] = [
            'label' => '% of Profit Margin',
            'kind' => 'percent',
            'values' => $this->ratio($actualGp, $actualRevenue),
            'compare' => $this->spread($benchmark, average: true),
        ];

        $rows[] = [
            'label' => '% of Profit Margin Benchmark',
            'kind' => 'percent',
            'values' => $this->spread($benchmark, average: true),
        ];

        return $rows;
    }

    // -------------------------------------------------------
    // Simpan
    // -------------------------------------------------------

    public function canEdit(): bool
    {
        return auth()->user()?->can('Update:SalesTarget') ?? false;
    }

    public function save(): void
    {
        abort_unless($this->canEdit(), 403);

        $saved = 0;

        foreach ($this->cells as $salesId => $months) {
            foreach ($months as $month => $raw) {
                $amount = (int) preg_replace('/\D/', '', (string) $raw);

                $existing = SalesTarget::forSales((int) $salesId)
                    ->forMonth((int) $this->year, (int) $month)
                    ->first();

                if ($amount <= 0) {
                    // Kosong/0 tanpa baris lama: tidak perlu bikin baris sampah.
                    $existing?->delete();
                    continue;
                }

                if ($existing && (int) $existing->target_amount === $amount) {
                    continue;
                }

                SalesTarget::updateOrCreate(
                    [
                        'bv_sales_list_id' => (int) $salesId,
                        'year' => (int) $this->year,
                        'month' => (int) $month,
                    ],
                    [
                        'target_amount' => $amount,
                        'updated_by' => auth()->id(),
                        'created_by' => $existing?->created_by ?? auth()->id(),
                    ]
                );

                $saved++;
            }
        }

        $this->loadCells();
        $this->dispatch('salesTargetYearChanged', year: (int) $this->year);

        Notification::make()
            ->title($saved > 0 ? "{$saved} target tersimpan" : 'Tidak ada perubahan')
            ->success()
            ->send();
    }

    // -------------------------------------------------------
    // Helper internal
    // -------------------------------------------------------

    private function loadCells(): void
    {
        $targets = SalesTarget::forYear((int) $this->year)->get()->groupBy('bv_sales_list_id');

        $this->cells = [];

        foreach (array_keys($this->salesRows()) as $salesId) {
            $byMonth = ($targets[$salesId] ?? collect())->keyBy('month');

            foreach (range(1, 12) as $month) {
                $amount = (int) ($byMonth->get($month)?->target_amount ?? 0);
                $this->cells[$salesId][$month] = $amount > 0 ? number_format($amount, 0, ',', '.') : '';
            }
        }
    }

    /** Sebar nilai bulanan ke seluruh kolom (bulan + Q1–Q4 + total tahun). */
    private function spread(array $months, bool $average = false): array
    {
        $values = [];
        $sum = fn(array $list) => array_sum($list);

        foreach ($months as $month => $value) {
            $values["m{$month}"] = (float) $value;
        }

        foreach (range(1, 4) as $quarter) {
            $slice = array_intersect_key($months, array_flip(SalesTarget::quarterMonths($quarter)));
            $values["q{$quarter}"] = $average ? $this->mean($slice) : $sum($slice);
        }

        $values['year'] = $average ? $this->mean($months) : $sum($months);

        return $values;
    }

    /** Persentase per kolom, dihitung dari total kolomnya — bukan rata-rata persentase bulanan. */
    private function ratio(array $numerator, array $denominator): array
    {
        $top = $this->spread($numerator);
        $bottom = $this->spread($denominator);

        $values = [];
        foreach ($top as $key => $value) {
            $values[$key] = ($bottom[$key] ?? 0) > 0 ? round($value / $bottom[$key] * 100, 2) : 0.0;
        }

        return $values;
    }

    /** Rata-rata dari nilai yang terisi saja — bulan tanpa target tidak menarik benchmark ke bawah. */
    private function mean(array $values): float
    {
        $filled = array_filter($values, fn($value) => (float) $value > 0);

        return $filled === [] ? 0.0 : round(array_sum($filled) / count($filled), 2);
    }

    // -------------------------------------------------------
    // Widget
    // -------------------------------------------------------

    public function getWidgetData(): array
    {
        return ['year' => (int) $this->year];
    }

    protected function getHeaderWidgets(): array
    {
        return [
            SalesTargetRealizationWidget::class,
            SalesTargetAchievementChart::class,
        ];
    }

    public function getHeaderWidgetsColumns(): int|array
    {
        return 1;
    }
}
