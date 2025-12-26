<?php

namespace App\Filament\Resources\BvQuotations\Pages;

use App\Filament\Resources\BvQuotations\BvQuotationResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListBvQuotations extends ListRecords
{
    protected static string $resource = BvQuotationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
