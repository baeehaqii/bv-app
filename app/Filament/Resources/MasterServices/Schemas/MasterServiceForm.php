<?php

namespace App\Filament\Resources\MasterServices\Schemas;

use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class MasterServiceForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Informasi Service')
                    ->description('Konfigurasi master data service yang tersedia')
                    ->schema([
                        TextInput::make('nama_service')
                            ->label('Nama Service')
                            ->placeholder('Contoh: Influencer, SMM, Digital Video')
                            ->required()
                            ->maxLength(255)
                            ->columnSpan(2),

                        TextInput::make('kode_service')
                            ->label('Kode Service')
                            ->placeholder('Contoh: INF, AFF, SMM')
                            ->maxLength(50)
                            ->unique(ignoreRecord: true)
                            ->helperText('Kode unik untuk identifikasi service (opsional)')
                            ->columnSpan(1),

                        TextInput::make('urutan')
                            ->label('Urutan')
                            ->numeric()
                            ->default(0)
                            ->helperText('Urutan tampil (angka kecil tampil duluan)')
                            ->required()
                            ->columnSpan(1),

                        Textarea::make('deskripsi')
                            ->label('Deskripsi')
                            ->placeholder('Deskripsi singkat tentang service ini...')
                            ->rows(3)
                            ->columnSpan(2),

                        Toggle::make('is_active')
                            ->label('Aktif')
                            ->default(true)
                            ->helperText('Service yang nonaktif tidak akan muncul di pilihan')
                            ->columnSpan(1),

                        Toggle::make('is_coming_soon')
                            ->label('Coming Soon')
                            ->default(false)
                            ->helperText('Tandai jika service belum tersedia')
                            ->columnSpan(1),
                    ])->columns(2)
                    ->columnSpanFull(),
            ]);
    }
}
