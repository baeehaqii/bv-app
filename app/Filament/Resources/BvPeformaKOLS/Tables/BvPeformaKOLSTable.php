<?php

namespace App\Filament\Resources\BvPeformaKOLS\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class BvPeformaKOLSTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('pic')
                    ->label('PIC')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('username')
                    ->label('Username')
                    ->searchable()
                    ->sortable()
                    ->copyable(),
                TextColumn::make('tanggal_posting')
                    ->label('Tanggal Posting')
                    ->date('d M Y')
                    ->sortable(),

                // TikTok Stats
                TextColumn::make('tiktok_views')
                    ->label('TikTok Views')
                    ->numeric()
                    ->sortable()
                    ->toggleable()
                    ->summarize([
                        \Filament\Tables\Columns\Summarizers\Sum::make()
                            ->label('Total'),
                    ]),
                TextColumn::make('tiktok_likes')
                    ->label('TikTok Likes')
                    ->numeric()
                    ->sortable()
                    ->toggleable(),
                TextColumn::make('tiktok_comments')
                    ->label('TikTok Comments')
                    ->numeric()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('tiktok_saves')
                    ->label('TikTok Saves')
                    ->numeric()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('tiktok_shares')
                    ->label('TikTok Shares')
                    ->numeric()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('tiktok_total_engagement')
                    ->label('TikTok Engagement')
                    ->numeric()
                    ->sortable()
                    ->badge()
                    ->color('success'),

                // Instagram Stats
                TextColumn::make('instagram_views')
                    ->label('IG Views')
                    ->numeric()
                    ->sortable()
                    ->toggleable()
                    ->summarize([
                        \Filament\Tables\Columns\Summarizers\Sum::make()
                            ->label('Total'),
                    ]),
                TextColumn::make('instagram_likes')
                    ->label('IG Likes')
                    ->numeric()
                    ->sortable()
                    ->toggleable(),
                TextColumn::make('instagram_comments')
                    ->label('IG Comments')
                    ->numeric()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('instagram_saves')
                    ->label('IG Saves')
                    ->numeric()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('instagram_shares')
                    ->label('IG Shares')
                    ->numeric()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('instagram_total_engagement')
                    ->label('IG Engagement')
                    ->numeric()
                    ->sortable()
                    ->badge()
                    ->color('info'),

                // Links (hidden by default)
                TextColumn::make('link_posting_tiktok')
                    ->label('Link TikTok')
                    ->limit(30)
                    ->url(fn($state) => $state)
                    ->openUrlInNewTab()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('link_posting_instagram')
                    ->label('Link Instagram')
                    ->limit(30)
                    ->url(fn($state) => $state)
                    ->openUrlInNewTab()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('created_at')
                    ->label('Dibuat')
                    ->dateTime('d M Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('tanggal_posting', 'desc')
            ->filters([
                Filter::make('has_tiktok')
                    ->label('Ada TikTok')
                    ->query(fn(Builder $query) => $query->whereNotNull('link_posting_tiktok')),
                Filter::make('has_instagram')
                    ->label('Ada Instagram')
                    ->query(fn(Builder $query) => $query->whereNotNull('link_posting_instagram')),
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
