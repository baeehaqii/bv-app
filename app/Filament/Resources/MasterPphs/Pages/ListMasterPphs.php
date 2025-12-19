<?php

namespace App\Filament\Resources\MasterPphs\Pages;

use App\Filament\Resources\MasterPphs\MasterPphResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListMasterPphs extends ListRecords
{
    protected static string $resource = MasterPphResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
