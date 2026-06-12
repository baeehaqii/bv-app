<?php

namespace App\Filament\Resources\InternalBudgets\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\Filter;

class InternalBudgetsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('mediaPlan.quotation_number')
                    ->label('Quotation #')
                    ->searchable()
                    ->sortable()
                    ->copyable(),

                TextColumn::make('mediaPlan.campaign_name')
                    ->label('Campaign')
                    ->searchable()
                    ->sortable()
                    ->limit(25),

                TextColumn::make('mediaPlan.brand')
                    ->label('Brand')
                    ->searchable()
                    ->sortable()
                    ->toggleable(),

                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->color(fn(string $state): string => match ($state) {
                        'draft' => 'gray',
                        'pending' => 'warning',
                        'approved' => 'success',
                        'rejected' => 'danger',
                    }),

                TextColumn::make('items_count')
                    ->label('Items')
                    ->counts('items')
                    ->badge()
                    ->color('info')
                    ->alignCenter(),

                TextColumn::make('total_mu_pph')
                    ->label('Total Cost')
                    ->numeric(decimalPlaces: 0, thousandsSeparator: ',')
                    ->prefix('IDR ')
                    ->sortable()
                    ->color('danger')
                    ->description('MU PPh'),

                TextColumn::make('total_rounded')
                    ->label('Total Budget')
                    ->numeric(decimalPlaces: 0, thousandsSeparator: ',')
                    ->prefix('IDR ')
                    ->sortable()
                    ->weight('bold')
                    ->color('success')
                    ->description('Client Price'),

                // Profit & Avg Margin disembunyikan di Media Plan External (data internal saja).

                TextColumn::make('warnings')
                    ->label('⚠️')
                    ->state(fn($record) => $record->warnings ? '⚠️' : '✅')
                    ->alignCenter()
                    ->tooltip(fn($record) => $record->warnings ?? 'No warnings'),

                TextColumn::make('created_at')
                    ->label('Created')
                    ->dateTime('d M Y')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->options([
                        'draft' => 'Draft',
                        'pending' => 'Pending',
                        'approved' => 'Approved',
                        'rejected' => 'Rejected',
                    ]),

                Filter::make('has_warnings')
                    ->label('Has Warnings')
                    ->query(fn($query) => $query->whereNotNull('warnings')),
            ])
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('created_at', 'desc')
            ->striped();
    }
}
