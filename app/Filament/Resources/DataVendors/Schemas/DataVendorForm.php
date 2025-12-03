<?php

namespace App\Filament\Resources\DataVendors\Schemas;

use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Schemas\Schema;

class DataVendorForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('nama_vendor')
                    ->label('Nama Vendor')
                    ->required()
                    ->maxLength(255),
                TextInput::make('email_vendor')
                    ->label('Email Vendor')
                    ->email()
                    ->required()
                    ->maxLength(255),
                TextInput::make('nama_pic')
                    ->label('Nama PIC')
                    ->required()
                    ->maxLength(255),
                TextInput::make('no_ktp_pic')
                    ->label('No. KTP PIC')
                    ->required()
                    ->maxLength(255),
                TextInput::make('role_pic')
                    ->label('Role PIC')
                    ->required()
                    ->maxLength(255),
                TextInput::make('email_pic')
                    ->label('Email PIC')
                    ->email()
                    ->required()
                    ->maxLength(255),
                DatePicker::make('tanggal_registrasi')
                    ->label('Tanggal Registrasi')
                    ->required(),
                Select::make('status')
                    ->label('Status')
                    ->options([
                        'Active' => 'Active',
                        'Inactive' => 'Inactive',
                        'Pending' => 'Pending',
                        'Blocked' => 'Blocked',
                    ])
                    ->required(),
                Textarea::make('catatan')
                    ->label('Catatan')
                    ->rows(4)
                    ->columnSpanFull(),
            ]);
    }
}
