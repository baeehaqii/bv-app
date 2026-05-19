<?php

namespace App\Filament\Resources\Departments\Schemas;

use App\Enums\DivisionSyncType;
use App\Models\Division;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;
use Illuminate\Support\Str;

class DepartmentForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('division_id')
                    ->label('Divisi')
                    ->relationship('division', 'name')
                    ->searchable()
                    ->preload()
                    ->required(),

                TextInput::make('name')
                    ->label('Nama Departemen')
                    ->required()
                    ->live(onBlur: true)
                    ->afterStateUpdated(fn (string $operation, $state, callable $set) =>
                        $operation === 'create' ? $set('slug', Str::slug($state)) : null
                    ),

                TextInput::make('slug')
                    ->required()
                    ->unique(ignoreRecord: true)
                    ->alphaDash(),

                Textarea::make('description')
                    ->label('Deskripsi')
                    ->rows(3)
                    ->nullable(),

                Select::make('sync_type')
                    ->label('Sync Otomatis ke')
                    ->options(DivisionSyncType::options())
                    ->placeholder('— Ikuti setting Divisi —')
                    ->nullable()
                    ->helperText('Isi jika departemen ini perlu sync berbeda dari divisinya.'),

                Toggle::make('is_active')
                    ->label('Aktif')
                    ->default(true),
            ]);
    }
}
