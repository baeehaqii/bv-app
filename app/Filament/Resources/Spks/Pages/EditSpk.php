<?php

namespace App\Filament\Resources\Spks\Pages;

use App\Filament\Resources\Spks\SpkResource;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditSpk extends EditRecord
{
    protected static string $resource = SpkResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('document')
                ->label('Lihat Dokumen')
                ->icon('heroicon-o-document-text')
                ->url(fn() => SpkResource::getUrl('document', ['record' => $this->record]))
                ->openUrlInNewTab(),
            DeleteAction::make(),
        ];
    }
}
