<?php

namespace App\Filament\Resources\DataClients\Schemas;

use Filament\Forms\Components\DatePicker;
use Filament\Schemas\Components\Group;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;

class DataClientForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components(self::getFormSchema());
    }

    public static function getFormSchema(): array
    {
        return [
            \Filament\Schemas\Components\Section::make('Client Information')
                ->description('Basic details about the client')
                ->schema([
                    \Filament\Schemas\Components\Grid::make(2)
                        ->schema([
                            Select::make('type')
                                ->label('Client Type')
                                ->options([
                                    'direct' => 'Direct Brand',
                                    'agency' => 'Agency',
                                ])
                                ->default('direct')
                                ->live()
                                ->native(false)
                                ->required(),

                            TextInput::make('nama_brand')
                                ->required(),
                            TextInput::make('produk'),
                            TextInput::make('category'),
                            TextInput::make('priority'),
                            TextInput::make('website')
                                ->url(),
                        ]),
                ])
                ->collapsible(),

            \Filament\Schemas\Components\Section::make('PIC Details')
                ->description('Person in Charge information')
                ->schema([
                    // Direct Client PIC fields
                    \Filament\Schemas\Components\Grid::make(2)
                        ->schema([
                            TextInput::make('nama_pic')
                                ->label('PIC Name'),
                            TextInput::make('role_pic')
                                ->label('PIC Role'),
                            TextInput::make('email_pic')
                                ->email()
                                ->label('PIC Email')
                                ->columnSpanFull(),
                        ])
                        ->visible(fn(Get $get) => $get('type') !== 'agency'),

                    // Agency PICs Repeater
                    Repeater::make('pics')
                        ->label('Agency PICs')
                        ->schema([
                            \Filament\Schemas\Components\Grid::make(2)
                                ->schema([
                                    TextInput::make('name')->required(),
                                    TextInput::make('wa_number')
                                        ->label('WhatsApp Number')
                                        ->tel()
                                        ->required(),
                                    TextInput::make('email')
                                        ->email(),
                                    TextInput::make('role'),
                                ]),
                        ])
                        ->visible(fn(Get $get) => $get('type') === 'agency')
                        ->columnSpanFull()
                        ->addActionLabel('Add New PIC'),
                ])
                ->collapsible(),

            \Filament\Schemas\Components\Section::make('Tracking & Notes')
                ->description('Status tracking and additional notes')
                ->schema([
                    \Filament\Schemas\Components\Grid::make(2)
                        ->schema([
                            Select::make('status')
                                ->options([
                                    'Newest' => 'Newest',
                                    'Number of Meeting' => 'Number of Meeting',
                                    'Brief' => 'Brief',
                                    'Waiting Feedback' => 'Waiting Feedback',
                                    'Not Available' => 'Not Available',
                                ])
                                ->native(false),

                            DatePicker::make('date_outreach'),
                            DatePicker::make('date_follow_up'),
                        ]),
                    Textarea::make('notes')
                        ->columnSpanFull(),
                ])
                ->collapsible(),
        ];
    }
}
