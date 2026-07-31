<?php

namespace App\Filament\Resources\BvInvoices\Schemas;

use App\Models\BvInvoice;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class BvInvoiceForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Tagihan')
                    ->columns(2)
                    ->schema([
                        TextInput::make('invoice_number')
                            ->label('No. Invoice')
                            ->default(fn() => BvInvoice::generateNumber())
                            ->required()
                            ->unique(ignoreRecord: true)
                            ->maxLength(255),

                        Select::make('bv_quotation_id')
                            ->label('Quotation')
                            ->relationship('quotation', 'quotation_number')
                            ->searchable()
                            ->preload()
                            ->placeholder('— tanpa quotation —'),

                        TextInput::make('client_name')
                            ->label('Nama Client')
                            ->required()
                            ->maxLength(255),

                        Select::make('data_client_id')
                            ->label('Client di Database')
                            ->relationship('dataClient', 'nama_brand')
                            ->searchable()
                            ->preload()
                            ->placeholder('— tidak ditautkan —'),

                        TextInput::make('term_label')
                            ->label('Keterangan Termin')
                            ->placeholder('mis. DP 50%, Pelunasan')
                            ->maxLength(255),

                        TextInput::make('amount')
                            ->label('Nilai Invoice')
                            ->prefix('Rp')
                            ->numeric()
                            ->minValue(1)
                            ->required(),

                        DatePicker::make('issue_date')
                            ->label('Tanggal Invoice')
                            ->native(false)
                            ->default(now())
                            ->required(),

                        DatePicker::make('due_date')
                            ->label('Jatuh Tempo')
                            ->native(false)
                            ->default(now()->addDays(14))
                            ->required(),

                        Select::make('status')
                            ->label('Status')
                            ->options(BvInvoice::STATUSES)
                            ->default('draft')
                            ->native(false)
                            ->required(),
                    ]),

                Section::make('Pembayaran')
                    ->description('Isi hanya bila uangnya sudah diterima. Pembayaran otomatis masuk Cashflow pada pos SAK "Penerimaan dari Pelanggan". Lebih aman pakai aksi "Catat Pembayaran" di daftar invoice.')
                    ->columns(2)
                    ->schema([
                        TextInput::make('paid_amount')
                            ->label('Nominal Diterima')
                            ->prefix('Rp')
                            ->numeric(),

                        DatePicker::make('paid_at')
                            ->label('Tanggal Kas Diterima')
                            ->native(false),

                        Select::make('finance_account_id')
                            ->label('Masuk ke Akun')
                            ->relationship('financeAccount', 'name')
                            ->searchable()
                            ->preload(),

                        Textarea::make('notes')->label('Catatan')->rows(2)->columnSpanFull(),
                    ]),
            ]);
    }
}
