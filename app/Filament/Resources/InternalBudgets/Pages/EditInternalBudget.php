<?php

namespace App\Filament\Resources\InternalBudgets\Pages;

use App\Filament\Resources\InternalBudgets\InternalBudgetResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditInternalBudget extends EditRecord
{
    protected static string $resource = InternalBudgetResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        return $data;
    }
}
