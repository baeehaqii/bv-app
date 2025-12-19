<?php

namespace App\Filament\Resources\MasterMargins;

use App\Filament\Resources\MasterMargins\Pages\CreateMasterMargin;
use App\Filament\Resources\MasterMargins\Pages\EditMasterMargin;
use App\Filament\Resources\MasterMargins\Pages\ListMasterMargins;
use App\Filament\Resources\MasterMargins\Schemas\MasterMarginForm;
use App\Filament\Resources\MasterMargins\Tables\MasterMarginsTable;
use App\Models\MasterMargin;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;

class MasterMarginResource extends Resource
{
    protected static ?string $model = MasterMargin::class;

    protected static \BackedEnum|string|null $navigationIcon = 'heroicon-o-calculator';

    protected static ?string $recordTitleAttribute = 'name';

    protected static ?string $navigationLabel = 'Master Margins';

    protected static string|\UnitEnum|null $navigationGroup = 'Master Data';

    protected static ?int $navigationSort = 10;

    public static function form(Schema $schema): Schema
    {
        return MasterMarginForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return MasterMarginsTable::configure($table);
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
            'index' => ListMasterMargins::route('/'),
            'create' => CreateMasterMargin::route('/create'),
            'edit' => EditMasterMargin::route('/{record}/edit'),
        ];
    }
}
