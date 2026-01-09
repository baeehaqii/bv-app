<?php

namespace App\Filament\Resources\BvPeformaKOLS;

use App\Filament\Resources\BvPeformaKOLS\Pages\CreateBvPeformaKOL;
use App\Filament\Resources\BvPeformaKOLS\Pages\EditBvPeformaKOL;
use App\Filament\Resources\BvPeformaKOLS\Pages\ListBvPeformaKOLS;
use App\Filament\Resources\BvPeformaKOLS\Schemas\BvPeformaKOLForm;
use App\Filament\Resources\BvPeformaKOLS\Tables\BvPeformaKOLSTable;
use App\Models\BvPeformaKOL;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class BvPeformaKOLResource extends Resource
{
    protected static ?string $model = BvPeformaKOL::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-chart-bar';

    protected static string|\UnitEnum|null $navigationGroup = 'KOL Management';

    protected static ?int $navigationSort = 3;

    protected static ?string $navigationLabel = 'Performa KOL';

    protected static ?string $modelLabel = 'Performa KOL';

    protected static ?string $pluralModelLabel = 'Performa KOL';

    protected static ?string $recordTitleAttribute = 'username';

    public static function form(Schema $schema): Schema
    {
        return BvPeformaKOLForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return BvPeformaKOLSTable::configure($table);
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
            'index' => ListBvPeformaKOLS::route('/'),
            'create' => CreateBvPeformaKOL::route('/create'),
            'edit' => EditBvPeformaKOL::route('/{record}/edit'),
        ];
    }
}
