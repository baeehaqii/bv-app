<?php

namespace App\Filament\Resources\DataVendors\Pages;

use App\Filament\Resources\DataVendors\DataVendorResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditDataVendor extends EditRecord
{
    protected static string $resource = DataVendorResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
