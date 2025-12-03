<?php

namespace App\Filament\Resources\DataVendors\Pages;

use App\Filament\Resources\DataVendors\DataVendorResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListDataVendors extends ListRecords
{
    protected static string $resource = DataVendorResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
