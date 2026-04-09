<?php

namespace App\Filament\Resources\Spks;

use App\Filament\Resources\Spks\Pages\CreateSpk;
use App\Filament\Resources\Spks\Pages\EditSpk;
use App\Filament\Resources\Spks\Pages\ListSpks;
use App\Filament\Resources\Spks\Pages\ViewSpkDocument;
use App\Filament\Resources\Spks\Schemas\SpkForm;
use App\Filament\Resources\Spks\Tables\SpksTable;
use App\Models\BvSPK;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;

class SpkResource extends Resource
{
    protected static ?string $model = BvSPK::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-document-text';

    protected static string|\UnitEnum|null $navigationGroup = 'Campign Area ';

    protected static ?int $navigationSort = 2;

    protected static ?string $navigationLabel = 'Contract';

    protected static ?string $modelLabel = 'Contract';

    protected static ?string $pluralModelLabel = 'Contracts';

    protected static ?string $slug = 'spk';

    protected static ?string $recordTitleAttribute = 'spk_number';

    public static function form(Schema $schema): Schema
    {
        return SpkForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return SpksTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListSpks::route('/'),
            'create' => CreateSpk::route('/create'),
            'edit' => EditSpk::route('/{record}/edit'),
            'document' => ViewSpkDocument::route('/{record}/document'),
        ];
    }
}
