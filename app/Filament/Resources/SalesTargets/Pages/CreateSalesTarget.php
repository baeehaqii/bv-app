<?php

namespace App\Filament\Resources\SalesTargets\Pages;

use App\Filament\Resources\SalesTargets\SalesTargetResource;
use Filament\Resources\Pages\CreateRecord;

class CreateSalesTarget extends CreateRecord
{
    protected static string $resource = SalesTargetResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['created_by'] = auth()->id();
        $data['updated_by'] = auth()->id();
        return $data;
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
