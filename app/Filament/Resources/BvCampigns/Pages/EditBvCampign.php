<?php

namespace App\Filament\Resources\BvCampigns\Pages;

use App\Filament\Resources\BvCampigns\BvCampignResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditBvCampign extends EditRecord
{
    protected static string $resource = BvCampignResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
