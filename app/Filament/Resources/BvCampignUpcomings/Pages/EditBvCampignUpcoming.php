<?php

namespace App\Filament\Resources\BvCampignUpcomings\Pages;

use App\Filament\Resources\BvCampignUpcomings\BvCampignUpcomingResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;

class EditBvCampignUpcoming extends EditRecord
{
    protected static string $resource = BvCampignUpcomingResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
            DeleteAction::make(),
        ];
    }
}
