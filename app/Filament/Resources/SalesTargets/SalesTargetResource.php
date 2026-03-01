<?php

namespace App\Filament\Resources\SalesTargets;

use App\Filament\Resources\SalesTargets\Pages\CreateSalesTarget;
use App\Filament\Resources\SalesTargets\Pages\EditSalesTarget;
use App\Filament\Resources\SalesTargets\Pages\ListSalesTargets;
use App\Filament\Resources\SalesTargets\Schemas\SalesTargetForm;
use App\Filament\Resources\SalesTargets\Tables\SalesTargetsTable;
use App\Models\SalesTarget;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;

class SalesTargetResource extends Resource
{
    protected static ?string $model = SalesTarget::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-user-group';

    protected static string|\UnitEnum|null $navigationGroup = 'Sales';

    protected static ?int $navigationSort = 10;

    protected static ?string $navigationLabel = 'Target Per Sales';

    protected static ?string $modelLabel = 'Target Sales';

    protected static ?string $pluralModelLabel = 'Target Per Sales';

    protected static ?string $slug = 'target-per-sales';

    protected static ?string $recordTitleAttribute = 'id';

    protected static array $editableRoles = [
        'super_admin',
        'c_level',
        'finance',
    ];

    // -------------------------------------------------------
    // Authorization — hanya Super Admin, C Level, Finance
    // -------------------------------------------------------

    public static function canCreate(): bool
    {
        return static::hasAllowedRole();
    }

    public static function canEdit(\Illuminate\Database\Eloquent\Model $record): bool
    {
        return static::hasAllowedRole();
    }

    public static function canDelete(\Illuminate\Database\Eloquent\Model $record): bool
    {
        return static::hasAllowedRole();
    }

    public static function canDeleteAny(): bool
    {
        return static::hasAllowedRole();
    }

    protected static function hasAllowedRole(): bool
    {
        $user = auth()->user();
        return $user && $user->hasAnyRole(static::$editableRoles);
    }

    // -------------------------------------------------------
    // Schema / Table
    // -------------------------------------------------------

    public static function form(Schema $schema): Schema
    {
        return SalesTargetForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return SalesTargetsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListSalesTargets::route('/'),
            'create' => CreateSalesTarget::route('/create'),
            'edit' => EditSalesTarget::route('/{record}/edit'),
        ];
    }
}
