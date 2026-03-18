<?php

namespace App\Filament\Resources\BvSalesLists\Schemas;

use App\Models\BvBussinesDirector;
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
                Select::make('bv_bussines_director_id')
                    ->label('Business Director')
                    ->options(fn() => BvBussinesDirector::where('status', 'aktif')->orderBy('nama_lengkap')->pluck('nama_lengkap', 'id'))
                    ->searchable()
                    ->preload()
                    ->nullable(),
                TextInput::make('alamat'),
                DatePicker::make('tanggal_gabung_bv'),
                TextInput::make('keterangan'),
            ]);
    }
}
