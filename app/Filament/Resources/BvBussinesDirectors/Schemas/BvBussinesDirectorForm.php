<?php

namespace App\Filament\Resources\BvBussinesDirectors\Schemas;

use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TagsInput;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class BvBussinesDirectorForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('nama_lengkap')
                    ->label('Nama Lengkap')
                    ->required()
                    ->maxLength(255),

                TextInput::make('alamat_email')
                    ->label('Alamat Email')
                    ->email()
                    ->required()
                    ->maxLength(255),

                TextInput::make('no_wa')
                    ->label('No WA')
                    ->required()
                    ->maxLength(30),

                DatePicker::make('tanggal_gabung')
                    ->label('Tanggal Gabung')
                    ->native(false),

                Select::make('status')
                    ->options([
                        'aktif' => 'Aktif',
                        'tidak_aktif' => 'Tidak Aktif',
                    ])
                    ->required()
                    ->default('aktif')
                    ->native(false),

                TagsInput::make('list_sales')
                    ->label('List Sales')
                    ->disabled()
                    ->dehydrated(false)
                    ->placeholder('Akan terisi otomatis dari data Sales List'),
            ]);
    }
}
