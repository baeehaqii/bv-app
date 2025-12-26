<?php

namespace App\Filament\Resources\DataClients\Pages;

use App\Filament\Resources\DataClients\DataClientResource;
use App\Filament\Resources\DataClients\Widgets\DataClientStatsWidget;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Forms\Components\Select;
use Filament\Resources\Pages\ListRecords;

class ListDataClients extends ListRecords
{
    protected static string $resource = DataClientResource::class;

    public $dateFilter = 'all';

    public function getHeaderWidgetsColumns(): int|array
    {
        return 2;
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
                ->color('primary')
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

            CreateAction::make(),
        ];
    }
}
