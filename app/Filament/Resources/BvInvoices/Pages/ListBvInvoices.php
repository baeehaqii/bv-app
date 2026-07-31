<?php

namespace App\Filament\Resources\BvInvoices\Pages;

use App\Filament\Resources\BvInvoices\BvInvoiceResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListBvInvoices extends ListRecords
{
    protected static string $resource = BvInvoiceResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
