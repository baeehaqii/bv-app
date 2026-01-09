<?php

namespace App\Filament\Resources\BvPeformaKOLS\Pages;

use App\Filament\Resources\BvPeformaKOLS\BvPeformaKOLResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditBvPeformaKOL extends EditRecord
{
    protected static string $resource = BvPeformaKOLResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
