<?php

namespace App\Filament\Resources\MasterPphs\Pages;

use App\Filament\Resources\MasterPphs\MasterPphResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditMasterPph extends EditRecord
{
    protected static string $resource = MasterPphResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
