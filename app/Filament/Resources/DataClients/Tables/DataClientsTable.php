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
                TextColumn::make('type')
                    ->badge()
                    ->color(fn(string $state): string => match ($state) {
                        'agency' => 'warning',
                        'direct' => 'success',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn(string $state): string => match ($state) {
                        'agency' => 'Agency',
                        'direct' => 'Direct Brand',
                        default => $state,
                    }),
                TextColumn::make('nama_brand')
                    ->searchable(),
                TextColumn::make('produk')
                    ->searchable(),
                TextColumn::make('category')
                    ->searchable(),
                TextColumn::make('priority')
                    ->searchable(),
                TextColumn::make('website')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('nama_pic')
                    ->label('PIC Name')
                    ->searchable(),
                TextColumn::make('role_pic')
                    ->label('PIC Role')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('email_pic')
                    ->label('PIC Email')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('status')
                    ->badge()
                    ->color(fn(string $state): string => match ($state) {
                        'Newest' => 'info',
                        'Number of Meeting' => 'primary',
                        'Brief' => 'warning',
                        'Waiting Feedback' => 'danger',
                        'Not Available' => 'gray',
                        default => 'gray',
                    })
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
