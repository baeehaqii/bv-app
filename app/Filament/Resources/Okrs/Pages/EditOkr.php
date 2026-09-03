<?php

namespace App\Filament\Resources\Okrs\Pages;

use App\Filament\Resources\Okrs\OkrResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditOkr extends EditRecord
{
    protected static string $resource = OkrResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
