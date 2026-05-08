<?php

namespace App\Filament\Resources\CampaignExternals;

use App\Filament\Resources\CampaignExternals\Pages\ListCampaignExternals;
use App\Filament\Resources\CampaignExternals\Pages\ViewCampaignExternal;
use App\Filament\Resources\CampaignExternals\Tables\CampaignExternalsTable;
use App\Models\BvCampign;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
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

    public static function table(Table $table): Table
    {
        return CampaignExternalsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListCampaignExternals::route('/'),
            'view'  => ViewCampaignExternal::route('/{record}'),
        ];
    }
}
