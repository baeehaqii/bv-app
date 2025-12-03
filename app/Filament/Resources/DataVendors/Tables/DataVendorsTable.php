<?php

namespace App\Filament\Resources\DataVendors\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter as FiltersSelectFilter;
use Filament\Tables\Table;

class DataVendorsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('nama_vendor')
                    ->label('Nama Vendor')
                    ->searchable()
                    ->sortable()
                    ->weight('font-bold'),
                TextColumn::make('email_vendor')
                    ->label('Email Vendor')
                    ->searchable()
                    ->icon('heroicon-m-envelope'),
                TextColumn::make('nama_pic')
                    ->label('Nama PIC')
                    ->searchable(),
                TextColumn::make('role_pic')
                    ->label('Role PIC')
                    ->searchable(),
                TextColumn::make('email_pic')
                    ->label('Email PIC')
                    ->searchable()
                    ->icon('heroicon-m-envelope'),
                TextColumn::make('tanggal_registrasi')
                    ->label('Tgl Registrasi')
                    ->date('d/m/Y')
                    ->sortable(),
                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->color(fn(string $state): string => match ($state) {
                        'Active' => 'success',
                        'Inactive' => 'gray',
                        'Pending' => 'warning',
                        'Blocked' => 'danger',
                        default => 'gray',
                    })
                    ->searchable()
                    ->sortable(),
            ])
            ->filters([
                FiltersSelectFilter::make('status')
                    ->label('Status')
                    ->options([
                        'Active' => 'Active',
                        'Inactive' => 'Inactive',
                        'Pending' => 'Pending',
                        'Blocked' => 'Blocked',
                    ]),
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
