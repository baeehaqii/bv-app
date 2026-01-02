<?php

namespace App\Filament\Resources\BvEmployes;

use App\Filament\Resources\BvEmployes\Pages\CreateBvEmploye;
use App\Filament\Resources\BvEmployes\Pages\EditBvEmploye;
use App\Filament\Resources\BvEmployes\Pages\ListBvEmployes;
use App\Filament\Resources\BvEmployes\Schemas\BvEmployeForm;
use App\Filament\Resources\BvEmployes\Tables\BvEmployesTable;
use App\Models\BvEmploye;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class BvEmployeResource extends Resource
{
    protected static ?string $model = BvEmploye::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-users';
    protected static string|\UnitEnum|null $navigationGroup = "Human Capital ";
    protected static ?int $navigationSort = 1;
    protected static ?string $navigationLabel = 'Data Karyawan';
    protected static ?string $modelLabel = 'Data Karyawan';
    protected static ?string $pluralModelLabel = 'Data Karyawan';
    protected static ?string $slug = 'data-karyawan';

    protected static ?string $recordTitleAttribute = 'nama_lengkap';

    public static function form(Schema $schema): Schema
    {
        return BvEmployeForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return BvEmployesTable::configure($table);
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
            'index' => ListBvEmployes::route('/'),
            'create' => CreateBvEmploye::route('/create'),
            'edit' => EditBvEmploye::route('/{record}/edit'),
        ];
    }
}
