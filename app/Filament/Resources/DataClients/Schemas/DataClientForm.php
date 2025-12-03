<?php

namespace App\Filament\Resources\DataClients\Schemas;

use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Schemas\Schema;

class DataClientForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('nama_brand')
                    ->required(),
                TextInput::make('produk'),
                TextInput::make('category'),
                TextInput::make('priority'),
                TextInput::make('website')
                    ->url(),
                TextInput::make('nama_pic'),
                TextInput::make('role_pic'),
                TextInput::make('email_pic')
                    ->email(),
                TextInput::make('status'),
                DatePicker::make('date_outreach'),
                DatePicker::make('date_follow_up'),
                Textarea::make('notes')
                    ->columnSpanFull(),
            ]);
    }
}
