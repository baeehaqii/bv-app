<?php

namespace App\Filament\Resources\BvCashflows\Pages;

use App\Filament\Resources\BvCashflows\BvCashflowResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditBvCashflow extends EditRecord
{
    protected static string $resource = BvCashflowResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
