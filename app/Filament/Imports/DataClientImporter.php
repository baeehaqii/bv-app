<?php

namespace App\Filament\Imports;

use App\Enums\ClientStatus;
use App\Models\BvSalesList;
use App\Models\DataClient;
use Filament\Actions\Imports\ImportColumn;
use Filament\Actions\Imports\Importer;
use Filament\Actions\Imports\Models\Import;
use Illuminate\Validation\Rule;
use Illuminate\Support\Number;

class DataClientImporter extends Importer
{
    protected static ?string $model = DataClient::class;

    public static function getColumns(): array
    {
        return [
            ImportColumn::make('type')
                ->label('Tipe (direct / agency)')
                ->rules(['nullable', 'in:direct,agency'])
                ->example('direct')
                ->helperText('Isi "direct" untuk brand, atau "agency".'),

            ImportColumn::make('nama_brand')
                ->label('Nama Brand / Agency')
                ->requiredMapping()
                ->rules(['required', 'max:255'])
                ->example('Garuda Food'),

            ImportColumn::make('category')
                ->label('Kategori')
                ->rules(['nullable', 'max:255'])
                ->example('FMCG'),

            ImportColumn::make('priority')
                ->label('Prioritas (P0/P1/P2/P3)')
                ->rules(['nullable', 'in:P0,P1,P2,P3'])
                ->example('P0'),

            ImportColumn::make('website')
                ->label('Website')
                ->rules(['nullable', 'url', 'max:255'])
                ->example('https://example.com'),

            ImportColumn::make('parent_brand')
                ->label('Parent Brand')
                ->rules(['nullable', 'max:255'])
                ->example('Garudafood Group'),

            ImportColumn::make('status_client')
                ->label('Status Client (' . implode('/', array_keys(ClientStatus::options())) . ')')
                ->rules(['nullable', Rule::in(array_keys(ClientStatus::options()))])
                ->example(ClientStatus::AWAITING_FEEDBACK->value),

            ImportColumn::make('status')
                ->label('Status Campaign')
                ->rules(['nullable', 'max:100'])
                ->example('not_started'),

            ImportColumn::make('pic_internal_sales')
                ->label('PIC Internal (Nama Sales)')
                ->example('Budi Santoso')
                ->fillRecordUsing(function (DataClient $record, ?string $state): void {
                    if (blank($state)) {
                        return;
                    }

                    $record->pic_internal_sales_id = BvSalesList::query()
                        ->where('nama_sales', trim($state))
                        ->value('id');
                }),

            ImportColumn::make('agency_handled_by')
                ->label('Dihandel Agency (nama agency, khusus direct)')
                ->example('IDEA Imaji')
                ->helperText('Untuk direct brand yang ditangani agency. Diabaikan jika tipe agency.')
                ->fillRecordUsing(function (DataClient $record, ?string $state): void {
                    if ($record->type !== 'direct' || blank($state)) {
                        return;
                    }

                    $record->agency_client_id = DataClient::query()
                        ->where('type', 'agency')
                        ->where('nama_brand', trim($state))
                        ->value('id');
                    $record->has_agency = true;
                }),

            ImportColumn::make('agency_brands')
                ->label('Brand yang Dihandel (khusus agency, pisah dengan ;)')
                ->example('Garuda Food; Indomie; Chitato')
                ->helperText('Daftar nama brand dipisah titik-koma. Hanya untuk tipe agency.')
                ->fillRecordUsing(function (DataClient $record, ?string $state): void {
                    if ($record->type !== 'agency' || blank($state)) {
                        return;
                    }

                    $names = collect(explode(';', $state))
                        ->map(fn (string $name): string => trim($name))
                        ->filter()
                        ->unique()
                        ->values();

                    if ($names->isEmpty()) {
                        return;
                    }

                    $directs = DataClient::query()
                        ->where('type', 'direct')
                        ->whereIn('nama_brand', $names->all())
                        ->get()
                        ->keyBy('nama_brand');

                    $record->agency_brands = $names->map(function (string $name) use ($directs): array {
                        $direct = $directs->get($name);
                        $pic = collect($direct?->pic_clients ?? [])->first() ?? [];

                        return [
                            'nama_brand' => $name,
                            'category' => $direct?->category,
                            'nama_pic' => $pic['name'] ?? $pic['nama_pic'] ?? null,
                            'email' => $pic['email'] ?? null,
                            'wa_number' => $pic['wa_number'] ?? null,
                            'description' => $direct?->notes,
                        ];
                    })->all();
                }),

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

            ImportColumn::make('top')
                ->label('TOP (hari)')
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
            'type' => ($this->data['type'] ?? '') ?: 'direct',
        ]);
    }

    public function getJobConnection(): ?string
    {
        return 'sync';
    }

    public static function getCompletedNotificationBody(Import $import): string
    {
        $body = 'Import data client selesai. '.Number::format($import->successful_rows).' '.str('baris')->plural($import->successful_rows).' berhasil diimport.';

        if ($failedRowsCount = $import->getFailedRowsCount()) {
            $body .= ' '.Number::format($failedRowsCount).' '.str('baris')->plural($failedRowsCount).' gagal diimport.';
        }

        return $body;
    }
}
