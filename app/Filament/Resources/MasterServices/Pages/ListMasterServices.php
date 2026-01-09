<?php

namespace App\Filament\Resources\MasterServices\Pages;

use App\Filament\Resources\MasterServices\MasterServiceResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListMasterServices extends ListRecords
{
    protected static string $resource = MasterServiceResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
