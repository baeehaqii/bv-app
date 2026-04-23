<?php

namespace App\Filament\Resources\SalesTargets\Pages;

use App\Filament\Resources\SalesTargets\SalesTargetResource;
use App\Filament\Widgets\SalesTargetSyncWidget;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListSalesTargets extends ListRecords
{
    protected static string $resource = SalesTargetResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->label('Add Sales Target')
                ->visible(fn() => auth()->user()?->can('Create:SalesTarget')),
        ];
    }

    protected function getHeaderWidgets(): array
    {
        return [
            SalesTargetSyncWidget::class,
        ];
    }
}
