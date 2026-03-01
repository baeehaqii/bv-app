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
use Illuminate\Database\Eloquent\Model;

class GrossProfitTargetResource extends Resource
{
    protected static ?string $model = GrossProfitTarget::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-chart-bar';

    protected static string|\UnitEnum|null $navigationGroup = 'Finance';

    protected static ?int $navigationSort = 1;

    protected static ?string $navigationLabel = 'Target Gross Profit';

    protected static ?string $modelLabel = 'Target GP';

    protected static ?string $pluralModelLabel = 'Target Gross Profit';

    protected static ?string $slug = 'target-gross-profit';

    protected static ?string $recordTitleAttribute = 'year';

    /**
     * Role yang diperbolehkan membuat, mengedit, dan menghapus target GP.
     * C Level dan Finance selain Super Admin.
     */
    protected static array $editableRoles = [
        'super_admin',
        'c_level',
        'finance',
    ];

    // -------------------------------------------------------
    // Authorization
    // -------------------------------------------------------

    /**
     * Semua user yang sudah login bisa melihat halaman ini (View).
     * Hanya role tertentu yang bisa melakukan perubahan data.
     */
    public static function canCreate(): bool
    {
        return static::hasAllowedRole();
    }

    public static function canEdit(Model $record): bool
    {
        return static::hasAllowedRole();
    }

    public static function canDelete(Model $record): bool
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
        if (!$user) {
            return false;
        }

        // Spatie HasRoles – cek apakah user punya salah satu role yang diizinkan
        return $user->hasAnyRole(static::$editableRoles);
    }

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
