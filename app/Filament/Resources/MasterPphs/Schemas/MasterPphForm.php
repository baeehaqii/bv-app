<?php

namespace App\Filament\Resources\MasterPphs\Schemas;

use Filament\Schemas\Schema;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;

class MasterPphForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('PPH Configuration')
                    ->description('Configure Gross Up PPH coefficients and tax settings')
                    ->schema([
                        TextInput::make('name')
                            ->label('Name')
                            ->placeholder('e.g., Pribadi, PT Non PKP, PT PKP, CV')
                            ->required()
                            ->maxLength(255)
                            ->unique(ignoreRecord: true)
                            ->columnSpan(2),

                        Select::make('entity_type')
                            ->label('Entity Type')
                            ->options([
                                'Pribadi' => 'Pribadi',
                                'PT' => 'PT (Perseroan Terbatas)',
                                'CV' => 'CV (Commanditaire Vennootschap)',
                            ])
                            ->required()
                            ->columnSpan(1),

                        TextInput::make('order')
                            ->label('Order')
                            ->numeric()
                            ->default(0)
                            ->helperText('Display order (lower numbers appear first)')
                            ->required()
                            ->columnSpan(1),

                        TextInput::make('coefficient')
                            ->label('PPH Coefficient')
                            ->numeric()
                            ->step(0.001)
                            ->minValue(0)
                            ->maxValue(1)
                            ->required()
                            ->helperText('e.g., 0.975 for Pribadi, 0.98 for PT, 0.995 for CV')
                            ->columnSpan(1),

                        Toggle::make('include_ppn')
                            ->label('Include PPN?')
                            ->helperText('Enable for PT PKP (PPN will be added to coefficient)')
                            ->live()
                            ->columnSpan(1),

                        TextInput::make('ppn_percent')
                            ->label('PPN Percentage')
                            ->suffix('%')
                            ->numeric()
                            ->step(0.01)
                            ->minValue(0)
                            ->maxValue(100)
                            ->default(11)
                            ->visible(fn(callable $get) => $get('include_ppn'))
                            ->required(fn(callable $get) => $get('include_ppn'))
                            ->helperText('Standard PPN is 11%')
                            ->columnSpan(1),

                        Textarea::make('description')
                            ->label('Description')
                            ->rows(3)
                            ->placeholder('Additional notes or formula explanation')
                            ->columnSpanFull(),

                        Toggle::make('is_active')
                            ->label('Aktif')
                            ->default(true)
                            ->helperText('Hanya pilihan aktif yang muncul di form')
                            ->columnSpan(1),

                        Toggle::make('is_default')
                            ->label('Jadikan default')
                            ->helperText('Tipe pajak yang otomatis dipakai KOL baru di Media Plan Internal. Hanya boleh satu — menandai di sini otomatis melepas yang lain.')
                            ->columnSpan(1),
                    ])->columns(2),
            ]);
    }
}

