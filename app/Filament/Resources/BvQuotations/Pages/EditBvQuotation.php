<?php

namespace App\Filament\Resources\BvQuotations\Pages;

use App\Filament\Resources\BvQuotations\BvQuotationResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditBvQuotation extends EditRecord
{
    protected static string $resource = BvQuotationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
