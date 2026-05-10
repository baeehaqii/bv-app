<?php

namespace App\Filament\Resources\Divisions\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\Action;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class DivisionsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label('Nama Divisi')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('departments_count')
                    ->label('Dept')
                    ->counts('departments')
                    ->sortable()
                    ->badge()
                    ->color('primary')
                    ->action(
                        Action::make('viewDepartments')
                            ->modalHeading(fn ($record) => 'Departemen — ' . $record->name)
                            ->modalContent(fn ($record) => view(
                                'filament.modals.division-departments',
                                ['division' => $record->load('departments.positions')]
                            ))
                            ->modalWidth('lg')
                            ->modalSubmitAction(false)
                            ->modalCancelActionLabel('Tutup')
                    ),

                TextColumn::make('description')
                    ->label('Deskripsi')
                    ->limit(50)
                    ->toggleable(),

                IconColumn::make('is_active')
                    ->label('Aktif')
                    ->boolean()
                    ->sortable(),

                TextColumn::make('created_at')
                    ->label('Dibuat')
                    ->dateTime('d M Y')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([])
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('name');
    }
}
