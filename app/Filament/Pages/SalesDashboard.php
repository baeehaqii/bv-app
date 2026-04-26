<?php

namespace App\Filament\Pages;

use App\Filament\Widgets\Sales\SalesActivityFeedWidget;
use App\Filament\Widgets\Sales\SalesPersonalStatsWidget;
use App\Filament\Widgets\Sales\SalesPersonalTargetWidget;
use App\Models\BvSalesList;
use Carbon\Carbon;
use Filament\Pages\Page;
use Livewire\Attributes\Url;

class SalesDashboard extends Page
{
    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-home';
    protected static ?string $navigationLabel = 'Dashboard';
    protected static ?string $title = 'Sales Dashboard';
    protected static string|\UnitEnum|null $navigationGroup = 'Sales';
    protected static ?int $navigationSort = 0;
    protected static ?string $slug = 'sales-dashboard';
    protected string $view = 'filament.pages.sales-dashboard';

    #[Url]
    public string $dateFilter = 'today';

    public static function shouldRegisterNavigation(): bool
    {
        return auth()->check() && auth()->user()->hasRole(['Sales/BD', 'sales', 'bd', 'Sales', 'BD', 'Business Development']);
    }

    public function updated(string $name): void
    {
        if ($name === 'dateFilter') {
            $this->dispatch('salesDashboardFilterChanged', dateFilter: $this->dateFilter);
        }
    }

    public function getSubheading(): ?string
    {
        return null; // rendered manually in blade
    }

    public function getGreeting(): string
    {
        $user = auth()->user();
        $salesList = BvSalesList::where('user_id', $user->id)->first();
        $name = $salesList?->nama_sales ?? $user->name;
        $greeting = match (true) {
            Carbon::now()->hour < 12 => 'Selamat pagi',
            Carbon::now()->hour < 17 => 'Selamat siang',
            default => 'Selamat sore',
        };

        return "{$greeting}, {$name}! Berikut ringkasan aktivitas sales kamu hari ini.";
    }

    protected function getHeaderWidgets(): array
    {
        return [];
    }

    protected function getFooterWidgets(): array
    {
        return [];
    }

    public function getWidgets(): array
    {
        return [
            SalesPersonalTargetWidget::class,
            SalesPersonalStatsWidget::class,
            SalesActivityFeedWidget::class,
        ];
    }
}
