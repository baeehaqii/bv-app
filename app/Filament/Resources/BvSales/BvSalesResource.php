<?php

namespace App\Filament\Resources\BvSales;

use App\Filament\Resources\BvSales\Pages\CreateBvSales;
use App\Filament\Resources\BvSales\Pages\EditBvSales;
use App\Filament\Resources\BvSales\Pages\ListBvSales;
use App\Filament\Resources\BvSales\Schemas\BvSalesForm;
use App\Filament\Resources\BvSales\Tables\BvSalesTable;
use App\Models\BvSales;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class BvSalesResource extends Resource
{
    protected static ?string $model = BvSales::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;
    protected static string|\UnitEnum|null $navigationGroup = "Sales";
    protected static ?int $navigationSort = 1;
    protected static ?string $navigationLabel = 'Sales Activity Tracker';
    protected static ?string $modelLabel = 'Sales Activity Tracker';
    protected static ?string $pluralModelLabel = 'Sales Activity Tracker';
    protected static ?string $slug = 'sales-activity';


    protected static ?string $recordTitleAttribute = 'nama_sales';

    public static function form(Schema $schema): Schema
    {
        return BvSalesForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return BvSalesTable::configure($table);
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
            'index' => ListBvSales::route('/'),
            'create' => CreateBvSales::route('/create'),
            'edit' => EditBvSales::route('/{record}/edit'),
        ];
    }
}
