<?php

namespace App\Filament\Resources\BvCampignUpcomings\Schemas;

use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;


class BvCampignUpcomingForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Campaign Details')
                    ->schema([
                        Select::make('client_id')
                            ->relationship('client', 'name') // Assuming 'name' exists on DataClient
                            ->searchable()
                            ->preload()
                            ->label('Brand / Client'),
                        TextInput::make('campaign_name')
                            ->required()
                            ->label('Campaign Name'),
                        FileUpload::make('campaign_image')
                            ->image()
                            ->directory('campaign-images'),
                        Select::make('status')
                            ->options([
                                'forecasted' => 'Forecasted',
                                'upcoming' => 'Upcoming',
                                'ongoing' => 'Ongoing',
                                'completed' => 'Completed',
                                'cancelled' => 'Cancelled',
                            ])
                            ->default('forecasted')
                            ->required(),
                        Textarea::make('description')
                            ->columnSpanFull(),
                    ])->columns(2),

                Section::make('Forecasting & Metrics')
                    ->schema([
                        TextInput::make('budget_allocated')
                            ->required()
                            ->numeric()
                            ->prefix('IDR')
                            ->label('Budget Allocated'),
                        TextInput::make('pot_cpv')
                            ->numeric()
                            ->prefix('IDR')
                            ->label('Potential CPV'),
                        TextInput::make('pot_cpe')
                            ->numeric()
                            ->prefix('IDR')
                            ->label('Potential CPE'),
                        TextInput::make('pot_views')
                            ->numeric()
                            ->label('Potential Views'),
                    ])->columns(2),

                Section::make('Scheduling')
                    ->schema([
                        DatePicker::make('start_date'),
                        DatePicker::make('end_date'),
                    ])->columns(2),
            ]);
    }
}
