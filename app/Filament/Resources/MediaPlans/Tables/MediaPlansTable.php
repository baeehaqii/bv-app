<?php

namespace App\Filament\Resources\MediaPlans\Tables;

use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Filters\SelectFilter;

class MediaPlansTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('brand')
                    ->label('Brand')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('quotation_number')
                    ->label('Quotation #')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('campaign_name')
                    ->label('Campaign')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('username')
                    ->label('KOL Username')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('channel')
                    ->label('Channel')
                    ->badge()
                    ->color(fn($state) => match ($state) {
                        'Instagram' => 'blue',
                        'Tiktok' => 'black',
                        default => 'gray',
                    }),

                TextColumn::make('categories')
                    ->label('Category'),

                TextColumn::make('tier')
                    ->label('Tier')
                    ->badge()
                    ->color(fn($state) => match ($state) {
                        'Mega' => 'success',
                        'Macro' => 'warning',
                        'Micro' => 'info',
                        'Nano' => 'secondary',
                        default => 'gray',
                    }),

                TextColumn::make('followers')
                    ->label('Followers')
                    ->numeric()
                    ->formatStateUsing(fn($state) => number_format($state))
                    ->sortable()
                    ->alignRight(),

                TextColumn::make('er')
                    ->label('ER %')
                    ->numeric()
                    ->suffix('%')
                    ->sortable()
                    ->alignRight(),

                TextColumn::make('engagement')
                    ->label('Engagement')
                    ->numeric()
                    ->formatStateUsing(fn($state) => number_format($state))
                    ->sortable()
                    ->alignRight(),

                TextColumn::make('rate')
                    ->label('Rate')
                    ->money('IDR', locale: 'id')
                    ->sortable()
                    ->alignRight(),

                TextColumn::make('cpi_cpv')
                    ->label('CPI/CPV')
                    ->numeric()
                    ->formatStateUsing(fn($state) => $state ? 'Rp ' . number_format($state) : '-')
                    ->alignRight(),

                TextColumn::make('cpe')
                    ->label('CPE')
                    ->numeric()
                    ->formatStateUsing(fn($state) => $state ? 'Rp ' . number_format($state) : '-')
                    ->alignRight(),

                TextColumn::make('date')
                    ->label('Date')
                    ->date('d M Y')
                    ->sortable(),

                TextColumn::make('created_at')
                    ->label('Created At')
                    ->dateTime('d M Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('channel')
                    ->options([
                        'Instagram' => 'Instagram',
                        'Tiktok' => 'Tiktok',
                    ]),

                SelectFilter::make('tier')
                    ->options([
                        'Mega' => 'Mega',
                        'Macro' => 'Macro',
                        'Micro' => 'Micro',
                        'Nano' => 'Nano',
                    ]),

                SelectFilter::make('categories')
                    ->options(function () {
                        return \App\Models\MediaPlan::distinct()
                            ->pluck('categories', 'categories')
                            ->toArray();
                    }),
            ])
            ->defaultSort('created_at', 'desc');
    }
}
