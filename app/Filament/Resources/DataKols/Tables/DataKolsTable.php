<?php

namespace App\Filament\Resources\DataKols\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class DataKolsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('username')
                    ->label('Username')
                    ->searchable()
                    ->sortable()
                    ->copyable()
                    ->icon('heroicon-o-at-symbol'),

                TextColumn::make('channel')
                    ->label('Channel')
                    ->badge()
                    ->color(fn(string $state): string => match ($state) {
                        'Instagram' => 'danger',
                        'TikTok' => 'info',
                        'YouTube' => 'danger',
                        'Twitter' => 'info',
                        default => 'gray',
                    })
                    ->searchable()
                    ->sortable(),

                TextColumn::make('followers')
                    ->label('Followers')
                    ->numeric()
                    ->sortable()
                    ->formatStateUsing(fn($state) => number_format($state)),

                BadgeColumn::make('tier')
                    ->label('Tier')
                    ->colors([
                        'success' => 'Mega',
                        'warning' => 'Macro',
                        'primary' => 'Micro',
                        'info' => 'Nano',
                        'gray' => 'Mini',
                    ])
                    ->icons([
                        'heroicon-o-star' => 'Mega',
                        'heroicon-o-fire' => 'Macro',
                        'heroicon-o-sparkles' => 'Micro',
                        'heroicon-o-light-bulb' => 'Nano',
                        'heroicon-o-user' => 'Mini',
                    ])
                    ->searchable()
                    ->sortable(),

                TextColumn::make('engagement_rate')
                    ->label('ER %')
                    ->suffix('%')
                    ->sortable()
                    ->badge()
                    ->color(fn($state): string => match (true) {
                        $state >= 5 => 'success',      // Excellent
                        $state >= 3 => 'warning',      // Good
                        $state >= 1 => 'primary',      // Average
                        default => 'gray',             // Low
                    })
                    ->formatStateUsing(fn($state) => number_format($state, 2))
                    ->tooltip(fn($state): string => match (true) {
                        $state >= 5 => 'Excellent engagement!',
                        $state >= 3 => 'Good engagement',
                        $state >= 1 => 'Average engagement',
                        default => 'Low engagement',
                    }),

                TextColumn::make('engagements')
                    ->label('Total Engagements')
                    ->numeric()
                    ->sortable()
                    ->formatStateUsing(fn($state) => number_format($state))
                    ->toggleable(),

                TextColumn::make('impressions')
                    ->label('Avg Impressions')
                    ->numeric()
                    ->sortable()
                    ->formatStateUsing(fn($state) => number_format($state))
                    ->toggleable(),

                TextColumn::make('category')
                    ->label('Category')
                    ->badge()
                    ->formatStateUsing(fn($state) => is_array($state) ? implode(', ', $state) : ($state ?? '-'))
                    ->searchable()
                    ->sortable()
                    ->toggleable(),

                TextColumn::make('contact')
                    ->label('Contact')
                    ->searchable()
                    ->copyable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('terakhir_update')
                    ->label('Last Update')
                    ->date('d M Y')
                    ->sortable()
                    ->toggleable(),

                TextColumn::make('created_at')
                    ->label('Created')
                    ->dateTime('d M Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('updated_at')
                    ->label('Updated')
                    ->dateTime('d M Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('tier')
                    ->label('Filter by Tier')
                    ->options([
                        'Mega' => 'Mega (1M+)',
                        'Macro' => 'Macro (100K-999K)',
                        'Micro' => 'Micro (10K-99K)',
                        'Nano' => 'Nano (1K-9K)',
                        'Mini' => 'Mini (<1K)',
                    ])
                    ->multiple(),

                SelectFilter::make('channel')
                    ->label('Filter by Channel')
                    ->options([
                        'Instagram' => 'Instagram',
                        'TikTok' => 'TikTok',
                        'YouTube' => 'YouTube',
                        'Twitter' => 'Twitter',
                    ])
                    ->multiple(),
            ])
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
    }
}
