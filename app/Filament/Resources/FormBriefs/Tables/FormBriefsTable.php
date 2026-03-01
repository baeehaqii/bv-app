<?php

namespace App\Filament\Resources\FormBriefs\Tables;

use App\Models\FormBrief;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class FormBriefsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('title')
                    ->label('Judul Brief')
                    ->searchable()
                    ->sortable()
                    ->weight('bold')
                    ->limit(40),

                TextColumn::make('client.nama_brand')
                    ->label('Client')
                    ->searchable()
                    ->sortable()
                    ->placeholder('-'),

                TextColumn::make('campaign.campaign_name')
                    ->label('Campaign')
                    ->searchable()
                    ->sortable()
                    ->placeholder('-')
                    ->limit(30),

                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->color(fn(string $state): string => match ($state) {
                        'draft' => 'gray',
                        'submitted' => 'info',
                        'reviewed' => 'warning',
                        'approved' => 'success',
                        'revision' => 'danger',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn(string $state): string => match ($state) {
                        'draft' => 'Draft',
                        'submitted' => 'Submitted',
                        'reviewed' => 'Reviewed',
                        'approved' => 'Approved',
                        'revision' => 'Perlu Revisi',
                        default => ucfirst($state),
                    })
                    ->sortable(),

                TextColumn::make('content_deadline')
                    ->label('Deadline')
                    ->date('d M Y')
                    ->sortable()
                    ->placeholder('-'),

                TextColumn::make('budget')
                    ->label('Budget')
                    ->money('IDR')
                    ->sortable()
                    ->placeholder('-'),

                TextColumn::make('submitted_by_name')
                    ->label('Submitted By')
                    ->placeholder('-')
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('created_at')
                    ->label('Dibuat')
                    ->dateTime('d M Y')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->label('Status')
                    ->options([
                        'draft' => 'Draft',
                        'submitted' => 'Submitted',
                        'reviewed' => 'Reviewed',
                        'approved' => 'Approved',
                        'revision' => 'Perlu Revisi',
                    ]),

                SelectFilter::make('client_id')
                    ->label('Client')
                    ->relationship('client', 'nama_brand')
                    ->searchable()
                    ->preload(),
            ])
            ->actions([
                ViewAction::make()
                    ->label('Detail')
                    ->button()
                    ->color('primary'),
                EditAction::make()
                    ->label('Edit')
                    ->link(),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
    }
}
