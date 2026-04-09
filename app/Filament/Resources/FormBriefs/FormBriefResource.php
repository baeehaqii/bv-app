<?php

namespace App\Filament\Resources\FormBriefs;

use App\Filament\Resources\FormBriefs\Pages\CreateFormBrief;
use App\Filament\Resources\FormBriefs\Pages\EditFormBrief;
use App\Filament\Resources\FormBriefs\Pages\ListFormBriefs;
use App\Filament\Resources\FormBriefs\Pages\ViewFormBrief;
use App\Filament\Resources\FormBriefs\Schemas\FormBriefForm;
use App\Filament\Resources\FormBriefs\Schemas\FormBriefInfolist;
use App\Filament\Resources\FormBriefs\Tables\FormBriefsTable;
use App\Models\FormBrief;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;

class FormBriefResource extends Resource
{
    protected static ?string $model = FormBrief::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-document-text';
    protected static string|\UnitEnum|null $navigationGroup = 'Master Data';
    protected static ?int $navigationSort = 1;
    protected static ?string $navigationLabel = 'Form Brief';
    protected static ?string $modelLabel = 'Form Brief';
    protected static ?string $pluralModelLabel = 'Form Brief';
    protected static ?string $slug = 'form-brief';

    public static function form(Schema $schema): Schema
    {
        return FormBriefForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return FormBriefInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return FormBriefsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListFormBriefs::route('/'),
            'create' => CreateFormBrief::route('/create'),
            'view' => ViewFormBrief::route('/{record}'),
            'edit' => EditFormBrief::route('/{record}/edit'),
        ];
    }
}
