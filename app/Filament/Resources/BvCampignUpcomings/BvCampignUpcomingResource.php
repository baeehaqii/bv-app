<?php

namespace App\Filament\Resources\BvCampignUpcomings;

use App\Filament\Resources\BvCampignUpcomings\Pages\CreateBvCampignUpcoming;
use App\Filament\Resources\BvCampignUpcomings\Pages\EditBvCampignUpcoming;
use App\Filament\Resources\BvCampignUpcomings\Pages\ListBvCampignUpcomings;
use App\Filament\Resources\BvCampignUpcomings\Pages\ViewBvCampignUpcoming;
use App\Filament\Resources\BvCampignUpcomings\Schemas\BvCampignUpcomingForm;
use App\Filament\Resources\BvCampignUpcomings\Schemas\BvCampignUpcomingInfolist;
use App\Filament\Resources\BvCampignUpcomings\Tables\BvCampignUpcomingsTable;
use App\Models\BvCampignUpcoming;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class BvCampignUpcomingResource extends Resource
{
    protected static ?string $model = BvCampignUpcoming::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-calendar-days';
    protected static string|\UnitEnum|null $navigationGroup = "Campign Area ";
    protected static ?int $navigationSort = 2; // Upcoming likely separate or before/after Ongoing
    protected static ?string $navigationLabel = 'Upcoming Campaign';
    protected static ?string $modelLabel = 'Upcoming Campaign';
    protected static ?string $pluralModelLabel = 'Upcoming Campaigns';
    protected static ?string $slug = 'upcoming-campaign';

    public static function form(Schema $schema): Schema
    {
        return BvCampignUpcomingForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return BvCampignUpcomingInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return BvCampignUpcomingsTable::configure($table);
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
            'index' => ListBvCampignUpcomings::route('/'),
            'view' => ViewBvCampignUpcoming::route('/{record}'),
            'edit' => EditBvCampignUpcoming::route('/{record}/edit'),
        ];
    }
}
