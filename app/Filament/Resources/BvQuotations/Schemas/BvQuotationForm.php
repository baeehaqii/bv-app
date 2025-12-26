<?php

namespace App\Filament\Resources\BvQuotations\Schemas;

use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Schemas\Schema;

class BvQuotationForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('quotation_number')
                    ->required(),
                DatePicker::make('quotation_date')
                    ->required(),
                DatePicker::make('expiry_date'),
                TextInput::make('client_name')
                    ->required(),
                TextInput::make('client_email')
                    ->email(),
                Textarea::make('client_address')
                    ->columnSpanFull(),
                TextInput::make('subtotal')
                    ->required()
                    ->numeric()
                    ->default(0.0),
                TextInput::make('discount')
                    ->required()
                    ->numeric()
                    ->default(0.0),
                TextInput::make('total_amount')
                    ->required()
                    ->numeric()
                    ->default(0.0),
                Select::make('status')
                    ->options([
            'draft' => 'Draft',
            'sent' => 'Sent',
            'accepted' => 'Accepted',
            'rejected' => 'Rejected',
            'expired' => 'Expired',
        ])
                    ->default('draft')
                    ->required(),
                Textarea::make('notes')
                    ->columnSpanFull(),
                Textarea::make('terms_conditions')
                    ->columnSpanFull(),
                TextInput::make('user_id')
                    ->required()
                    ->numeric(),
            ]);
    }
}
