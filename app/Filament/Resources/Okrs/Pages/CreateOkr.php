<?php

namespace App\Filament\Resources\Okrs\Pages;

use App\Filament\Resources\Okrs\OkrResource;
use Filament\Resources\Pages\CreateRecord;

class CreateOkr extends CreateRecord
{
    protected static string $resource = OkrResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
