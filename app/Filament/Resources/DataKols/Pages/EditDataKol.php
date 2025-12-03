<?php

namespace App\Filament\Resources\DataKols\Pages;

use App\Filament\Resources\DataKols\DataKolResource;
use Filament\Actions\Action;
use Filament\Resources\Pages\EditRecord;

class EditDataKol extends EditRecord
{
    protected static string $resource = DataKolResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('save')
                ->label('Save Changes')
                ->action('save')
                ->keyBindings(['mod+s']),
        ];
    }

    protected function getFormActions(): array
    {
        return [];
    }
}

