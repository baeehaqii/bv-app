<?php

namespace App\Filament\Resources\GrossProfitTargets\Pages;

use App\Filament\Resources\GrossProfitTargets\GrossProfitTargetResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditGrossProfitTarget extends EditRecord
{
    protected static string $resource = GrossProfitTargetResource::class;

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $data['updated_by'] = auth()->id();
        return $data;
    }

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
