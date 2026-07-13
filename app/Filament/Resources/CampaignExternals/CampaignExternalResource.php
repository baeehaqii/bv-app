<?php

namespace App\Filament\Resources\CampaignExternals;

use App\Filament\Resources\CampaignExternals\Pages\ListCampaignExternals;
use App\Filament\Resources\CampaignExternals\Pages\ViewCampaignExternal;
use App\Filament\Resources\CampaignExternals\RelationManagers\StorylinesExternalRelationManager;
use App\Filament\Resources\CampaignExternals\RelationManagers\TrackerExternalRelationManager;
use App\Filament\Resources\CampaignExternals\Tables\CampaignExternalsTable;
use App\Models\BvCampign;
use BackedEnum;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Enums\FontWeight;
use Filament\Tables\Table;

class CampaignExternalResource extends Resource
{
    protected static ?string $model = BvCampign::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-globe-alt';
    protected static string|\UnitEnum|null $navigationGroup = 'Campaign Area';
    protected static ?int $navigationSort = 2;
    protected static ?string $navigationLabel = 'Campaign Ongoing External';
    protected static ?string $modelLabel = 'Campaign Ongoing External';
    protected static ?string $pluralModelLabel = 'Campaign Ongoing External';
    protected static ?string $slug = 'campaign-ongoing-external';

    protected static ?string $recordTitleAttribute = 'campaign_name';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([]);
    }

    public static function infolist(Schema $infolist): Schema
    {
        return $infolist
            ->schema([
                Grid::make()
                    ->columns(2)
                    ->schema([
                        Section::make('Informasi Campaign')
                            ->columns(3)
                            ->schema([
                                TextEntry::make('campaign_name')
                                    ->label('Nama Campaign')
                                    ->weight(FontWeight::SemiBold)
                                    ->columnSpan(2),

                                TextEntry::make('status')
                                    ->label('Status')
                                    ->badge()
                                    ->color(fn($state) => match ($state) {
                                        'ongoing'   => 'success',
                                        'upcoming'  => 'warning',
                                        'completed' => 'gray',
                                        default     => 'primary',
                                    }),

                                TextEntry::make('client.nama_brand')
                                    ->label('Client / Brand'),

                                TextEntry::make('start_date')
                                    ->label('Mulai')
                                    ->date('d M Y'),

                                TextEntry::make('end_date')
                                    ->label('Selesai')
                                    ->date('d M Y'),
                            ]),

                        // Link yang sudah tergenerate — tampil di sini biar tidak dibuat 2x.
                        Section::make('Link Campaign')
                            ->icon('heroicon-o-link')
                            ->schema([
                                TextEntry::make('content_review_url')
                                    ->label('Link Approval Konten')
                                    ->state(fn($record) => $record->content_review_is_public ? $record->content_review_url : null)
                                    ->placeholder('Belum dibuat — pakai tombol "Approval Konten"')
                                    ->copyable()
                                    ->copyMessage('Link approval disalin')
                                    ->url(fn($record) => $record->content_review_is_public ? $record->content_review_url : null)
                                    ->openUrlInNewTab()
                                    ->color('primary'),

                                TextEntry::make('public_url')
                                    ->label('Link Performa Konten (External)')
                                    ->state(fn($record) => $record->is_public ? $record->public_url : null)
                                    ->placeholder('Belum dibuat — pakai tombol "Link External"')
                                    ->copyable()
                                    ->copyMessage('Link external disalin')
                                    ->url(fn($record) => $record->is_public ? $record->public_url : null)
                                    ->openUrlInNewTab()
                                    ->color('primary'),
                            ]),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return CampaignExternalsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            TrackerExternalRelationManager::class,
            StorylinesExternalRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListCampaignExternals::route('/'),
            'view'  => ViewCampaignExternal::route('/{record}'),
        ];
    }
}
