<?php

namespace App\Filament\Resources\BvEmployes\Pages;

use App\Filament\Resources\BvEmployes\BvEmployeResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditBvEmploye extends EditRecord
{
    protected static string $resource = BvEmployeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
