<?php

namespace App\Filament\Resources\FormBriefs\Pages;

use App\Filament\Resources\FormBriefs\FormBriefResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;

class EditFormBrief extends EditRecord
{
    protected static string $resource = FormBriefResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
            DeleteAction::make(),
        ];
    }
}
