<?php

namespace App\Filament\Resources\MasterSows\Pages;

use App\Filament\Resources\MasterSows\MasterSowResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditMasterSow extends EditRecord
{
    protected static string $resource = MasterSowResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
