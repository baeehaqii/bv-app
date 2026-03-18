<?php

namespace App\Filament\Resources\BvBussinesDirectors\Pages;

use App\Filament\Resources\BvBussinesDirectors\BvBussinesDirectorResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListBvBussinesDirectors extends ListRecords
{
    protected static string $resource = BvBussinesDirectorResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
