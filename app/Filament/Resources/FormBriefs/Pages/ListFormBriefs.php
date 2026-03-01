<?php

namespace App\Filament\Resources\FormBriefs\Pages;

use App\Filament\Resources\FormBriefs\FormBriefResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListFormBriefs extends ListRecords
{
    protected static string $resource = FormBriefResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->label('Buat Brief Baru'),
        ];
    }
}
