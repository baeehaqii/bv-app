<?php

namespace App\Filament\Resources\GrossProfitTargets;

use App\Filament\Resources\GrossProfitTargets\Pages\CreateGrossProfitTarget;
use App\Filament\Resources\GrossProfitTargets\Pages\EditGrossProfitTarget;
use App\Filament\Resources\GrossProfitTargets\Pages\ListGrossProfitTargets;
use App\Filament\Resources\GrossProfitTargets\Schemas\GrossProfitTargetForm;
use App\Filament\Resources\GrossProfitTargets\Tables\GrossProfitTargetsTable;
use App\Models\GrossProfitTarget;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;

class GrossProfitTargetResource extends Resource
{
    protected static ?string $model = GrossProfitTarget::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-banknotes';

    protected static string|\UnitEnum|null $navigationGroup = 'Finance';

    protected static ?int $navigationSort = 1;

    protected static ?string $navigationLabel = 'Target Finance';

    protected static ?string $modelLabel = 'Target Finance';

    protected static ?string $pluralModelLabel = 'Target Finance';

    protected static ?string $slug = 'target-finance';

    protected static ?string $recordTitleAttribute = 'year';

    // -------------------------------------------------------
    // Schema / Table
    // -------------------------------------------------------

    public static function form(Schema $schema): Schema
    {
        return GrossProfitTargetForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return GrossProfitTargetsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListGrossProfitTargets::route('/'),
            'create' => CreateGrossProfitTarget::route('/create'),
            'edit' => EditGrossProfitTarget::route('/{record}/edit'),
        ];
    }
}
