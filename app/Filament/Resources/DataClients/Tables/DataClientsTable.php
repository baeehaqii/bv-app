<?php

namespace App\Filament\Resources\DataClients\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class DataClientsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('nama_brand')
                    ->searchable(),
                TextColumn::make('produk')
                    ->searchable(),
                TextColumn::make('category')
                    ->searchable(),
                TextColumn::make('priority')
                    ->searchable(),
                TextColumn::make('website')
                    ->searchable(),
                TextColumn::make('nama_pic')
                    ->searchable(),
                TextColumn::make('role_pic')
                    ->searchable(),
                TextColumn::make('email_pic')
                    ->searchable(),
                TextColumn::make('status')
                    ->searchable(),
                TextColumn::make('date_outreach')
                    ->date()
                    ->sortable(),
                TextColumn::make('date_follow_up')
                    ->date()
                    ->sortable(),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
                    ->dateTime()
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
