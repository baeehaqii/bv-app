<?php

namespace App\Filament\Resources\BvQuotations;

use App\Filament\Resources\BvQuotations\Pages\CreateBvQuotation;
use App\Filament\Resources\BvQuotations\Pages\EditBvQuotation;
use App\Filament\Resources\BvQuotations\Pages\ListBvQuotations;
use App\Filament\Resources\BvQuotations\Schemas\BvQuotationForm;
use App\Filament\Resources\BvQuotations\Tables\BvQuotationsTable;
use App\Models\BvQuotation;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class BvQuotationResource extends Resource
{
    protected static ?string $model = BvQuotation::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-document-duplicate';
    protected static string|\UnitEnum|null $navigationGroup = "Finance";
    protected static ?int $navigationSort = 2;
    protected static ?string $navigationLabel = 'Quotation';
    protected static ?string $modelLabel = 'Quotation';
    protected static ?string $pluralModelLabel = 'Quotation';
    protected static ?string $slug = 'quotation';

    protected static ?string $recordTitleAttribute = 'BvQuotation';

    public static function form(Schema $schema): Schema
    {
        return BvQuotationForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return BvQuotationsTable::configure($table);
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
            'index' => ListBvQuotations::route('/'),
            'create' => CreateBvQuotation::route('/create'),
            'edit' => EditBvQuotation::route('/{record}/edit'),
        ];
    }
}
