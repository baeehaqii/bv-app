<?php

namespace App\Filament\Resources\BvSalesLists;

use App\Filament\Resources\BvSalesLists\Pages\CreateBvSalesList;
use App\Filament\Resources\BvSalesLists\Pages\EditBvSalesList;
use App\Filament\Resources\BvSalesLists\Pages\ListBvSalesLists;
use App\Filament\Resources\BvSalesLists\Schemas\BvSalesListForm;
use App\Filament\Resources\BvSalesLists\Tables\BvSalesListsTable;
use App\Models\BvSalesList;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class BvSalesListResource extends Resource
{
    protected static ?string $model = BvSalesList::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-queue-list';

    protected static ?string $recordTitleAttribute = 'BvSalesList';

    protected static string|UnitEnum|null $navigationGroup = 'Master Data';

    protected static ?string $navigationLabel = 'Sales List';

    protected static ?int $navigationSort = 10;

    public static function form(Schema $schema): Schema
    {
        return BvSalesListForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return BvSalesListsTable::configure($table);
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
            'index' => ListBvSalesLists::route('/'),
            'create' => CreateBvSalesList::route('/create'),
            'edit' => EditBvSalesList::route('/{record}/edit'),
        ];
    }
}
