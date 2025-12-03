<?php

namespace App\Filament\Resources\InternalBudgets;

use App\Filament\Resources\InternalBudgets\Pages\CreateInternalBudget;
use App\Filament\Resources\InternalBudgets\Pages\EditInternalBudget;
use App\Filament\Resources\InternalBudgets\Pages\ListInternalBudgets;
use App\Filament\Resources\InternalBudgets\Schemas\InternalBudgetForm;
use App\Filament\Resources\InternalBudgets\Tables\InternalBudgetsTable;
use App\Models\InternalBudget;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;

class InternalBudgetResource extends Resource
{
    protected static ?string $model = InternalBudget::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-m-calculator';
    protected static string|\UnitEnum|null $navigationGroup = "Media Planning";
    protected static ?int $navigationSort = 2;
    protected static ?string $navigationLabel = 'Internal Budget';
    protected static ?string $modelLabel = 'Internal Budget';
    protected static ?string $pluralModelLabel = 'Internal Budgets';
    protected static ?string $slug = 'internal-budget';

    public static function getGloballySearchableAttributes(): array
    {
        return ['mediaPlan.campaign_name', 'mediaPlan.username', 'scopeofwork_item'];
    }

    public static function getGlobalSearchResultTitle($record): string
    {
        return $record->mediaPlan?->campaign_name ?? 'Internal Budget';
    }

    public static function getGlobalSearchResultDetails($record): array
    {
        return [
            'Campaign' => $record->mediaPlan?->campaign_name ?? 'N/A',
            'KOL' => $record->mediaPlan?->username ?? 'N/A',
            'Margin' => $record->margin_percent ? number_format($record->margin_percent, 2) . '%' : 'N/A',
        ];
    }

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
            //
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
