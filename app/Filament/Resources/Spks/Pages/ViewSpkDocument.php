<?php

namespace App\Filament\Resources\Spks\Pages;

use App\Filament\Resources\Spks\SpkResource;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewSpkDocument extends ViewRecord
{
    protected static string $resource = SpkResource::class;

    protected string $view = 'filament.resources.spks.pages.view-spk-document';

    protected function getHeaderActions(): array
    {
        return [
            Action::make('print')
                ->label('Print')
                ->icon('heroicon-o-printer')
                ->extraAttributes([
                    'onclick' => 'window.print(); return false;',
                ]),
            EditAction::make(),
        ];
    }
}
