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
                    ->formatStateUsing(fn($state, $record) => 'Brief — ' . ($record->campaign_name ?: preg_replace('/^KOL Needs\s*[—-]\s*/u', '', $state)))
                    ->limit(50),

                TextColumn::make('brand')
                    ->label('Client')
                    ->searchable()
                    ->sortable()
                    ->placeholder('-'),

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

                TextColumn::make('deadline')
                    ->label('Deadline')
                    ->placeholder('-'),

                TextColumn::make('budget_main_kol')
                    ->label('Budget Main KOL')
                    ->formatStateUsing(fn($state) => $state ? 'Rp ' . number_format((float) preg_replace('/[^0-9]/', '', $state), 0, ',', '.') : '-'),

                TextColumn::make('budget_macro_kol')
                    ->label('Budget Macro KOL')
                    ->formatStateUsing(fn($state) => $state ? 'Rp ' . number_format((float) preg_replace('/[^0-9]/', '', $state), 0, ',', '.') : '-'),

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
