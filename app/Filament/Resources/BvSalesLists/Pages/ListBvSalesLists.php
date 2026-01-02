<?php

namespace App\Filament\Resources\BvSalesLists\Pages;

use App\Filament\Resources\BvSalesLists\BvSalesListResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListBvSalesLists extends ListRecords
{
    protected static string $resource = BvSalesListResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
