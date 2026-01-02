<?php

namespace App\Filament\Resources\BvSales\Schemas;

use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class BvSalesForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('nama_sales')
                    ->required(),
            ]);
    }
}
