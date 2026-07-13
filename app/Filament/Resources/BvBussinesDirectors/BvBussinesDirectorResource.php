<?php

namespace App\Filament\Resources\BvBussinesDirectors;

use App\Filament\Resources\BvBussinesDirectors\Pages\CreateBvBussinesDirector;
use App\Filament\Resources\BvBussinesDirectors\Pages\EditBvBussinesDirector;
use App\Filament\Resources\BvBussinesDirectors\Pages\ListBvBussinesDirectors;
use App\Filament\Resources\BvBussinesDirectors\Schemas\BvBussinesDirectorForm;
use App\Filament\Resources\BvBussinesDirectors\Tables\BvBussinesDirectorsTable;
use App\Models\BvBussinesDirector;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class BvBussinesDirectorResource extends Resource
{
    protected static ?string $model = BvBussinesDirector::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-briefcase';

    protected static string|UnitEnum|null $navigationGroup = 'Master Data';

    protected static ?string $navigationLabel = 'Business Director';

    protected static ?string $modelLabel = 'Business Director';

    protected static ?string $pluralModelLabel = 'Business Director';

    protected static ?int $navigationSort = 9;

    protected static ?string $recordTitleAttribute = 'nama_lengkap';

    public static function form(Schema $schema): Schema
    {
        return BvBussinesDirectorForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return BvBussinesDirectorsTable::configure($table);
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
            'index' => ListBvBussinesDirectors::route('/'),
            'create' => CreateBvBussinesDirector::route('/create'),
            'edit' => EditBvBussinesDirector::route('/{record}/edit'),
        ];
    }
}
