<?php

namespace App\Filament\Resources\Okrs;

use App\Filament\Resources\Okrs\Pages\CreateOkr;
use App\Filament\Resources\Okrs\Pages\EditOkr;
use App\Filament\Resources\Okrs\Pages\ListOkrs;
use App\Filament\Resources\Okrs\Schemas\OkrForm;
use App\Models\Okr;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;

/**
 * OKR tim — pengganti sheet "BV 2026 - Weekly Meetings - OKR".
 *
 * Bentuknya sengaja dibuat sama dengan sheet-nya (Status, Objective, Key
 * Results, Results per orang per periode) supaya isinya bisa dipindah apa
 * adanya. Angka realisasi otomatis belum ada di sini: dari belasan Key Result
 * di sheet cuma tiga yang punya sumber data di aplikasi.
 */
class OkrResource extends Resource
{
    protected static ?string $model = Okr::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-flag';

    protected static string|\UnitEnum|null $navigationGroup = 'Human Capital ';

    protected static ?int $navigationSort = 1;

    protected static ?string $navigationLabel = 'OKR';

    protected static ?string $modelLabel = 'OKR';

    protected static ?string $pluralModelLabel = 'OKR';

    protected static ?string $slug = 'okr';

    protected static ?string $recordTitleAttribute = 'objective';

    public static function form(Schema $schema): Schema
    {
        return OkrForm::configure($schema);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListOkrs::route('/'),
            'create' => CreateOkr::route('/create'),
            'edit' => EditOkr::route('/{record}/edit'),
        ];
    }
}
