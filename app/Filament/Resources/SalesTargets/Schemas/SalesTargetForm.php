<?php

namespace App\Filament\Resources\SalesTargets\Schemas;

use App\Models\BvSalesList;
use Carbon\Carbon;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\RawJs;

class SalesTargetForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Target Per Sales')
                ->description('Set nominal target deal value per bulan untuk setiap sales person. Target Quarter dan Tahunan dihitung otomatis dari akumulasi data bulanan.')
                ->schema([
                    Select::make('bv_sales_list_id')
                        ->label('Nama Sales')
                        ->options(fn() => BvSalesList::orderBy('nama_sales')->pluck('nama_sales', 'id'))
                        ->searchable()
                        ->required()
                        ->native(false)
                        ->columnSpan(2),

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

                    TextInput::make('target_amount')
                        ->label('Target Deal Value (Rp)')
                        ->prefix('Rp')
                        ->mask(RawJs::make('$money($input)'))
                        ->stripCharacters(',')
                        ->numeric()
                        ->required()
                        ->minValue(0)
                        ->helperText('Nominal target deal value untuk sales ini di bulan yang dipilih.')
                        ->columnSpan(2),

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
