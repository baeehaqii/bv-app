<?php

namespace App\Filament\Resources\InternalBudgets\Tables;

use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;

class InternalBudgetsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('mediaPlan.campaign_name')
                    ->label('Campaign')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('mediaPlan.username')
                    ->label('KOL')
                    ->searchable(),

                TextColumn::make('scopeofwork_item')
                    ->label('Scope of Work')
                    ->searchable(),

                TextColumn::make('qty')
                    ->label('Qty')
                    ->numeric()
                    ->alignCenter(),

                TextColumn::make('rate')
                    ->label('Rate (Base)')
                    ->money('IDR', locale: 'id')
                    ->sortable()
                    ->alignRight(),

                TextColumn::make('subtotal')
                    ->label('Subtotal Rate')
                    ->money('IDR', locale: 'id')
                    ->alignRight(),

                TextColumn::make('mu_pph')
                    ->label('MU PPh')
                    ->money('IDR', locale: 'id')
                    ->sortable()
                    ->alignRight(),

                TextColumn::make('mu_target')
                    ->label('MU Target')
                    ->money('IDR', locale: 'id')
                    ->alignRight(),

                TextColumn::make('published_rate')
                    ->label('Published Rate')
                    ->money('IDR', locale: 'id')
                    ->alignRight(),

                TextColumn::make('rounded')
                    ->label('Rounded')
                    ->money('IDR', locale: 'id')
                    ->alignRight(),

                TextColumn::make('margin_percent')
                    ->label('Margin %')
                    ->formatStateUsing(fn($state) => $state ? number_format($state, 2) . '%' : '-')
                    ->color(fn($state) => $state && $state < 30 ? 'danger' : 'success')
                    ->sortable()
                    ->alignCenter(),

                TextColumn::make('created_at')
                    ->label('Created')
                    ->dateTime('d M Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('created_at', 'desc');
    }
}
