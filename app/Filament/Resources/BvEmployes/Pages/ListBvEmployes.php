<?php

namespace App\Filament\Resources\BvEmployes\Pages;

use App\Filament\Resources\BvEmployes\BvEmployeResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListBvEmployes extends ListRecords
{
    protected static string $resource = BvEmployeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
