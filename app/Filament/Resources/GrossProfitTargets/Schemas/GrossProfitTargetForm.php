<?php

namespace App\Filament\Resources\GrossProfitTargets\Schemas;

use Carbon\Carbon;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\RawJs;

class GrossProfitTargetForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Target Finance Bulanan')
                ->description('Set target Deal Revenue dan Gross Profit per bulan. Target Quarter dan Tahunan dihitung otomatis dari akumulasi data bulanan.')
                ->schema([
                    Select::make('year')
                        ->label('Tahun')
                        ->options(function () {
                            $current = now()->year;
                            $years = [];
                            for ($i = $current - 1; $i <= $current + 2; $i++) {
                                $years[$i] = (string) $i;
                            }
                            return $years;
                        })
                        ->default(now()->year)
                        ->required()
                        ->native(false)
                        ->columnSpan(1),

                    Select::make('month')
                        ->label('Bulan')
                        ->options(function () {
                            $months = [];
                            for ($i = 1; $i <= 12; $i++) {
                                $months[$i] = Carbon::createFromDate(null, $i, 1)->translatedFormat('F');
                            }
                            return $months;
                        })
                        ->required()
                        ->native(false)
                        ->columnSpan(1),

                    TextInput::make('target_deal_revenue')
                        ->label('Target Deal Revenue (Rp)')
                        ->prefix('Rp')
                        ->mask(RawJs::make('$money($input)'))
                        ->stripCharacters(',')
                        ->numeric()
                        ->required()
                        ->minValue(0)
                        ->helperText('Total omset/penjualan yang harus dicapai perusahaan bulan ini. Digunakan sebagai acuan distribusi target per sales.')
                        ->columnSpan(1),

                    TextInput::make('target_amount')
                        ->label('Target Gross Profit (Rp)')
                        ->prefix('Rp')
                        ->mask(RawJs::make('$money($input)'))
                        ->stripCharacters(',')
                        ->numeric()
                        ->required()
                        ->minValue(0)
                        ->helperText('Keuntungan bersih yang harus dicapai perusahaan bulan ini.')
                        ->columnSpan(1),

                    Textarea::make('notes')
                        ->label('Catatan')
                        ->placeholder('Tulis catatan opsional di sini...')
                        ->rows(2)
                        ->columnSpan(2),
                ])
                ->columns(2),
        ]);
    }
}
