<?php

namespace App\Filament\Resources\DataKols;

use App\Filament\Resources\DataKols\Pages\CreateDataKol;
use App\Filament\Resources\DataKols\Pages\EditDataKol;
use App\Filament\Resources\DataKols\Pages\ListDataKols;
use App\Filament\Resources\DataKols\Schemas\DataKolForm;
use App\Filament\Resources\DataKols\Tables\DataKolsTable;
use App\Models\DataKol;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class DataKolResource extends Resource
{
    protected static ?string $model = DataKol::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-star';
    protected static string|\UnitEnum|null $navigationGroup = 'KOL Area';
    protected static ?int $navigationSort = 1;
    protected static ?string $navigationLabel = 'KOL Data';
    protected static ?string $modelLabel = 'KOL Data';
    protected static ?string $pluralModelLabel = 'KOL Data';
    protected static ?string $slug = 'data-kol';

    // Enable global search
    public static function getGloballySearchableAttributes(): array
    {
        return ['username', 'channel', 'category'];
    }

    public static function getGlobalSearchResultTitle($record): string
    {
        return $record->username ?? 'N/A';
    }

    public static function getGlobalSearchResultDetails($record): array
    {
        return [
            'Channel' => $record->channel ?? 'N/A',
            'Category' => is_array($record->category) ? implode(', ', $record->category) : ($record->category ?? 'N/A'),
        ];
    }

    public static function form(Schema $schema): Schema
    {
        return DataKolForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return DataKolsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            RelationManagers\SpksRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListDataKols::route('/'),
            'edit' => EditDataKol::route('/{record}/edit'),
        ];
    }
}
