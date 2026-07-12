<?php

namespace App\Filament\Resources\MasterSows\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;

class MasterSowsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('sort_order')
                    ->label('#')
                    ->sortable()
                    ->width(50)
                    ->alignCenter(),

                TextColumn::make('name')
                    ->label('Nama SOW')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),

                TextColumn::make('channel')
                    ->label('Platform')
                    ->badge()
                    ->color(fn($state) => match ($state) {
                        'Instagram'        => 'pink',
                        'Tiktok'           => 'gray',
                        'Youtube Channels',
                        'Youtube Shorts'   => 'danger',
                        'X'                => 'info',
                        'Threads'          => 'primary',
                        default            => 'gray',
                    })
                    ->placeholder('-')
                    ->sortable(),

                TextColumn::make('code')
                    ->label('Kode')
                    ->badge()
                    ->color('gray')
                    ->placeholder('-'),

                IconColumn::make('is_custom')
                    ->label('Custom')
                    ->boolean()
                    ->trueIcon('heroicon-o-pencil-square')
                    ->falseIcon('heroicon-o-minus')
                    ->trueColor('warning')
                    ->falseColor('gray')
                    ->alignCenter(),

                IconColumn::make('is_active')
                    ->label('Aktif')
                    ->boolean()
                    ->sortable()
                    ->alignCenter(),
            ])
            ->defaultSort('sort_order')
            ->filters([
                SelectFilter::make('channel')
                    ->label('Platform')
                    ->options(\App\Filament\Resources\DataKols\Schemas\DataKolForm::$channelOptions),
                TernaryFilter::make('is_active')->label('Status Aktif'),
                TernaryFilter::make('is_custom')->label('Tipe Custom'),
            ])
            ->recordAction(EditAction::class)
            ->bulkActions([
                BulkActionGroup::make([DeleteBulkAction::make()]),
            ]);
    }
}
