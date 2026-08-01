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
            // Print dari halaman ini ikut mencetak chrome Filament, jadi arahkan ke PDF-nya.
            Action::make('download_pdf')
                ->label('Download PDF')
                ->icon('heroicon-o-arrow-down-tray')
                ->url(fn($record) => route('kol-contract.download', $record)),

            Action::make('preview_pdf')
                ->label('Preview PDF')
                ->icon('heroicon-o-printer')
                ->color('gray')
                ->url(fn($record) => route('kol-contract.preview', $record), shouldOpenInNewTab: true),

            EditAction::make(),
        ];
    }
}
