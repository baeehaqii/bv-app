<?php

namespace App\Filament\Resources\BvCashflows\Tables;

use App\Models\BvCashflow;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DatePicker;
use Filament\Tables\Columns\Summarizers\Sum;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class BvCashflowsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('transaction_date')
                    ->label('Tanggal')
                    ->date('d M Y')
                    ->sortable(),

                TextColumn::make('type')
                    ->label('Jenis')
                    ->badge()
                    ->color(fn($state) => $state === 'income' ? 'success' : 'danger')
                    ->formatStateUsing(fn($state) => $state === 'income' ? 'Penerimaan' : 'Pengeluaran'),

                TextColumn::make('category')
                    ->label('Pos Akun (SAK)')
                    ->formatStateUsing(fn($state) => BvCashflow::ACCOUNTS[$state][0] ?? $state)
                    ->searchable()
                    ->wrap(),

                TextColumn::make('activity')
                    ->label('Aktivitas')
                    ->badge()
                    ->color('gray')
                    ->formatStateUsing(fn($state) => BvCashflow::ACTIVITIES[$state] ?? $state)
                    ->toggleable(),

                TextColumn::make('amount')
                    ->label('Nominal')
                    // locale id → "Rp 2.500.000,00" sesuai penyajian rupiah SAK.
                    ->money('IDR', locale: 'id')
                    ->sortable()
                    ->summarize(Sum::make()->label('Total')->money('IDR', locale: 'id')),

                TextColumn::make('financeAccount.name')
                    ->label('Akun')
                    ->placeholder('—')
                    ->toggleable(),

                TextColumn::make('dataClient.nama_brand')
                    ->label('Client')
                    ->placeholder('—')
                    ->searchable()
                    ->toggleable(),

                TextColumn::make('reference_no')
                    ->label('No. Ref')
                    ->placeholder('—')
                    ->searchable()
                    ->toggleable(),

                TextColumn::make('payment_method')
                    ->label('Metode')
                    ->formatStateUsing(fn($state) => BvCashflow::PAYMENT_METHODS[$state] ?? $state)
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('attachment')
                    ->label('Bukti')
                    ->url(fn($record) => $record->attachment ? asset('storage/' . $record->attachment) : null, true)
                    ->formatStateUsing(fn($state) => $state ? 'Lihat' : '—')
                    ->color('primary')
                    ->toggleable(),

                // Pakai state() bukan formatStateUsing(): source_type null tidak
                // melewati formatter, jadi baris manual tampil kosong.
                TextColumn::make('source_type')
                    ->label('Sumber')
                    ->badge()
                    ->state(fn($record) => $record->isAutoPosted() ? 'Otomatis' : 'Manual')
                    ->color(fn($state) => $state === 'Otomatis' ? 'info' : 'gray')
                    ->tooltip(fn($record) => $record->isAutoPosted()
                        ? 'Dicatat otomatis dari dokumen sumber — ubah di dokumen aslinya.'
                        : null),

                TextColumn::make('description')
                    ->label('Keterangan')
                    ->placeholder('—')
                    ->wrap()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('type')
                    ->label('Jenis Arus Kas')
                    ->options(['income' => 'Penerimaan Kas', 'expense' => 'Pengeluaran Kas']),

                SelectFilter::make('activity')
                    ->label('Aktivitas (PSAK 2)')
                    ->options(BvCashflow::ACTIVITIES),

                SelectFilter::make('category')
                    ->label('Pos Akun')
                    ->options(BvCashflow::optionsFor(null))
                    ->searchable(),

                SelectFilter::make('finance_account_id')
                    ->label('Akun Kas / Bank')
                    ->relationship('financeAccount', 'name'),

                Filter::make('periode')
                    ->label('Periode')
                    ->schema([
                        DatePicker::make('dari')->label('Dari Tanggal')->native(false),
                        DatePicker::make('sampai')->label('Sampai Tanggal')->native(false),
                    ])
                    ->query(fn(Builder $query, array $data) => $query
                        ->when($data['dari'] ?? null, fn($q, $d) => $q->whereDate('transaction_date', '>=', $d))
                        ->when($data['sampai'] ?? null, fn($q, $d) => $q->whereDate('transaction_date', '<=', $d))),
            ])
            ->recordActions([
                // Baris auto-posting adalah cerminan dokumen sumber — jejak audit
                // SAK tidak boleh diedit/dihapus dari sini.
                EditAction::make()
                    ->visible(fn($record) => ! $record->isAutoPosted()),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()
                        ->action(function ($records) {
                            $records->reject(fn($record) => $record->isAutoPosted())
                                ->each->delete();
                        }),
                ]),
            ])
            ->defaultSort('transaction_date', 'desc');
    }
}
