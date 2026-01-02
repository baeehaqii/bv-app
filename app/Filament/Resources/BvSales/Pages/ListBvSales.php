<?php

namespace App\Filament\Resources\BvSales\Pages;

use App\Filament\Resources\BvSales\BvSalesResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListBvSales extends ListRecords
{
    protected static string $resource = BvSalesResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
