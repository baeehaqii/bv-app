<?php

namespace App\Filament\Resources\BvSalesLists\Schemas;

use App\Models\BvBussinesDirector;
use App\Models\User;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Schemas\Schema;

class BvSalesListForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('nama_sales')
                    ->required(),
                Select::make('user_id')
                    ->label('Akun User')
                    ->helperText('Hubungkan sales person ini ke akun login mereka, agar mereka bisa melihat target mereka sendiri.')
                    ->options(fn() => User::orderBy('name')->pluck('name', 'id'))
                    ->searchable()
                    ->preload()
                    ->nullable(),
                Select::make('bv_bussines_director_id')
                    ->label('Business Director')
                    ->options(fn() => BvBussinesDirector::orderBy('nama_lengkap')->pluck('nama_lengkap', 'id'))
                    ->searchable()
                    ->preload()
                    ->nullable(),
                TextInput::make('alamat'),
                DatePicker::make('tanggal_gabung_bv'),
                TextInput::make('keterangan'),
            ]);
    }
}
