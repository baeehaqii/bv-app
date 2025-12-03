<?php

namespace App\Filament\Resources\InternalBudgets\Pages;

use App\Filament\Resources\InternalBudgets\InternalBudgetResource;
use Filament\Resources\Pages\CreateRecord;

class CreateInternalBudget extends CreateRecord
{
    protected static string $resource = InternalBudgetResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        return $data;
    }
}
