<?php

namespace App\Filament\Resources\BvInvoices\Pages;

use App\Filament\Resources\BvInvoices\BvInvoiceResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditBvInvoice extends EditRecord
{
    protected static string $resource = BvInvoiceResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
