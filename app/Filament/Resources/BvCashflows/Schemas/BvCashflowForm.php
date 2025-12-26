<?php

namespace App\Filament\Resources\BvCashflows\Schemas;

use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Schemas\Schema;

class BvCashflowForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                DatePicker::make('transaction_date')
                    ->required(),
                Select::make('type')
                    ->options(['income' => 'Income', 'expense' => 'Expense'])
                    ->required(),
                TextInput::make('amount')
                    ->required()
                    ->numeric(),
                TextInput::make('category')
                    ->required(),
                TextInput::make('reference_no'),
                Textarea::make('description')
                    ->columnSpanFull(),
                TextInput::make('payment_method')
                    ->required()
                    ->default('transfer'),
                TextInput::make('attachment'),
            ]);
    }
}
