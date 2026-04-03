<?php

namespace App\Filament\Resources\SalesTargets;

use App\Filament\Resources\SalesTargets\Pages\CreateSalesTarget;
use App\Filament\Resources\SalesTargets\Pages\EditSalesTarget;
use App\Filament\Resources\SalesTargets\Pages\ListSalesTargets;
use App\Filament\Resources\SalesTargets\Schemas\SalesTargetForm;
use App\Filament\Resources\SalesTargets\Tables\SalesTargetsTable;
use App\Models\BvSalesList;
use App\Models\SalesTarget;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

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

    // -------------------------------------------------------
    // Query Scoping — Sales person hanya melihat target mereka sendiri
    // -------------------------------------------------------

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();
        $user  = auth()->user();

        // Super admin & roles dengan full permission: lihat semua
        if ($user?->can('ViewAny:SalesTarget')) {
            return $query;
        }

        // Sales person yang akunnya terhubung: hanya lihat target milik mereka
        $salesListId = BvSalesList::where('user_id', $user?->id)->value('id');

        if ($salesListId) {
            return $query->where('bv_sales_list_id', $salesListId);
        }

        // Tidak ada akses → kembalikan query kosong
        return $query->whereRaw('0 = 1');
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
