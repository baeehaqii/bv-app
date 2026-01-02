<?php

namespace App\Filament\Forms;

use App\Enums\SalesStatus;
use App\Models\BvSalesList;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Grid;

class BvSalesForm
{
    public static function getFormComponents(): array
    {
        return [
            Grid::make(2)
                ->schema([
                    TextInput::make('event_name')
                        ->label('Event/Campaign Name')
                        ->placeholder('e.g. Campaign Ramadan 2026')
                        ->required()
                        ->maxLength(255),

                    Select::make('bv_sales_list_id')
                        ->label('Sales Name')
                        ->relationship('salesList', 'nama_sales')
                        ->searchable()
                        ->preload()
                        ->required(),

                    TextInput::make('company_name')
                        ->label('Company Name')
                        ->placeholder('e.g. PT. Contoh Perusahaan')
                        ->maxLength(255),

                    Select::make('campaign_items')
                        ->label('Campaign Items')
                        ->multiple()
                        ->options([
                            'Active Services' => [
                                'influencer' => 'Influencer',
                                'social_media_mgmt' => 'Social Media Management',
                            ],
                            'Coming Soon' => [
                                'affiliate' => 'Affiliate (Soon)',
                                'smm' => 'SMM (Soon)',
                                'tiktok_clippers' => 'TikTok Clipper (Soon)',
                                'digital_video' => 'Digital Video (Soon)',
                            ],
                        ])
                        ->searchable(),

                    TextInput::make('deal_value')
                        ->label('Deal Value')
                        ->prefix('Rp')
                        ->mask(\Filament\Support\RawJs::make(<<<'JS'
                            $money($input, ',', '.', 0)
                        JS))
                        ->stripCharacters(['.'])
                        ->dehydrateStateUsing(fn($state) => (int) str_replace('.', '', $state))
                        ->default(0),

                    TextInput::make('margin')
                        ->label('Margin (%)')
                        ->numeric()
                        ->suffix('%')
                        ->default(0),

                    Select::make('campaign_periode')
                        ->label('Campaign Period')
                        ->placeholder('e.g. Jan - Mar')
                        ->options([
                            'q0' => 'Q0',
                            'q1' => 'Q1',
                            'q2' => 'Q2',
                            'q3' => 'Q3',
                            'q4' => 'Q4',
                        ]),

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
                        ->native(false)
                        ->placeholder('Select a date')
                        ->label('Close Date'),

                    Select::make('status')
                        ->label('Status')
                        ->options(SalesStatus::toArray())
                        ->default(SalesStatus::PITCHING->value)
                        ->required(),

                    Textarea::make('comments')
                        ->label('Comments')
                        ->placeholder('Masukan komentar jika ada...')
                        ->rows(2)
                        ->columnSpan(2),

                    Textarea::make('detail')
                        ->label('Detail')
                        ->placeholder('Masukan detail jika ada...')
                        ->rows(3)
                        ->columnSpan(2),
                ]),
        ];
    }
}
