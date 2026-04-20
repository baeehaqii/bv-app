<?php

namespace App\Filament\Resources\InternalBudgets;

use App\Filament\Resources\InternalBudgets\Pages\CreateInternalBudget;
use App\Filament\Resources\InternalBudgets\Pages\EditInternalBudget;
use App\Filament\Resources\InternalBudgets\Pages\ListInternalBudgets;
use App\Filament\Resources\InternalBudgets\RelationManagers\ItemsRelationManager;
use App\Filament\Resources\InternalBudgets\Schemas\InternalBudgetForm;
use App\Filament\Resources\InternalBudgets\Tables\InternalBudgetsTable;
use App\Models\InternalBudget;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class InternalBudgetResource extends Resource
{
    protected static ?string $model = InternalBudget::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static string|\UnitEnum|null $navigationGroup = 'Media Planning';

    protected static ?int $navigationSort = 1;

    protected static ?string $navigationLabel = 'Media Plan External';

    protected static ?string $modelLabel = 'Media Plan External';

    protected static ?string $pluralModelLabel = 'Media Plan External';

    //route
    protected static ?string $slug = 'media-plan-external';

    public static function form(Schema $schema): Schema
    {
        return InternalBudgetForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return InternalBudgetsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            ItemsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListInternalBudgets::route('/'),
            'create' => CreateInternalBudget::route('/create'),
            'edit' => EditInternalBudget::route('/{record}/edit'),
        ];
    }
}
