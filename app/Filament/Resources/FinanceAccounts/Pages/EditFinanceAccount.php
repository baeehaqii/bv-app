<?php

namespace App\Filament\Resources\FinanceAccounts\Pages;

use App\Filament\Resources\FinanceAccounts\FinanceAccountResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditFinanceAccount extends EditRecord
{
    protected static string $resource = FinanceAccountResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
