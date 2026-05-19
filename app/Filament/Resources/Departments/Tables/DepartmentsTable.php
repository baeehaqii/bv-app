<?php

namespace App\Filament\Resources\Departments\Tables;

use App\Enums\DivisionSyncType;
use App\Models\Division;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class DepartmentsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('division.name')
                    ->label('Divisi')
                    ->sortable()
                    ->badge()
                    ->color('primary'),

                TextColumn::make('name')
                    ->label('Nama Departemen')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('positions_count')
                    ->label('Posisi')
                    ->counts('positions')
                    ->sortable(),

                TextColumn::make('description')
                    ->label('Deskripsi')
                    ->limit(50)
                    ->toggleable(),

                TextColumn::make('sync_type')
                    ->label('Sync ke')
                    ->badge()
                    ->formatStateUsing(fn($state) => $state instanceof DivisionSyncType ? $state->label() : '—')
                    ->color(fn($state) => match ($state) {
                        DivisionSyncType::Sales => 'success',
                        DivisionSyncType::BusinessDirector => 'info',
                        default => 'gray',
                    }),

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
            ->filters([
                SelectFilter::make('division_id')
                    ->label('Divisi')
                    ->relationship('division', 'name'),
            ])
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('division_id');
    }
}
