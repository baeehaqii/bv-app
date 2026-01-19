<?php

namespace App\Filament\Resources\BvSales\Schemas;

use App\Enums\SalesStatus;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class BvSalesForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components(static::getFormComponents());
    }

    public static function getFormComponents(): array
    {
        return [
            TextInput::make('nama_sales')
                ->label('Sales Name')
                ->required()
                ->maxLength(255),

            TextInput::make('company_name')
                ->label('Company Name')
                ->maxLength(255),

            TextInput::make('campaign_items')
                ->label('Campaign Items')
                ->maxLength(255),

            TextInput::make('deal_value')
                ->label('Deal Value')
                ->numeric()
                ->prefix('Rp')
                ->default(0),

            TextInput::make('margin')
                ->label('Margin (%)')
                ->numeric()
                ->suffix('%')
                ->default(0),

            TextInput::make('campaign_periode')
                ->label('Campaign Period')
                ->placeholder('e.g. Jan - Mar')
                ->maxLength(255),

            Select::make('campaign_year')
                ->label('Campaign Year')
                ->options(function () {
                    $currentYear = now()->year;
                    $years = [];
                    for ($i = $currentYear - 2; $i <= $currentYear + 2; $i++) {
                        $years[$i] = (string) $i;
                    }
                    return $years;
                })
                ->default(now()->year),

            DatePicker::make('close_date')
                ->label('Close Date'),

            Select::make('status')
                ->label('Status')
                ->options(SalesStatus::toArray())
                ->default(SalesStatus::BRIEFING->value)
                ->required(),

            Textarea::make('comments')
                ->label('Comments')
                ->rows(2),

            Textarea::make('detail')
                ->label('Detail')
                ->rows(3),
        ];
    }
}
