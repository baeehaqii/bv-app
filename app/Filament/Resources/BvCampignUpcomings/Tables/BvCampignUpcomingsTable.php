<?php

namespace App\Filament\Resources\BvCampignUpcomings\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use App\Models\BvCampignUpcoming;


class BvCampignUpcomingsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                ImageColumn::make('campaign_image')
                    ->label('')
                    ->circular()
                    ->defaultImageUrl('https://placehold.co/100'),
                TextColumn::make('campaign_name')
                    ->label('CAMPAIGN NAME')
                    ->searchable()
                    ->weight('bold')
                    ->description(fn(BvCampignUpcoming $record) => $record->description ? \Illuminate\Support\Str::limit($record->description, 30) : null),
                TextColumn::make('status')
                    ->label('STATUS')
                    ->badge()
                    ->color(fn(string $state): string => match ($state) {
                        'forecasted' => 'success',
                        'ongoing' => 'warning',
                        'completed' => 'info',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn(string $state): string => strtoupper($state)),
                TextColumn::make('budget_allocated')
                    ->label('BUDGET ALLOCATED')
                    ->money('IDR')
                    ->sortable(),
                TextColumn::make('pot_cpv')
                    ->label('POT. CPV')
                    ->money('IDR')
                    ->sortable(),
                TextColumn::make('pot_cpe')
                    ->label('POT. CPE')
                    ->money('IDR')
                    ->sortable(),
                TextColumn::make('pot_views')
                    ->label('POT. VIEWS')
                    ->numeric(locale: 'id') // Indonesian locale for thousand separators
                    ->sortable(),
            ])
            ->filters([
                //
            ])
            ->actions([
                ViewAction::make()
                    ->label('Detail')
                    ->button()
                    ->color('primary'),
                EditAction::make()
                    ->label('Edit Campaign')
                    ->link(),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
