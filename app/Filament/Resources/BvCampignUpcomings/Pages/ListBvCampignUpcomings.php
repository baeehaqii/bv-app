<?php

namespace App\Filament\Resources\BvCampignUpcomings\Pages;

use App\Filament\Resources\BvCampignUpcomings\BvCampignUpcomingResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListBvCampignUpcomings extends ListRecords
{
    protected static string $resource = BvCampignUpcomingResource::class;

    protected function getHeaderActions(): array
    {
        return [
            // CreateAction::make(),
        ];
    }
}
