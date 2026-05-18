<?php

namespace App\Filament\Resources\MasterSows\Pages;

use App\Filament\Resources\MasterSows\MasterSowResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListMasterSows extends ListRecords
{
    protected static string $resource = MasterSowResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
