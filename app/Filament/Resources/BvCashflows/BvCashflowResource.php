<?php

namespace App\Filament\Resources\BvCashflows;

use App\Filament\Resources\BvCashflows\Pages\CreateBvCashflow;
use App\Filament\Resources\BvCashflows\Pages\EditBvCashflow;
use App\Filament\Resources\BvCashflows\Pages\ListBvCashflows;
use App\Filament\Resources\BvCashflows\Schemas\BvCashflowForm;
use App\Filament\Resources\BvCashflows\Tables\BvCashflowsTable;
use App\Models\BvCashflow;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class BvCashflowResource extends Resource
{
    protected static ?string $model = BvCashflow::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-banknotes';
    protected static string|\UnitEnum|null $navigationGroup = "Finance";
    protected static ?int $navigationSort = 1;
    protected static ?string $navigationLabel = 'Cashflow';
    protected static ?string $modelLabel = 'Cashflow';
    protected static ?string $pluralModelLabel = 'Cashflow';
    protected static ?string $slug = 'cashflow';

    protected static ?string $recordTitleAttribute = 'description';

    public static function form(Schema $schema): Schema
    {
        return BvCashflowForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return BvCashflowsTable::configure($table);
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
            'index' => ListBvCashflows::route('/'),
            'create' => CreateBvCashflow::route('/create'),
            'edit' => EditBvCashflow::route('/{record}/edit'),
        ];
    }
}
