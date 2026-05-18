<?php

namespace App\Filament\Resources\CampaignExternals\RelationManagers;

use App\Models\CampaignStoryline;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

/**
 * Read-only storyline tab untuk Campaign Ongoing External.
 * Hanya menampilkan storyline berstatus 'approved'.
 */
class StorylinesExternalRelationManager extends RelationManager
{
    protected static string $relationship = 'storylines';

    protected static ?string $title = 'Storyline';

    protected static ?string $recordTitleAttribute = 'kol_name';

    public function isReadOnly(): bool
    {
        return true;
    }

    public function form(Schema $schema): Schema
    {
        return $schema->components([]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn($query) => $query->where('status', 'approved'))
            ->columns([
                TextColumn::make('kol_name')
                    ->label('KOL / Creator')
                    ->searchable()
                    ->sortable()
                    ->weight(\Filament\Support\Enums\FontWeight::SemiBold),

                TextColumn::make('platform')
                    ->label('Platform')
                    ->badge()
                    ->color(fn($state) => match ($state) {
                        'instagram' => 'pink',
                        'tiktok'    => 'gray',
                        'youtube'   => 'danger',
                        'threads'   => 'info',
                        default     => 'primary',
                    })
                    ->formatStateUsing(fn($state) => CampaignStoryline::PLATFORMS[$state] ?? ucfirst($state)),

                TextColumn::make('sow')
                    ->label('SOW'),

                TextColumn::make('content_angle')
                    ->label('Content Angle')
                    ->limit(40),

                TextColumn::make('key_message')
                    ->label('Key Message')
                    ->limit(60)
                    ->wrap(),

                TextColumn::make('posting_deadline')
                    ->label('Deadline Posting')
                    ->date('d M Y')
                    ->sortable(),

                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->color(fn($state) => match ($state) {
                        'approved' => 'success',
                        'posted'   => 'primary',
                        default    => 'gray',
                    })
                    ->formatStateUsing(fn($state) => CampaignStoryline::STATUSES[$state] ?? ucfirst($state)),
            ])
            ->filters([
                SelectFilter::make('platform')
                    ->label('Platform')
                    ->options(CampaignStoryline::PLATFORMS),
            ])
            ->defaultSort('posting_deadline', 'asc')
            ->emptyStateHeading('Belum ada storyline yang disetujui')
            ->emptyStateDescription('Storyline akan muncul di sini setelah disetujui oleh tim internal.')
            ->emptyStateIcon('heroicon-o-document-check')
            ->headerActions([])
            ->actions([])
            ->bulkActions([]);
    }
}
