<?php

namespace App\Filament\Resources\MasterPphs\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
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

                TernaryFilter::make('include_ppn')
                    ->label('PPN Status')
                    ->placeholder('All')
                    ->trueLabel('With PPN')
                    ->falseLabel('Without PPN'),
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

