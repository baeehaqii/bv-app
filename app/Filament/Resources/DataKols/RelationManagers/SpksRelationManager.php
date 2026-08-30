<?php

namespace App\Filament\Resources\DataKols\RelationManagers;

use App\Filament\Resources\Spks\Actions\SignatureLinkAction;
use App\Filament\Resources\Spks\SpkResource;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

/**
 * Riwayat SPK KOL ini: pernah kontrak di campaign apa saja, nominal berapa,
 * sudah tanda tangan atau belum. Read-only — SPK diterbitkan dari Internal Budget
 * (setelah client approve), bukan dibuat manual dari halaman KOL.
 */
class SpksRelationManager extends RelationManager
{
    protected static string $relationship = 'spks';

    protected static ?string $title = 'Riwayat SPK / PKS';

    protected static \BackedEnum|string|null $icon = 'heroicon-o-document-check';

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('spk_number')
            ->emptyStateHeading('Belum ada SPK')
            ->emptyStateDescription('SPK terbit otomatis dari Media Plan External setelah client approve KOL ini.')
            ->columns([
                TextColumn::make('spk_number')
                    ->label('Nomor SPK')
                    ->searchable()
                    ->copyable(),

                TextColumn::make('nama_campaign')
                    ->label('Campaign')
                    ->searchable()
                    ->placeholder('—'),

                TextColumn::make('tanggal_perjanjian')
                    ->label('Tanggal')
                    ->date('d M Y')
                    ->sortable(),

                TextColumn::make('nominal_kesepakatan')
                    ->label('Nominal')
                    ->money('IDR', 0)
                    ->alignEnd()
                    ->summarize(\Filament\Tables\Columns\Summarizers\Sum::make()->money('IDR', 0)->label('Total')),

                TextColumn::make('status')
                    ->badge()
                    ->color(fn(string $state): string => match ($state) {
                        'draft' => 'gray',
                        'active' => 'info',
                        'signed' => 'success',
                        'cancelled' => 'danger',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn(string $state): string => match ($state) {
                        'draft' => 'Draft',
                        'active' => 'Menunggu TTD',
                        'signed' => 'Signed',
                        'cancelled' => 'Cancelled',
                        default => ucfirst($state),
                    }),

                TextColumn::make('signed_at')
                    ->label('Ditandatangani')
                    ->dateTime('d M Y H:i')
                    ->placeholder('Belum'),
            ])
            ->recordActions([
                SignatureLinkAction::make(),

                Action::make('document')
                    ->label('Dokumen')
                    ->icon('heroicon-o-document-text')
                    ->url(fn($record) => SpkResource::getUrl('document', ['record' => $record]))
                    ->openUrlInNewTab(),

                EditAction::make()
                    ->url(fn($record) => SpkResource::getUrl('edit', ['record' => $record])),
            ])
            ->defaultSort('tanggal_perjanjian', 'desc');
    }
}
