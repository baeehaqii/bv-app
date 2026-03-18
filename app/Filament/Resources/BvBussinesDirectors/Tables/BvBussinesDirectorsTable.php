<?php

namespace App\Filament\Resources\BvBussinesDirectors\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class BvBussinesDirectorsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('nama_lengkap')
                    ->label('Nama Lengkap')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('alamat_email')
                    ->label('Alamat Email')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('no_wa')
                    ->label('No WA')
                    ->searchable(),

                TextColumn::make('tanggal_gabung')
                    ->label('Tanggal Gabung')
                    ->date('d M Y')
                    ->sortable(),

                TextColumn::make('sales_lists_count')
                    ->label('Jumlah Sales')
                    ->counts('salesLists'),

                BadgeColumn::make('status')
                    ->label('Status')
                    ->formatStateUsing(fn(string $state): string => $state === 'aktif' ? 'Aktif' : 'Tidak Aktif')
                    ->colors([
                        'success' => 'aktif',
                        'danger' => 'tidak_aktif',
                    ]),

                TextColumn::make('updated_at')
                    ->dateTime('d M Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
