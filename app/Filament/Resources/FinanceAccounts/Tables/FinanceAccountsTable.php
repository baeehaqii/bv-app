<?php

namespace App\Filament\Resources\FinanceAccounts\Tables;

use App\Models\FinanceAccount;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Filament\Support\Enums\FontWeight;

class FinanceAccountsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label('Nama Akun')
                    ->weight(FontWeight::SemiBold)
                    ->searchable()
                    ->sortable(),

                TextColumn::make('type')
                    ->label('Jenis')
                    ->badge()
                    ->color('gray')
                    ->formatStateUsing(fn($state) => FinanceAccount::TYPES[$state] ?? $state),

                TextColumn::make('bank_name')->label('Bank')->placeholder('—')->toggleable(),
                TextColumn::make('account_number')->label('No. Rekening')->placeholder('—')->searchable()->toggleable(),

                TextColumn::make('opening_balance')
                    ->label('Saldo Awal')
                    ->money('IDR', locale: 'id')
                    ->toggleable(isToggledHiddenByDefault: true),

                // Saldo dihitung dari arus kas — tidak bisa disortir di SQL,
                // dan itu memang trade-off yang diambil supaya tidak pernah basi.
                TextColumn::make('balance')
                    ->label('Saldo Berjalan')
                    ->money('IDR', locale: 'id')
                    ->weight(FontWeight::Bold)
                    ->color(fn($record) => $record->balance < 0 ? 'danger' : 'success'),

                IconColumn::make('is_default')
                    ->label('Default')
                    ->boolean(),

                IconColumn::make('is_active')
                    ->label('Aktif')
                    ->boolean(),
            ])
            ->filters([
                SelectFilter::make('type')->label('Jenis')->options(FinanceAccount::TYPES),
                SelectFilter::make('is_active')
                    ->label('Status')
                    ->options([1 => 'Aktif', 0 => 'Non-aktif']),
            ])
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('name')
            ->emptyStateHeading('Belum ada akun kas / bank')
            ->emptyStateDescription('Tambahkan minimal satu akun agar saldo dan pencatatan otomatis punya tujuan.')
            ->emptyStateIcon('heroicon-o-wallet');
    }
}
