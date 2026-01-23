<?php

namespace App\Filament\Resources\BvCampigns\Tables;

use App\Models\BvCampign;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Support\Enums\FontWeight;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class BvCampignsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->query(BvCampign::query()->with(['client', 'kols']))
            ->columns([
                ImageColumn::make('campaign_image')
                    ->label('')
                    ->circular()
                    ->size(45)
                    ->defaultImageUrl(fn($record) => 'https://ui-avatars.com/api/?name=' . urlencode($record->campaign_name ?? 'C') . '&color=7c3aed&background=e9d5ff'),

                TextColumn::make('campaign_name')
                    ->label('Campaign')
                    ->weight(FontWeight::SemiBold)
                    ->searchable()
                    ->sortable()
                    ->description(fn($record) => $record->client?->nama_brand ?? '-'),

                TextColumn::make('client.nama_brand')
                    ->label('Brand')
                    ->searchable()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('start_date')
                    ->label('Timeline')
                    ->formatStateUsing(
                        fn($state, $record) =>
                        ($record->start_date?->format('d M') ?? '-') . ' - ' . ($record->end_date?->format('d M Y') ?? '-')
                    )
                    ->sortable(),

                TextColumn::make('media_platforms')
                    ->label('Platforms')
                    ->badge()
                    ->separator(',')
                    ->formatStateUsing(fn($state) => is_array($state) ? implode(', ', array_map('ucfirst', $state)) : $state)
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('kols_count')
                    ->label('KOLs')
                    ->counts('kols')
                    ->badge()
                    ->color('info')
                    ->suffix(' creators'),

                TextColumn::make('total_cost')
                    ->label('Total Cost')
                    ->money('IDR')
                    ->sortable()
                    ->color('success'),

                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->color(fn($state) => match ($state) {
                        'draft' => 'gray',
                        'upcoming' => 'info',
                        'ongoing' => 'warning',
                        'completed' => 'success',
                        'cancelled' => 'danger',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn($state) => ucfirst($state ?? 'draft')),

                TextColumn::make('progress_percentage')
                    ->label('Progress')
                    ->suffix('%')
                    ->badge()
                    ->color(fn($state) => match (true) {
                        $state >= 100 => 'success',
                        $state >= 50 => 'warning',
                        default => 'gray',
                    })
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('created_at')
                    ->label('Created')
                    ->since()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->label('Status')
                    ->options([
                        'draft' => 'Draft',
                        'upcoming' => 'Upcoming',
                        'ongoing' => 'Ongoing',
                        'completed' => 'Completed',
                        'cancelled' => 'Cancelled',
                    ]),

                SelectFilter::make('client_id')
                    ->label('Brand')
                    ->relationship('client', 'nama_brand')
                    ->searchable()
                    ->preload(),

                SelectFilter::make('campaign_type')
                    ->label('Campaign Type')
                    ->options([
                        'regular' => 'Regular',
                        'advance' => 'Advance',
                    ]),
            ])
            ->recordActions([
                Action::make('kol_performance')
                    ->label('KOL Performance')
                    ->icon('heroicon-o-chart-bar')
                    ->color('info')
                    ->url(fn($record) => \App\Filament\Resources\BvCampigns\BvCampignResource::getUrl('kol-performance', ['record' => $record])),
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
