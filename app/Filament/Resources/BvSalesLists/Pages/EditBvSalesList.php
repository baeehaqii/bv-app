<?php

namespace App\Filament\Resources\BvSalesLists\Pages;

use App\Filament\Resources\BvSalesLists\BvSalesListResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditBvSalesList extends EditRecord
{
    protected static string $resource = BvSalesListResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
