<?php

namespace App\Filament\Resources\BvPeformaKOLS\Pages;

use App\Filament\Resources\BvPeformaKOLS\BvPeformaKOLResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListBvPeformaKOLS extends ListRecords
{
    protected static string $resource = BvPeformaKOLResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
