<?php

namespace App\Filament\Resources\MasterMargins\Pages;

use App\Filament\Resources\MasterMargins\MasterMarginResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListMasterMargins extends ListRecords
{
    protected static string $resource = MasterMarginResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
