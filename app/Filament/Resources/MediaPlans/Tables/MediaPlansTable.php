<?php

namespace App\Filament\Resources\MediaPlans\Tables;

use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Actions\EditAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;

class MediaPlansTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('quotation_number')
                    ->label('Quotation #')
                    ->searchable()
                    ->sortable()
                    ->copyable()
                    ->copyMessage('Quotation number copied'),

                TextColumn::make('brand')
                    ->label('Brand')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),

                TextColumn::make('campaign_name')
                    ->label('Campaign')
                    ->searchable()
                    ->sortable()
                    ->limit(30),

                TextColumn::make('kols_count')
                    ->label('Total KOLs')
                    ->counts('kols')
                    ->badge()
                    ->color('gray')
                    ->alignCenter(),

                // Selected KOLs count
                TextColumn::make('selected_kols_count')
                    ->label('Selected')
                    ->state(fn($record) => $record->kols()->where('is_selected', true)->count())
                    ->badge()
                    ->color('success')
                    ->alignCenter(),

                TextColumn::make('kols.name')
                    ->label('KOL(s)')
                    ->listWithLineBreaks()
                    ->limitList(3)
                    ->expandableLimitedList()
                    ->searchable(),

                TextColumn::make('kols.channel')
                    ->label('Channel(s)')
                    ->badge()
                    ->separator(',')
                    ->color(fn($state) => match ($state) {
                        'Instagram' => 'pink',
                        'Tiktok' => 'gray',
                        'Youtube Channels' => 'danger',
                        'Youtube Shorts' => 'warning',
                        default => 'gray',
                    })
                    ->toggleable(),

                // Summary from Internal Budget
                TextColumn::make('internalBudget.total_rounded')
                    ->label('Total Budget')
                    ->money('IDR', locale: 'id')
                    ->sortable()
                    ->alignRight()
                    ->weight('bold')
                    ->color('success'),

                TextColumn::make('internalBudget.average_margin_percent')
                    ->label('Avg Margin')
                    ->suffix('%')
                    ->sortable()
                    ->alignCenter()
                    ->color(fn($state) => ($state ?? 0) < 30 ? 'danger' : 'success')
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('internalBudget.status')
                    ->label('Budget Status')
                    ->badge()
                    ->color(fn(string $state): string => match ($state) {
                        'draft' => 'gray',
                        'pending' => 'warning',
                        'approved' => 'success',
                        'rejected' => 'danger',
                        default => 'gray',
                    })
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('created_at')
                    ->label('Created At')
                    ->dateTime('d M Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('channel')
                    ->label('Filter by Channel')
                    ->options([
                        'Instagram' => 'Instagram',
                        'Tiktok' => 'Tiktok',
                        'Youtube Channels' => 'Youtube Channels',
                        'Youtube Shorts' => 'Youtube Shorts',
                    ])
                    ->query(function ($query, $state) {
                        if ($state['value']) {
                            $query->whereHas('kols', function ($q) use ($state) {
                                $q->where('channel', $state['value']);
                            });
                        }
                    }),

                SelectFilter::make('budget_status')
                    ->label('Budget Status')
                    ->options([
                        'draft' => 'Draft',
                        'pending' => 'Pending',
                        'approved' => 'Approved',
                        'rejected' => 'Rejected',
                    ])
                    ->query(function ($query, $state) {
                        if ($state['value']) {
                            $query->whereHas('internalBudget', function ($q) use ($state) {
                                $q->where('status', $state['value']);
                            });
                        }
                    }),
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
