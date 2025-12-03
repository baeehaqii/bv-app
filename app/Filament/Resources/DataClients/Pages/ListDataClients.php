<?php

namespace App\Filament\Resources\DataClients\Pages;

use App\Filament\Resources\DataClients\DataClientResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListDataClients extends ListRecords
{
    protected static string $resource = DataClientResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
