<?php

namespace App\Filament\Resources\MasterPphs\Tables;

use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Notifications\Notification;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Filters\TernaryFilter;

class MasterPphsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('order')
                    ->label('#')
                    ->sortable()
                    ->width(60),

                TextColumn::make('name')
                    ->label('Name')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),

                TextColumn::make('entity_type')
                    ->label('Entity Type')
                    ->badge()
                    ->color(fn(string $state): string => match ($state) {
                        'Pribadi' => 'success',
                        'PT' => 'info',
                        'CV' => 'warning',
                        default => 'gray',
                    })
                    ->sortable(),

                TextColumn::make('coefficient')
                    ->label('Coefficient')
                    ->sortable()
                    ->alignCenter()
                    ->weight('semibold'),

                IconColumn::make('include_ppn')
                    ->label('PPN')
                    ->boolean()
                    ->sortable()
                    ->alignCenter(),

                TextColumn::make('ppn_percent')
                    ->label('PPN %')
                    ->suffix('%')
                    ->sortable()
                    ->alignCenter()
                    ->placeholder('-')
                    ->toggleable(),

                TextColumn::make('calculated_coefficient')
                    ->label('Final Coefficient')
                    ->getStateUsing(fn($record) => $record->getCalculatedCoefficient())
                    ->alignCenter()
                    ->color('primary')
                    ->weight('bold')
                    ->description('Including PPN if applicable'),

                IconColumn::make('is_active')
                    ->label('Aktif')
                    ->boolean()
                    ->sortable()
                    ->alignCenter(),

                IconColumn::make('is_default')
                    ->label('Default')
                    ->boolean()
                    ->trueIcon('heroicon-s-star')
                    ->falseIcon('heroicon-o-minus-small')
                    ->trueColor('warning')
                    ->falseColor('gray')
                    ->sortable()
                    ->alignCenter()
                    ->tooltip('Tipe pajak yang dipakai KOL baru di Media Plan Internal'),

                TextColumn::make('created_at')
                    ->label('Created')
                    ->dateTime('d M Y, H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('updated_at')
                    ->label('Updated')
                    ->dateTime('d M Y, H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                TernaryFilter::make('is_active')
                    ->label('Active Status')
                    ->placeholder('All')
                    ->trueLabel('Active only')
                    ->falseLabel('Inactive only'),

                TernaryFilter::make('include_ppn')
                    ->label('PPN Status')
                    ->placeholder('All')
                    ->trueLabel('With PPN')
                    ->falseLabel('Without PPN'),
            ])
            ->recordActions([
                Action::make('jadikan_default')
                    ->label('Jadikan default')
                    ->icon('heroicon-o-star')
                    ->color('warning')
                    ->visible(fn($record) => ! $record->is_default)
                    ->requiresConfirmation()
                    ->modalDescription('KOL baru di Media Plan Internal akan memakai tipe pajak ini. Baris budget yang sudah ada tidak berubah.')
                    ->action(function ($record) {
                        // booted() di MasterPph yang melepas default lama.
                        $record->update(['is_default' => true, 'is_active' => true]);

                        Notification::make()
                            ->success()
                            ->title("\"{$record->name}\" jadi tipe pajak default")
                            ->send();
                    }),

                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('order', 'asc');
    }
}

