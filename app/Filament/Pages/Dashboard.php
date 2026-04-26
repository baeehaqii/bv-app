<?php

namespace App\Filament\Pages;

use App\Filament\Widgets\BdManagerReportWidget;
use App\Filament\Widgets\ClientDemographyChart;
use App\Filament\Widgets\ClientStatusChart;
use App\Filament\Widgets\GrossProfitTargetWidget;
use App\Filament\Widgets\RevenueStatsWidget;
use App\Filament\Widgets\TopSpenderWidget;
use Filament\Pages\Dashboard as BaseDashboard;
use Livewire\Attributes\Url;

class Dashboard extends BaseDashboard
{
    protected static ?string $title = 'Dashboard Executive';

    protected string $view = 'filament.pages.executive-dashboard';

    #[Url]
    public string $dateFilter = 'today';

    public static function shouldRegisterNavigation(): bool
    {
        return auth()->check() && auth()->user()->hasRole(['super_admin', 'superadmin', 'Super Admin', 'CEO', 'COO']);
    }

    public function mount()
    {
        $user = auth()->user();

        if ($user->hasRole(['Finance', 'finance'])) {
            return redirect()->to(\App\Filament\Resources\BvCashflows\BvCashflowResource::getUrl());
        }

        if ($user->hasRole(['Operation KOL & Creative', 'Operation', 'Creative', 'KOL'])) {
            return redirect()->to(\App\Filament\Resources\BvCampigns\BvCampignResource::getUrl());
        }

        if ($user->hasRole(['Sales/BD', 'sales', 'bd', 'Sales', 'BD', 'Business Development'])) {
            return redirect()->to(\App\Filament\Pages\SalesDashboard::getUrl());
        }
    }

    public function updated(string $name): void
    {
        if ($name === 'dateFilter') {
            $this->dispatch('executiveDashboardFilterChanged', dateFilter: $this->dateFilter);
        }
    }

    public function getWidgets(): array
    {
        return [
            GrossProfitTargetWidget::class,
            RevenueStatsWidget::class,
            BdManagerReportWidget::class,
            ClientStatusChart::class,
            ClientDemographyChart::class,
            TopSpenderWidget::class,
        ];
    }

    public function getColumns(): int|array
    {
        return 2;
    }
}
