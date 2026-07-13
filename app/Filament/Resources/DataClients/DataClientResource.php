<?php

namespace App\Filament\Resources\DataClients;

use App\Filament\Resources\DataClients\Pages\CreateDataClient;
use App\Filament\Resources\DataClients\Pages\DataClientKanban;
use App\Filament\Resources\DataClients\Pages\EditDataClient;
use App\Filament\Resources\DataClients\Pages\ListDataClients;
use App\Filament\Resources\DataClients\Schemas\DataClientForm;
use App\Filament\Resources\DataClients\Tables\DataClientsTable;
use App\Models\DataClient;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;

class DataClientResource extends Resource
{
    protected static ?string $model = DataClient::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-user-group';
    protected static string|\UnitEnum|null $navigationGroup = 'Sales';
    protected static ?int $navigationSort = 11;
    protected static ?string $navigationLabel = 'Database Client';
    protected static ?string $modelLabel = 'Database Client';
    protected static ?string $pluralModelLabel = 'Database Client';
    protected static ?string $slug = 'data-client';


    public static function form(Schema $schema): Schema
    {
        return DataClientForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return DataClientsTable::configure($table);
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
            'index' => ListDataClients::route('/'),
            'kanban' => DataClientKanban::route('/kanban'),
            'create' => CreateDataClient::route('/create'),
            'edit' => EditDataClient::route('/{record}/edit'),
        ];
    }
}
