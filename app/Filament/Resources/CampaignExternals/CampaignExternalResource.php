<?php

namespace App\Filament\Resources\CampaignExternals;

use App\Filament\Resources\CampaignExternals\Pages\ListCampaignExternals;
use App\Filament\Resources\CampaignExternals\Pages\ViewCampaignExternal;
use App\Filament\Resources\CampaignExternals\RelationManagers\StorylinesExternalRelationManager;
use App\Filament\Resources\CampaignExternals\Tables\CampaignExternalsTable;
use App\Models\BvCampign;
use BackedEnum;
use Filament\Infolists\Components\TextEntry;
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
            ]);
    }

    public static function table(Table $table): Table
    {
        return CampaignExternalsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
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
