<?php

namespace App\Filament\Resources\MasterSows;

use App\Filament\Resources\MasterSows\Pages\CreateMasterSow;
use App\Filament\Resources\MasterSows\Pages\EditMasterSow;
use App\Filament\Resources\MasterSows\Pages\ListMasterSows;
use App\Filament\Resources\MasterSows\Schemas\MasterSowForm;
use App\Filament\Resources\MasterSows\Tables\MasterSowsTable;
use App\Models\MasterSow;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;

class MasterSowResource extends Resource
{
    protected static ?string $model = MasterSow::class;

    protected static \BackedEnum|string|null $navigationIcon = 'heroicon-o-clipboard-document-list';

    protected static ?string $recordTitleAttribute = 'name';

    protected static ?string $navigationLabel = 'Master SOW';

    protected static ?string $modelLabel = 'SOW';

    protected static ?string $pluralModelLabel = 'SOW';

    protected static string|\UnitEnum|null $navigationGroup = 'Master Data';

    protected static ?int $navigationSort = 16;

    public static function form(Schema $schema): Schema
    {
        return MasterSowForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return MasterSowsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index'  => ListMasterSows::route('/'),
            'create' => CreateMasterSow::route('/create'),
            'edit'   => EditMasterSow::route('/{record}/edit'),
        ];
    }
}
