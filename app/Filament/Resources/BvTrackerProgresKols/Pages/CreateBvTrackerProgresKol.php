<?php

namespace App\Filament\Resources\BvTrackerProgresKols\Pages;

use App\Filament\Resources\BvTrackerProgresKols\BvTrackerProgresKolResource;
use Filament\Resources\Pages\CreateRecord;

class CreateBvTrackerProgresKol extends CreateRecord
{
    protected static string $resource = BvTrackerProgresKolResource::class;

    public function canCreateAnother(): bool
    {
        return false;
    }
}
