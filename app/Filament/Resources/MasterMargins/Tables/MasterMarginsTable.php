<?php

namespace App\Filament\Resources\MasterMargins\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Filters\TernaryFilter;

class MasterMarginsTable
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
                    ->label('Range Name')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),

                TextColumn::make('min_amount')
                    ->label('Min Amount')
                    ->money('IDR')
                    ->sortable(),

                TextColumn::make('max_amount')
                    ->label('Max Amount')
                    ->money('IDR')
                    ->sortable()
                    ->placeholder('Unlimited')
                    ->default('∞'),

                TextColumn::make('margin_percent')
                    ->label('Margin %')
                    ->suffix('%')
                    ->sortable()
                    ->alignCenter()
                    ->color('success')
                    ->weight('semibold'),

                IconColumn::make('is_active')
                    ->label('Active')
                    ->boolean()
                    ->sortable()
                    ->alignCenter(),

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
            ])
            ->recordActions([
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

