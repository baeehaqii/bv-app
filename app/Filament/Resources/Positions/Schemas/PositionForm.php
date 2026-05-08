<?php

namespace App\Filament\Resources\Positions\Schemas;

use App\Models\Position;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class PositionForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('department_id')
                    ->label('Departemen')
                    ->relationship('department', 'name',
                        fn ($query) => $query->with('division')->orderBy('division_id')->orderBy('name')
                    )
                    ->getOptionLabelFromRecordUsing(
                        fn ($record) => $record->division->name . ' › ' . $record->name
                    )
                    ->searchable()
                    ->preload()
                    ->required(),

                TextInput::make('name')
                    ->label('Nama Jabatan')
                    ->required(),

                Select::make('level')
                    ->label('Level')
                    ->options(Position::LEVELS)
                    ->required(),

                Textarea::make('description')
                    ->label('Deskripsi')
                    ->rows(3)
                    ->nullable(),

                Toggle::make('is_active')
                    ->label('Aktif')
                    ->default(true),
            ]);
    }
}
