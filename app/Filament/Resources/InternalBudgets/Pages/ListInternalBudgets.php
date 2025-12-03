<?php

namespace App\Filament\Resources\InternalBudgets\Pages;

use App\Filament\Resources\InternalBudgets\InternalBudgetResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListInternalBudgets extends ListRecords
{
    protected static string $resource = InternalBudgetResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->label('Create Internal Budget'),
        ];
    }
}
