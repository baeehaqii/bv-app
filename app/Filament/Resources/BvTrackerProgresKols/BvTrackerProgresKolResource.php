<?php

namespace App\Filament\Resources\BvTrackerProgresKols;

use App\Filament\Resources\BvTrackerProgresKols\Pages\CreateBvTrackerProgresKol;
use App\Filament\Resources\BvTrackerProgresKols\Pages\EditBvTrackerProgresKol;
use App\Filament\Resources\BvTrackerProgresKols\Pages\ListBvTrackerProgresKols;
use App\Filament\Resources\BvTrackerProgresKols\Schemas\BvTrackerProgresKolForm;
use App\Filament\Resources\BvTrackerProgresKols\Tables\BvTrackerProgresKolsTable;
use App\Models\BvTrackerProgresKol;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class BvTrackerProgresKolResource extends Resource
{
    protected static ?string $model = BvTrackerProgresKol::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;
    protected static string|\UnitEnum|null $navigationGroup = "Campign Area ";
    protected static ?int $navigationSort = 2;
    protected static ?string $navigationLabel = 'Tracker Progress KOL';
    protected static bool $shouldRegisterNavigation = false;
    protected static ?string $modelLabel = 'Tracker Progress KOL';
    protected static ?string $pluralModelLabel = 'Tracker Progress KOL';
    protected static ?string $slug = 'tracker-progress-kol';

    protected static ?string $recordTitleAttribute = 'username';

    public static function form(Schema $schema): Schema
    {
        return BvTrackerProgresKolForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return BvTrackerProgresKolsTable::configure($table);
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
            'index' => ListBvTrackerProgresKols::route('/'),
            'create' => CreateBvTrackerProgresKol::route('/create'),
            'edit' => EditBvTrackerProgresKol::route('/{record}/edit'),
        ];
    }
}
