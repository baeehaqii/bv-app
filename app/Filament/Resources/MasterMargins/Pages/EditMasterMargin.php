<?php

namespace App\Filament\Resources\MasterMargins\Pages;

use App\Filament\Resources\MasterMargins\MasterMarginResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditMasterMargin extends EditRecord
{
    protected static string $resource = MasterMarginResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
