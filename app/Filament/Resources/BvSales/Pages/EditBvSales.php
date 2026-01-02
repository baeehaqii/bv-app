<?php

namespace App\Filament\Resources\BvSales\Pages;

use App\Filament\Resources\BvSales\BvSalesResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditBvSales extends EditRecord
{
    protected static string $resource = BvSalesResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
