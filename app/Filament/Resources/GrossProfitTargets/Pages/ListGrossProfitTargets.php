<?php

namespace App\Filament\Resources\GrossProfitTargets\Pages;

use App\Filament\Resources\GrossProfitTargets\GrossProfitTargetResource;
use App\Filament\Widgets\GrossProfitTargetWidget;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListGrossProfitTargets extends ListRecords
{
    protected static string $resource = GrossProfitTargetResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->label('Tambah Target Bulan'),
        ];
    }

    protected function getHeaderWidgets(): array
    {
        return [
            GrossProfitTargetWidget::class,
        ];
    }
}
