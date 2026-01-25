<?php

namespace App\Filament\Resources\BvCampignUpcomings\Pages;

use App\Filament\Resources\BvCampignUpcomings\BvCampignUpcomingResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewBvCampignUpcoming extends ViewRecord
{
    protected static string $resource = BvCampignUpcomingResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
        ];
    }
}
