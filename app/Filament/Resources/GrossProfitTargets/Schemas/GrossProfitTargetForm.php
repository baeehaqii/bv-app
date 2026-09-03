<?php

namespace App\Filament\Resources\GrossProfitTargets\Schemas;

use Carbon\Carbon;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
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
                        ->live(onBlur: true)
                        ->columnSpan(1),

                    TextInput::make('margin_benchmark_percent')
                        ->label('Benchmark Margin (%)')
                        ->suffix('%')
                        ->numeric()
                        ->required()
                        ->default(31)
                        ->minValue(0)
                        ->maxValue(100)
                        ->live(onBlur: true)
                        ->helperText('Benchmark margin perusahaan. Default 31% sesuai sheet Sales Target.')
                        ->columnSpan(1),

                    Placeholder::make('target_amount_preview')
                        ->label('Target Gross Profit (otomatis)')
                        ->content(fn(Get $get) => 'Rp ' . number_format(
                            (float) $get('target_deal_revenue') * (float) $get('margin_benchmark_percent') / 100,
                            0,
                            ',',
                            '.'
                        ))
                        ->helperText('Dihitung dari Target Deal Revenue x Benchmark Margin.')
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
