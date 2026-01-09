<?php

namespace App\Filament\Resources\MasterServices\Pages;

use App\Filament\Resources\MasterServices\MasterServiceResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditMasterService extends EditRecord
{
    protected static string $resource = MasterServiceResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
