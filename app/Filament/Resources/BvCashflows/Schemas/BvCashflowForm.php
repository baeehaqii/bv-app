<?php

namespace App\Filament\Resources\BvCashflows\Schemas;

use App\Models\BvCashflow;
use App\Models\FinanceAccount;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;

class BvCashflowForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Informasi Transaksi')
                    ->description('Pos akun & klasifikasi aktivitas mengikuti Standar Akuntansi Keuangan (PSAK 2 — Laporan Arus Kas).')
                    ->columns(2)
                    ->schema([
                        DatePicker::make('transaction_date')
                            ->label('Tanggal Transaksi')
                            ->helperText('Tanggal kas benar-benar diterima / dikeluarkan.')
                            ->native(false)
                            ->default(now())
                            ->required(),

                        Select::make('type')
                            ->label('Jenis Arus Kas')
                            ->options(['income' => 'Penerimaan Kas', 'expense' => 'Pengeluaran Kas'])
                            ->native(false)
                            ->required()
                            ->live()
                            // Pos akun terikat jenis kas — ganti jenis, pilihan lama tidak valid lagi.
                            ->afterStateUpdated(fn(Set $set) => $set('category', null)),

                        Select::make('category')
                            ->label('Pos Akun (SAK)')
                            ->options(fn(Get $get) => BvCashflow::optionsFor($get('type')))
                            ->helperText(fn(Get $get) => filled($get('category'))
                                ? 'Klasifikasi: ' . (BvCashflow::ACTIVITIES[BvCashflow::activityOf($get('category'))] ?? '—')
                                : 'Pilih jenis arus kas dulu.')
                            ->native(false)
                            ->searchable()
                            ->required()
                            ->live(),

                        TextInput::make('amount')
                            ->label('Nominal')
                            ->prefix('Rp')
                            ->numeric()
                            ->minValue(1)
                            ->required(),

                        Select::make('payment_method')
                            ->label('Metode Pembayaran')
                            ->options(BvCashflow::PAYMENT_METHODS)
                            ->default('transfer')
                            ->native(false)
                            ->required(),

                        Select::make('finance_account_id')
                            ->label('Akun Kas / Bank')
                            ->relationship('financeAccount', 'name')
                            ->searchable()
                            ->preload()
                            ->default(fn() => FinanceAccount::defaultId())
                            ->helperText('Menentukan saldo akun mana yang berubah.'),

                        Select::make('data_client_id')
                            ->label('Client Terkait')
                            ->relationship('dataClient', 'nama_brand')
                            ->searchable()
                            ->preload()
                            ->placeholder('— tidak terkait client —'),
                    ]),

                Section::make('Bukti & Keterangan')
                    ->columns(2)
                    ->schema([
                        TextInput::make('reference_no')
                            ->label('No. Invoice / Kwitansi')
                            ->maxLength(255),

                        FileUpload::make('attachment')
                            ->label('Bukti Transaksi')
                            ->directory('cashflow')
                            ->disk('public')
                            ->downloadable()
                            ->openable(),

                        Textarea::make('description')
                            ->label('Keterangan')
                            ->rows(2)
                            ->columnSpanFull(),
                    ]),
            ]);
    }
}
