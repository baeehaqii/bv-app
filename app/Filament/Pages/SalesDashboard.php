<?php

namespace App\Filament\Pages;

use App\Enums\ClientStatus;
use App\Enums\SalesStatus;
use App\Models\BvSales;
use App\Models\BvSalesList;
use App\Models\DataClient;
use App\Models\SalesTarget;
use Carbon\Carbon;
use Filament\Pages\Page;
use Livewire\Attributes\Url;

class SalesDashboard extends Page
{
    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-presentation-chart-line';

    protected static ?string $navigationLabel = 'Dashboard';

    protected static ?string $title = 'Sales Dashboard';

    protected static string|\UnitEnum|null $navigationGroup = 'Sales';

    protected static ?int $navigationSort = 0;

    protected static ?string $slug = 'sales-dashboard';

    protected string $view = 'filament.pages.sales-dashboard';

    private const EXECUTIVE_ROLES = ['super_admin', 'superadmin', 'Super Admin', 'CEO', 'COO'];

    private const SALES_ROLES = ['Sales/BD', 'sales', 'bd', 'Sales', 'BD', 'Business Development'];

    #[Url]
    public string $dateFilter = 'today';

    #[Url]
    public ?int $selectedSalesId = null;

    public static function shouldRegisterNavigation(): bool
    {
        return auth()->check() && auth()->user()->hasRole([...self::SALES_ROLES, ...self::EXECUTIVE_ROLES]);
    }

    public function isExecutiveViewer(): bool
    {
        return auth()->user()?->hasRole(self::EXECUTIVE_ROLES) ?? false;
    }

    /** Daftar sales untuk selector executive */
    public function getSalesOptions(): array
    {
        return BvSalesList::orderBy('nama_sales')->pluck('nama_sales', 'id')->all();
    }

    public function getSalesList(): ?BvSalesList
    {
        // Executive bebas memonitor sales mana pun; default ke datanya sendiri
        // bila ada, atau sales pertama bila belum memilih.
        if ($this->isExecutiveViewer()) {
            if ($this->selectedSalesId) {
                return BvSalesList::find($this->selectedSalesId);
            }

            return BvSalesList::where('user_id', auth()->id())->first()
                ?? BvSalesList::orderBy('nama_sales')->first();
        }

        // Sales biasa hanya melihat datanya sendiri.
        return BvSalesList::where('user_id', auth()->id())->first();
    }

    public function getDisplayName(): string
    {
        $sales = $this->getSalesList();

        return $sales?->nama_sales ?? auth()->user()->name;
    }

    public function getCurrentDateLabel(): string
    {
        return Carbon::now()->translatedFormat('l, d M Y');
    }

    public function getQuickStats(): array
    {
        $sales = $this->getSalesList();
        if (! $sales) {
            return [
                ['label' => 'My Deals', 'value' => 0, 'color' => 'primary', 'icon' => 'heroicon-m-funnel'],
                ['label' => 'Critical', 'value' => 0, 'color' => 'danger', 'icon' => 'heroicon-m-exclamation-triangle'],
                ['label' => 'In Progress', 'value' => 0, 'color' => 'warning', 'icon' => 'heroicon-m-bolt'],
                ['label' => 'Overdue', 'value' => 0, 'color' => 'danger', 'icon' => 'heroicon-m-clock'],
            ];
        }

        $base = BvSales::where('bv_sales_list_id', $sales->id);
        $now = Carbon::now();

        $closedStatuses = [SalesStatus::PAID->value, SalesStatus::CLOSE_LOSE->value];
        $inProgressStatuses = [
            SalesStatus::PROPOSAL_BUILDING->value,
            SalesStatus::NEGOTIATION->value,
            SalesStatus::CAMPAIGN_LIVE->value,
            SalesStatus::REPORTING->value,
            SalesStatus::INVOICING->value,
        ];

        // My Deals: hanya campaign yang sudah selesai/deal (Campaign Live → Paid).
        $myDeals = (clone $base)->whereIn('status', $this->wonStatuses())->count();

        $critical = (clone $base)
            ->whereNotIn('status', $closedStatuses)
            ->whereNotNull('close_date')
            ->whereBetween('close_date', [$now->copy()->toDateString(), $now->copy()->addDays(3)->toDateString()])
            ->count();

        $inProgress = (clone $base)->whereIn('status', $inProgressStatuses)->count();

        $overdue = (clone $base)
            ->whereNotIn('status', $closedStatuses)
            ->whereNotNull('close_date')
            ->whereDate('close_date', '<', $now->toDateString())
            ->count();

        return [
            ['label' => 'My Deals', 'value' => $myDeals, 'color' => 'primary', 'icon' => 'heroicon-m-funnel'],
            ['label' => 'Critical', 'value' => $critical, 'color' => 'danger', 'icon' => 'heroicon-m-exclamation-triangle'],
            ['label' => 'In Progress', 'value' => $inProgress, 'color' => 'warning', 'icon' => 'heroicon-m-bolt'],
            ['label' => 'Overdue', 'value' => $overdue, 'color' => 'danger', 'icon' => 'heroicon-m-clock'],
        ];
    }

    public function getMyTarget(): array
    {
        $sales = $this->getSalesList();
        $now = Carbon::now();
        $year = $now->year;
        $month = $now->month;

        if (! $sales) {
            return [
                'month' => ['target' => 0, 'achieved' => 0, 'percent' => 0],
                'year' => ['target' => 0, 'achieved' => 0, 'percent' => 0],
            ];
        }

        $wonStatuses = $this->wonStatuses();
        $base = BvSales::where('bv_sales_list_id', $sales->id);

        $monthTarget = (float) SalesTarget::forSales($sales->id)->forMonth($year, $month)->value('target_amount') ?? 0;
        $monthAchieved = (float) (clone $base)
            ->whereIn('status', $wonStatuses)
            ->whereYear('close_date', $year)
            ->whereMonth('close_date', $month)
            ->sum('deal_value');

        $yearTarget = SalesTarget::totalForSalesYear($sales->id, $year);
        $yearAchieved = (float) (clone $base)
            ->whereIn('status', $wonStatuses)
            ->whereYear('close_date', $year)
            ->sum('deal_value');

        return [
            'month' => [
                'target' => $monthTarget,
                'achieved' => $monthAchieved,
                'percent' => $monthTarget > 0 ? min(round(($monthAchieved / $monthTarget) * 100), 100) : 0,
            ],
            'year' => [
                'target' => $yearTarget,
                'achieved' => $yearAchieved,
                'percent' => $yearTarget > 0 ? min(round(($yearAchieved / $yearTarget) * 100), 100) : 0,
            ],
        ];
    }

    public function getClientCategoryStats(): array
    {
        $sales = $this->getSalesList();
        if (! $sales) {
            return [];
        }

        $rows = DataClient::where('pic_internal_sales_id', $sales->id)
            ->whereNotNull('category')
            ->selectRaw('category, count(*) as total')
            ->groupBy('category')
            ->orderByDesc('total')
            ->limit(4)
            ->get();

        if ($rows->isEmpty()) {
            return [];
        }

        $max = $rows->max('total');
        $colors = ['bg-primary-500', 'bg-emerald-500', 'bg-amber-500', 'bg-rose-500'];
        $maxBarPx = 96;

        return $rows->values()->map(fn ($row, $i) => [
            'category' => $row->category,
            'total' => $row->total,
            'percent' => $max > 0 ? round(($row->total / $max) * 100) : 0,
            'height_px' => $max > 0 ? max((int) round(($row->total / $max) * $maxBarPx), 4) : 4,
            'color' => $colors[$i] ?? 'bg-gray-400',
        ])->toArray();
    }

    public function getActivePipelineForCard(): array
    {
        $sales = $this->getSalesList();
        if (! $sales) {
            return [];
        }

        $excludedStatuses = [SalesStatus::PAID->value, SalesStatus::CLOSE_LOSE->value];

        $statusOrder = [
            SalesStatus::NOT_STARTED->value,
            SalesStatus::PITCHING->value,
            SalesStatus::BRIEFING->value,
            SalesStatus::PROPOSAL_BUILDING->value,
            SalesStatus::NEGOTIATION->value,
            SalesStatus::CAMPAIGN_LIVE->value,
            SalesStatus::REPORTING->value,
            SalesStatus::INVOICING->value,
            SalesStatus::PAID->value,
        ];

        $statusTimeline = collect(SalesStatus::cases())
            ->filter(fn ($s) => $s !== SalesStatus::CLOSE_LOSE)
            ->map(fn ($s) => [
                'value' => $s->value,
                'label' => $s->getLabel(),
                'icon' => $s->getIcon(),
            ])
            ->values()
            ->toArray();

        return BvSales::where('bv_sales_list_id', $sales->id)
            ->whereNotIn('status', $excludedStatuses)
            ->orderByRaw("FIELD(status, '".implode("','", array_reverse($statusOrder))."')")
            ->limit(6)
            ->get(['id', 'event_name', 'company_name', 'status', 'close_date', 'deal_value'])
            ->map(function (BvSales $deal) use ($statusTimeline, $statusOrder) {
                $currentStatus = $deal->status?->value ?? SalesStatus::NOT_STARTED->value;
                $currentIndex = array_search($currentStatus, $statusOrder);

                $timeline = array_map(function ($step, $i) use ($currentIndex) {
                    return [
                        'value' => $step['value'],
                        'label' => $step['label'],
                        'icon' => $step['icon'],
                        'done' => $i < $currentIndex,
                        'active' => $i === $currentIndex,
                    ];
                }, $statusTimeline, array_keys($statusTimeline));

                return [
                    'id' => $deal->id,
                    'title' => $deal->event_name ?? 'Untitled',
                    'company' => $deal->company_name ?? '',
                    'status' => $currentStatus,
                    'status_label' => $deal->status?->getLabel() ?? 'New Leads',
                    'close_date' => $deal->close_date?->translatedFormat('d M Y'),
                    'is_overdue' => $deal->close_date && $deal->close_date->isPast(),
                    'deal_value' => $deal->deal_value ? 'Rp '.number_format((float) $deal->deal_value, 0, ',', '.') : null,
                    'timeline' => $timeline,
                    'current_step' => $currentIndex !== false ? $currentIndex : 0,
                ];
            })
            ->toArray();
    }

    public function getMyPipeline(): array
    {
        $sales = $this->getSalesList();
        if (! $sales) {
            return [];
        }

        $closedStatuses = [SalesStatus::PAID->value, SalesStatus::CLOSE_LOSE->value];

        return BvSales::where('bv_sales_list_id', $sales->id)
            ->whereNotIn('status', $closedStatuses)
            ->orderBy('close_date')
            ->limit(6)
            ->get(['id', 'event_name', 'company_name', 'close_date', 'status', 'updated_at'])
            ->map(fn (BvSales $deal) => [
                'id' => $deal->id,
                'title' => $deal->event_name ?? 'Untitled',
                'company' => $deal->company_name ?? '',
                'close_date' => $deal->close_date?->translatedFormat('d M Y'),
                'status' => $deal->status?->value ?? 'not_started',
                'status_label' => $deal->status?->getLabel() ?? 'Not Started',
                'is_overdue' => $deal->close_date && $deal->close_date->isPast(),
            ])
            ->toArray();
    }

    public function getMyClients(): array
    {
        $sales = $this->getSalesList();
        if (! $sales) {
            return [];
        }

        return DataClient::where('pic_internal_sales_id', $sales->id)
            ->orderBy('nama_brand')
            ->limit(8)
            ->get(['id', 'nama_brand', 'type', 'category', 'status_client', 'date_follow_up', 'priority'])
            ->map(fn ($client) => [
                'id' => $client->id,
                'name' => $client->nama_brand,
                'type' => $client->type,
                'category' => $client->category ?? '—',
                'status' => ClientStatus::labelFor($client->status_client) ?? '—',
                'follow_up' => $client->date_follow_up ? Carbon::parse($client->date_follow_up)->translatedFormat('d M Y') : null,
                'priority' => $client->priority,
            ])
            ->toArray();
    }

    public function getNotifications(): array
    {
        $sales = $this->getSalesList();
        if (! $sales) {
            return [];
        }

        return BvSales::where('bv_sales_list_id', $sales->id)
            ->orderByDesc('updated_at')
            ->limit(8)
            ->get(['id', 'event_name', 'company_name', 'status', 'updated_at'])
            ->map(function (BvSales $deal) use ($sales) {
                $statusLabel = $deal->status?->getLabel() ?? 'Updated';

                return [
                    'actor' => $sales->nama_sales ?? 'You',
                    'avatar' => $sales->user?->avatar_url,
                    'message' => 'Deal "'.($deal->event_name ?? 'Untitled')."\" status berubah ke {$statusLabel}",
                    'time' => $deal->updated_at?->diffForHumans(),
                    'is_new' => $deal->updated_at && $deal->updated_at->gt(Carbon::now()->subDay()),
                ];
            })
            ->toArray();
    }

    private function wonStatuses(): array
    {
        return SalesStatus::wonValues();
    }

    public function getWidgets(): array
    {
        return [];
    }

    protected function getHeaderWidgets(): array
    {
        return [];
    }

    protected function getFooterWidgets(): array
    {
        return [];
    }
}
