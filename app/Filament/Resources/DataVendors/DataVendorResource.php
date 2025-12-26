<?php

namespace App\Filament\Resources\DataVendors;

use App\Filament\Resources\DataVendors\Pages\CreateDataVendor;
use App\Filament\Resources\DataVendors\Pages\EditDataVendor;
use App\Filament\Resources\DataVendors\Pages\ListDataVendors;
use App\Filament\Resources\DataVendors\Schemas\DataVendorForm;
use App\Filament\Resources\DataVendors\Tables\DataVendorsTable;
use App\Models\DataVendor;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class DataVendorResource extends Resource
{
    protected static ?string $model = DataVendor::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-swatch';
    protected static string|\UnitEnum|null $navigationGroup = "Database";
    protected static ?int $navigationSort = 2;
    protected static ?string $navigationLabel = 'Database Vendor';
    protected static ?string $modelLabel = 'Database Vendor';
    protected static ?string $pluralModelLabel = 'Database Vendor';
    protected static ?string $slug = 'data-vendor';

    public static function form(Schema $schema): Schema
    {
        return DataVendorForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return DataVendorsTable::configure($table);
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
            'index' => ListDataVendors::route('/'),
            'create' => CreateDataVendor::route('/create'),
            'edit' => EditDataVendor::route('/{record}/edit'),
        ];
    }
}
