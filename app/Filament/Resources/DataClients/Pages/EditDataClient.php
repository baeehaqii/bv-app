<?php

namespace App\Filament\Resources\DataClients\Pages;

use App\Filament\Resources\DataClients\DataClientResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditDataClient extends EditRecord
{
    protected static string $resource = DataClientResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
