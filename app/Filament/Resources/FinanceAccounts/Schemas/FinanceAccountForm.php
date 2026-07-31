<?php

namespace App\Filament\Resources\FinanceAccounts\Schemas;

use App\Models\FinanceAccount;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class FinanceAccountForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Identitas Akun')
                    ->columns(2)
                    ->schema([
                        TextInput::make('name')
                            ->label('Nama Akun')
                            ->placeholder('mis. BCA Operasional')
                            ->required()
                            ->maxLength(255),

                        Select::make('type')
                            ->label('Jenis')
                            ->options(FinanceAccount::TYPES)
                            ->default('bank')
                            ->native(false)
                            ->required(),

                        TextInput::make('bank_name')->label('Nama Bank')->maxLength(255),
                        TextInput::make('account_number')->label('Nomor Rekening')->maxLength(255),
                        TextInput::make('account_holder')->label('Nama Pemilik Rekening')->maxLength(255),
                    ]),

                Section::make('Saldo Awal')
                    ->description('Saldo saat akun mulai dicatat di sistem. Saldo berjalan dihitung otomatis dari arus kas — tidak perlu diubah manual.')
                    ->columns(2)
                    ->schema([
                        TextInput::make('opening_balance')
                            ->label('Saldo Awal')
                            ->prefix('Rp')
                            ->numeric()
                            ->default(0)
                            ->required(),

                        DatePicker::make('opening_date')
                            ->label('Tanggal Saldo Awal')
                            ->native(false),
                    ]),

                Section::make('Status')
                    ->columns(2)
                    ->schema([
                        Toggle::make('is_default')
                            ->label('Akun Default')
                            ->helperText('Tujuan pencatatan otomatis (pembayaran KOL, pembayaran invoice) bila akun tidak disebut. Hanya boleh satu.'),

                        Toggle::make('is_active')
                            ->label('Aktif')
                            ->default(true),

                        Textarea::make('notes')->label('Catatan')->rows(2)->columnSpanFull(),
                    ]),
            ]);
    }
}
