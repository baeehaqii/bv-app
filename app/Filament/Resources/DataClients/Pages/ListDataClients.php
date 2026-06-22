<?php

namespace App\Filament\Resources\DataClients\Pages;

use App\Filament\Imports\DataClientImporter;
use App\Filament\Resources\DataClients\DataClientResource;
use App\Filament\Resources\DataClients\Widgets\DataClientStatsWidget;
use App\Models\DataClient;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Actions\ImportAction;
use Filament\Forms\Components\Select;
use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Components\Tabs\Tab;
use Illuminate\Database\Eloquent\Builder;

class ListDataClients extends ListRecords
{
    protected static string $resource = DataClientResource::class;

    public $dateFilter = 'all';

    public function getHeaderWidgetsColumns(): int|array
    {
        return 3;
    }

    public function getTabs(): array
    {
        return [
            'brand' => Tab::make('Database Brand')
                ->icon('heroicon-o-building-storefront')
                ->badge(DataClient::where('type', 'direct')->count())
                ->modifyQueryUsing(fn (Builder $query) => $query->where('type', 'direct')),

            'agency' => Tab::make('Database Agency')
                ->icon('heroicon-o-building-office-2')
                ->badge(DataClient::where('type', 'agency')->count())
                ->modifyQueryUsing(fn (Builder $query) => $query->where('type', 'agency')),
        ];
    }

    protected function getHeaderWidgets(): array
    {
        return [
            DataClientStatsWidget::make([
                'dateFilter' => $this->dateFilter,
            ]),
        ];
    }

    public function updatedDateFilter()
    {
        // This will trigger widget refresh when filter changes
    }

    protected function getHeaderActions(): array
    {
        return [
            // Action::make('kanban_view')
            //     ->label('Kanban View')
            //     ->icon('heroicon-o-view-columns')
            //     ->color('gray')
            //     ->url(fn() => DataClientResource::getUrl('kanban')),

            Action::make('dateFilter')
                ->label(fn() => match ($this->dateFilter) {
                    'today' => 'Filter: Today',
                    '7days' => 'Filter: 7 Days',
                    '14days' => 'Filter: 14 Days',
                    '30days' => 'Filter: 30 Days',
                    '60days' => 'Filter: 60 Days',
                    '90days' => 'Filter: 90 Days',
                    default => 'Filter: All Time',
                })
                ->icon('heroicon-o-funnel')
                ->color('white')
                ->form([
                    Select::make('filter')
                        ->label('Select Date Range')
                        ->options([
                            'today' => 'Today',
                            '7days' => '7 Days',
                            '14days' => '14 Days',
                            '30days' => '30 Days',
                            '60days' => '60 Days',
                            '90days' => '90 Days',
                            'all' => 'All Time',
                        ])
                        ->default($this->dateFilter)
                        ->required()
                        ->native(false),
                ])
                ->action(function (array $data) {
                    $this->dateFilter = $data['filter'];
                })
                ->modalHeading('Filter by Date Range')
                ->modalSubmitActionLabel('Apply Filter')
                ->modalWidth('sm'),

            CreateAction::make()
                ->label('Tambah Data Client'),

            ImportAction::make()
                ->label('Import CSV')
                ->importer(DataClientImporter::class)
                ->icon('heroicon-o-arrow-up-tray')
                ->color('gray')
                ->chunkSize(200)
                ->maxRows(5000),
        ];
    }
}
