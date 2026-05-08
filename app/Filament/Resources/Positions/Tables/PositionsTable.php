<?php

namespace App\Filament\Resources\Positions\Tables;

use App\Models\Position;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class PositionsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('department.division.name')
                    ->label('Divisi')
                    ->sortable()
                    ->badge()
                    ->color('primary'),

                TextColumn::make('department.name')
                    ->label('Departemen')
                    ->sortable()
                    ->badge()
                    ->color('gray'),

                TextColumn::make('name')
                    ->label('Nama Jabatan')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('level')
                    ->label('Level')
                    ->formatStateUsing(fn (string $state): string => Position::LEVELS[$state] ?? $state)
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'director' => 'danger',
                        'manager'  => 'warning',
                        'senior'   => 'info',
                        'staff'    => 'success',
                        'junior'   => 'gray',
                        'intern'   => 'gray',
                        default    => 'gray',
                    })
                    ->sortable(),

                TextColumn::make('employees_count')
                    ->label('Karyawan')
                    ->counts('employees')
                    ->sortable(),

                IconColumn::make('is_active')
                    ->label('Aktif')
                    ->boolean()
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('department.division_id')
                    ->label('Divisi')
                    ->relationship('department.division', 'name'),

                SelectFilter::make('department_id')
                    ->label('Departemen')
                    ->relationship('department', 'name'),

                SelectFilter::make('level')
                    ->label('Level')
                    ->options(Position::LEVELS),
            ])
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('department_id');
    }
}
