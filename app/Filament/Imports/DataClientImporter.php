<?php

namespace App\Filament\Imports;

use App\Models\DataClient;
use Filament\Actions\Imports\ImportColumn;
use Filament\Actions\Imports\Importer;
use Filament\Actions\Imports\Models\Import;
use Illuminate\Support\Number;

class DataClientImporter extends Importer
{
    protected static ?string $model = DataClient::class;

    public static function getColumns(): array
    {
        return [
            ImportColumn::make('nama_brand')
                ->label('Nama Brand / Client')
                ->requiredMapping()
                ->rules(['required', 'max:255'])
                ->example('Garuda Food'),

            ImportColumn::make('type')
                ->label('Tipe (direct / agency)')
                ->rules(['nullable', 'in:direct,agency'])
                ->example('direct')
                ->helperText('Isi dengan "direct" atau "agency".'),

            ImportColumn::make('category')
                ->label('Kategori')
                ->rules(['nullable', 'max:255'])
                ->example('FMCG'),

            ImportColumn::make('priority')
                ->label('Prioritas (High/Medium/Low)')
                ->rules(['nullable', 'in:High,Medium,Low'])
                ->example('High'),

            ImportColumn::make('website')
                ->label('Website')
                ->rules(['nullable', 'url', 'max:255'])
                ->example('https://example.com'),

            ImportColumn::make('status')
                ->label('Status')
                ->rules(['nullable', 'max:100'])
                ->example('Active'),

            ImportColumn::make('status_client')
                ->label('Status Client')
                ->rules(['nullable', 'max:100'])
                ->example('Prospect'),

            ImportColumn::make('date_outreach')
                ->label('Tanggal Outreach (YYYY-MM-DD)')
                ->rules(['nullable', 'date'])
                ->example('2026-01-15'),

            ImportColumn::make('date_follow_up')
                ->label('Tanggal Follow Up (YYYY-MM-DD)')
                ->rules(['nullable', 'date'])
                ->example('2026-02-01'),

            ImportColumn::make('instagram')
                ->label('Instagram')
                ->rules(['nullable', 'max:255'])
                ->example('@garudafood'),

            ImportColumn::make('tiktok')
                ->label('TikTok')
                ->rules(['nullable', 'max:255'])
                ->example('@garudafood'),

            ImportColumn::make('youtube')
                ->label('YouTube')
                ->rules(['nullable', 'max:255'])
                ->example('GarudaFood Official'),

            ImportColumn::make('threads')
                ->label('Threads')
                ->rules(['nullable', 'max:255'])
                ->example('@garudafood'),

            ImportColumn::make('account_owner')
                ->label('Account Owner')
                ->rules(['nullable', 'max:255'])
                ->example('Budi Santoso'),

            ImportColumn::make('parent_brand')
                ->label('Parent Brand')
                ->rules(['nullable', 'max:255'])
                ->example('Garudafood Group'),

            ImportColumn::make('top')
                ->label('TOP (days)')
                ->integer()
                ->rules(['nullable', 'integer', 'min:0'])
                ->example('30'),

            ImportColumn::make('notes')
                ->label('Catatan')
                ->rules(['nullable'])
                ->example('Client potensial Q2'),
        ];
    }

    public function resolveRecord(): DataClient
    {
        return DataClient::firstOrNew([
            'nama_brand' => $this->data['nama_brand'],
        ]);
    }

    public static function getCompletedNotificationBody(Import $import): string
    {
        $body = 'Import data client selesai. ' . Number::format($import->successful_rows) . ' ' . str('baris')->plural($import->successful_rows) . ' berhasil diimport.';

        if ($failedRowsCount = $import->getFailedRowsCount()) {
            $body .= ' ' . Number::format($failedRowsCount) . ' ' . str('baris')->plural($failedRowsCount) . ' gagal diimport.';
        }

        return $body;
    }
}
