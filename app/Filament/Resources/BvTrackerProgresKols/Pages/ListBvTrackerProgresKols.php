<?php

namespace App\Filament\Resources\BvTrackerProgresKols\Pages;

use App\Filament\Resources\BvTrackerProgresKols\BvTrackerProgresKolResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListBvTrackerProgresKols extends ListRecords
{
    protected static string $resource = BvTrackerProgresKolResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
