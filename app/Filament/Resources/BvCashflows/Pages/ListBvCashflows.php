<?php

namespace App\Filament\Resources\BvCashflows\Pages;

use App\Filament\Resources\BvCashflows\BvCashflowResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListBvCashflows extends ListRecords
{
    protected static string $resource = BvCashflowResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
