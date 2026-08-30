<?php

namespace App\Filament\Resources\MediaPlans\Pages;

use App\Filament\Resources\MediaPlans\MediaPlanResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListMediaPlans extends ListRecords
{
    protected static string $resource = MediaPlanResource::class;

    protected function getHeaderActions(): array
    {
        return [
            // Membuka halaman Migrasi Data dengan jenisnya sudah terpilih, bukan
            // menyalin alurnya ke sini — preview & migrasi per-chunk cuma perlu
            // ada di satu tempat.
            Actions\Action::make('migrasi_kol')
                ->label('Migrasi KOL dari Spreadsheet')
                ->icon('heroicon-o-arrow-down-on-square-stack')
                ->color('gray')
                ->visible(fn() => \App\Service\GoogleSheetReader::configured())
                ->url(\App\Filament\Pages\MigrasiData::getUrl(['jenis' => 'mediaplan'])),

            Actions\CreateAction::make()
                ->label('Create Media Plan'),
        ];
    }
}
