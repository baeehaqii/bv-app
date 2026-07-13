<?php

namespace App\Filament\Resources\MasterPphs;

use App\Filament\Resources\MasterPphs\Pages\CreateMasterPph;
use App\Filament\Resources\MasterPphs\Pages\EditMasterPph;
use App\Filament\Resources\MasterPphs\Pages\ListMasterPphs;
use App\Filament\Resources\MasterPphs\Schemas\MasterPphForm;
use App\Filament\Resources\MasterPphs\Tables\MasterPphsTable;
use App\Models\MasterPph;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;

class MasterPphResource extends Resource
{
    protected static ?string $model = MasterPph::class;

    protected static \BackedEnum|string|null $navigationIcon = 'heroicon-o-receipt-percent';

    protected static ?string $recordTitleAttribute = 'name';

    protected static ?string $navigationLabel = 'Master PPH';

    protected static string|\UnitEnum|null $navigationGroup = 'Master Data';

    protected static ?int $navigationSort = 20;

    public static function form(Schema $schema): Schema
    {
        return MasterPphForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return MasterPphsTable::configure($table);
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
            'index' => ListMasterPphs::route('/'),
            'create' => CreateMasterPph::route('/create'),
            'edit' => EditMasterPph::route('/{record}/edit'),
        ];
    }
}
