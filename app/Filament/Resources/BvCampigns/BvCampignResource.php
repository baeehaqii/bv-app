<?php

namespace App\Filament\Resources\BvCampigns;

use App\Filament\Resources\BvCampigns\Pages\CreateBvCampign;
use App\Filament\Resources\BvCampigns\Pages\EditBvCampign;
use App\Filament\Resources\BvCampigns\Pages\ListBvCampigns;
use App\Filament\Resources\BvCampigns\Schemas\BvCampignForm;
use App\Filament\Resources\BvCampigns\Tables\BvCampignsTable;
use App\Models\BvCampign;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class BvCampignResource extends Resource
{
    protected static ?string $model = BvCampign::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-megaphone';
    protected static string|\UnitEnum|null $navigationGroup = "Campign Area ";
    protected static ?int $navigationSort = 1;
    protected static ?string $navigationLabel = 'Campign Ongoing';
    protected static ?string $modelLabel = 'Campign Ongoing';
    protected static ?string $pluralModelLabel = 'Campign Ongoing';
    protected static ?string $slug = 'campign-ongoing';

    protected static ?string $recordTitleAttribute = 'campaign_name';

    public static function form(Schema $schema): Schema
    {
        return BvCampignForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return BvCampignsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListBvCampigns::route('/'),
            'create' => CreateBvCampign::route('/create'),
            'edit' => EditBvCampign::route('/{record}/edit'),
        ];
    }
}
