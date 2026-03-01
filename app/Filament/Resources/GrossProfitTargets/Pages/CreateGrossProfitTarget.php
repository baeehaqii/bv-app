<?php

namespace App\Filament\Resources\GrossProfitTargets\Pages;

use App\Filament\Resources\GrossProfitTargets\GrossProfitTargetResource;
use Filament\Resources\Pages\CreateRecord;

class CreateGrossProfitTarget extends CreateRecord
{
    protected static string $resource = GrossProfitTargetResource::class;

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
