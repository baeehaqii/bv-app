<?php

namespace App\Filament\Resources\BvSalesLists\Schemas;

use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\DatePicker;
use Filament\Schemas\Schema;

class BvSalesListForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('nama_sales')
                    ->required(),
                TextInput::make('alamat'),
                DatePicker::make('tanggal_gabung_bv'),
                TextInput::make('keterangan'),
            ]);
    }
}
