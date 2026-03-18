<?php

namespace App\Filament\Resources\BvBussinesDirectors\Pages;

use App\Filament\Resources\BvBussinesDirectors\BvBussinesDirectorResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditBvBussinesDirector extends EditRecord
{
    protected static string $resource = BvBussinesDirectorResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
