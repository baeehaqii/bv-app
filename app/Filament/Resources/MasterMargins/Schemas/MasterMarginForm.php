<?php

namespace App\Filament\Resources\MasterMargins\Schemas;

use Filament\Schemas\Schema;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Support\RawJs;

class MasterMarginForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Margin Range Configuration')
                    ->description('Configure margin percentage based on budget amount ranges')
                    ->schema([
                        TextInput::make('name')
                            ->label('Range Name')
                            ->placeholder('e.g., Low Budget, Medium Budget, High Budget')
                            ->required()
                            ->maxLength(255)
                            ->unique(ignoreRecord: true)
                            ->columnSpan(2),

                        TextInput::make('order')
                            ->label('Order')
                            ->numeric()
                            ->default(0)
                            ->helperText('Display order (lower numbers appear first)')
                            ->required()
                            ->columnSpan(1),

                        TextInput::make('min_amount')
                            ->label('Minimum Amount')
                            ->prefix('Rp')
                            ->mask(RawJs::make('$money($input)'))
                            ->stripCharacters(',')
                            ->numeric()
                            ->required()
                            ->helperText('Minimum subtotal amount for this range')
                            ->columnSpan(1),

                        TextInput::make('max_amount')
                            ->label('Maximum Amount')
                            ->prefix('Rp')
                            ->mask(RawJs::make('$money($input)'))
                            ->stripCharacters(',')
                            ->numeric()
                            ->helperText('Maximum subtotal amount (leave empty for unlimited)')
                            ->columnSpan(1),

                        TextInput::make('margin_percent')
                            ->label('Margin Percentage')
                            ->suffix('%')
                            ->numeric()
                            ->required()
                            ->minValue(0)
                            ->maxValue(100)
                            ->step(0.01)
                            ->helperText('Margin percentage to apply for this range')
                            ->columnSpan(1),

                        Toggle::make('is_active')
                            ->label('Active')
                            ->default(true)
                            ->helperText('Only active ranges will be used in calculations')
                            ->columnSpan(1),
                    ])->columns(3),
            ]);
    }
}

