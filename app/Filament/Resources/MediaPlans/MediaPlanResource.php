<?php

namespace App\Filament\Resources\MediaPlans;

use App\Filament\Resources\MediaPlans\Pages\CreateMediaPlan;
use App\Filament\Resources\MediaPlans\Pages\EditMediaPlan;
use App\Filament\Resources\MediaPlans\Pages\ListMediaPlans;
use App\Filament\Resources\MediaPlans\Schemas\MediaPlanForm;
use App\Filament\Resources\MediaPlans\Tables\MediaPlansTable;
use App\Models\MediaPlan;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class MediaPlanResource extends Resource
{
    protected static ?string $model = MediaPlan::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-m-chart-bar-square';
    protected static string|\UnitEnum|null $navigationGroup = "Media Planning";
    protected static ?int $navigationSort = 1;
    protected static ?string $navigationLabel = 'Media Plan';
    protected static ?string $modelLabel = 'Media Plan';
    protected static ?string $pluralModelLabel = 'Media Plans';
    protected static ?string $slug = 'media-plan';

    // Enable global search
    public static function getGloballySearchableAttributes(): array
    {
        return ['brand', 'campaign_name', 'quotation_number'];
    }

    public static function getGlobalSearchResultTitle($record): string
    {
        return $record->campaign_name ?? 'N/A';
    }

    public static function getGlobalSearchResultDetails($record): array
    {
        return [
            'Brand' => $record->brand ?? 'N/A',
            'Quotation' => $record->quotation_number ?? 'N/A',
            'KOLs Count' => $record->kols->count() . ' KOL(s)',
        ];
    }

    public static function form(Schema $schema): Schema
    {
        return MediaPlanForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return MediaPlansTable::configure($table);
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
            'index' => ListMediaPlans::route('/'),
            'create' => CreateMediaPlan::route('/create'),
            'edit' => EditMediaPlan::route('/{record}/edit'),
        ];
    }
}
