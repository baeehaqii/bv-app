<?php

namespace App\Filament\Resources\BvTrackerProgresKols\Pages;

use App\Filament\Resources\BvTrackerProgresKols\BvTrackerProgresKolResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditBvTrackerProgresKol extends EditRecord
{
    protected static string $resource = BvTrackerProgresKolResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
