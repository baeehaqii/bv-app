<?php

namespace App\Filament\Resources\BvEmployes\Schemas;

use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class BvEmployeForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('nama_lengkap')
                    ->required(),
                TextInput::make('email')
                    ->label('Email address')
                    ->email()
                    ->required(),
                TextInput::make('whatsapp')
                    ->required(),
                TextInput::make('alamat')
                    ->required(),
                TextInput::make('kota')
                    ->required(),
                TextInput::make('provinsi')
                    ->required(),
                TextInput::make('kode_pos')
                    ->required(),
                TextInput::make('divis')
                    ->required(),
                TextInput::make('photo'),
            ]);
    }
}
