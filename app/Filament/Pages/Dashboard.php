<?php

namespace App\Filament\Pages;

use App\Filament\Resources\BvCampigns\BvCampignResource;
use App\Filament\Resources\BvCashflows\BvCashflowResource;
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

    private const EXECUTIVE_ROLES = ['super_admin', 'superadmin', 'Super Admin', 'CEO', 'COO'];

    #[Url]
    public string $dateFilter = 'today';

    public static function shouldRegisterNavigation(): bool
    {
        return auth()->check() && auth()->user()->hasRole(self::EXECUTIVE_ROLES);
    }

    public function mount()
    {
        $user = auth()->user();

        // User dengan role executive tetap diizinkan membuka dashboard executive,
        // meskipun juga memiliki role lain seperti Sales.
        if ($user->hasRole(self::EXECUTIVE_ROLES)) {
            return;
        }

        if ($user->hasRole(['Finance', 'finance'])) {
            return redirect()->to(BvCashflowResource::getUrl());
        }

        if ($user->hasRole(['Operation KOL & Creative', 'Operation', 'Creative', 'KOL'])) {
            return redirect()->to(BvCampignResource::getUrl());
        }

        if ($user->hasRole(['Sales/BD', 'sales', 'bd', 'Sales', 'BD', 'Business Development'])) {
            return redirect()->to(SalesDashboard::getUrl());
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
