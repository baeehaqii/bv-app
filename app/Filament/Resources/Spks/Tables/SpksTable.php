<?php

namespace App\Filament\Resources\Spks\Tables;

use App\Filament\Resources\Spks\SpkResource;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class SpksTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('spk_number')
                    ->label('Nomor Contract')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('client.nama_brand')
                    ->label('Client')
                    ->searchable()
                    ->sortable()
                    ->placeholder('-'),

                TextColumn::make('formBrief.title')
                    ->label('Form Brief')
                    ->searchable()
                    ->limit(35)
                    ->placeholder('-'),

                TextColumn::make('pihak_kedua_nama_lengkap')
                    ->label('Pihak Kedua')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('nama_campaign')
                    ->label('Campaign')
                    ->searchable()
                    ->limit(30),

                TextColumn::make('tanggal_perjanjian')
                    ->label('Tanggal')
                    ->date('d M Y')
                    ->sortable(),

                TextColumn::make('status')
                    ->badge()
                    ->color(fn(string $state): string => match ($state) {
                        'draft' => 'gray',
                        'active' => 'info',
                        'signed' => 'success',
                        'cancelled' => 'danger',
                        default => 'gray',
                    }),
            ])
            ->recordActions([
                Action::make('document')
                    ->label('Dokumen')
                    ->icon('heroicon-o-document-text')
                    ->url(fn($record) => SpkResource::getUrl('document', ['record' => $record]))
                    ->openUrlInNewTab(),
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
