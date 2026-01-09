<?php

namespace App\Filament\Resources\MasterServices;

use App\Filament\Resources\MasterServices\Pages\CreateMasterService;
use App\Filament\Resources\MasterServices\Pages\EditMasterService;
use App\Filament\Resources\MasterServices\Pages\ListMasterServices;
use App\Filament\Resources\MasterServices\Schemas\MasterServiceForm;
use App\Filament\Resources\MasterServices\Tables\MasterServicesTable;
use App\Models\MasterService;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;

class MasterServiceResource extends Resource
{
    protected static ?string $model = MasterService::class;

    protected static \BackedEnum|string|null $navigationIcon = 'heroicon-o-wrench-screwdriver';

    protected static ?string $recordTitleAttribute = 'nama_service';

    protected static ?string $navigationLabel = 'Master Service';

    protected static ?string $modelLabel = 'Service';

    protected static ?string $pluralModelLabel = 'Services';

    protected static string|\UnitEnum|null $navigationGroup = 'Master Data';

    protected static ?int $navigationSort = 15;

    public static function form(Schema $schema): Schema
    {
        return MasterServiceForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return MasterServicesTable::configure($table);
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
            'index' => ListMasterServices::route('/'),
            'create' => CreateMasterService::route('/create'),
            'edit' => EditMasterService::route('/{record}/edit'),
        ];
    }
}
