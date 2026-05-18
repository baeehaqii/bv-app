<?php

namespace App\Filament\Resources\MasterSows\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class MasterSowForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Informasi SOW')
                ->description('Scope of Work yang tersedia untuk rate card KOL')
                ->schema([
                    Grid::make(2)->schema([
                        TextInput::make('name')
                            ->label('Nama SOW')
                            ->placeholder('e.g. IG Reels, TikTok Video')
                            ->required()
                            ->maxLength(255),

                        Select::make('channel')
                            ->label('Channel / Platform')
                            ->options([
                                'instagram' => 'Instagram',
                                'tiktok'    => 'TikTok',
                                'youtube'   => 'YouTube',
                                'twitter'   => 'Twitter / X',
                                'threads'   => 'Threads',
                                'facebook'  => 'Facebook',
                                'other'     => 'Lainnya',
                            ])
                            ->native(false)
                            ->placeholder('Pilih platform')
                            ->nullable(),
                    ]),

                    Grid::make(2)->schema([
                        TextInput::make('code')
                            ->label('Kode SOW')
                            ->placeholder('e.g. ig_reels')
                            ->maxLength(50)
                            ->unique(ignoreRecord: true)
                            ->helperText('Kode unik (opsional), huruf kecil, underscore'),

                        TextInput::make('sort_order')
                            ->label('Urutan Tampil')
                            ->numeric()
                            ->default(0),
                    ]),

                    Textarea::make('description')
                        ->label('Deskripsi')
                        ->placeholder('Keterangan singkat SOW ini...')
                        ->rows(2)
                        ->columnSpanFull(),

                    Grid::make(2)->schema([
                        Toggle::make('is_active')
                            ->label('Aktif')
                            ->default(true)
                            ->helperText('SOW nonaktif tidak muncul di pilihan rate card'),

                        Toggle::make('is_custom')
                            ->label('Tandai sebagai "Custom"')
                            ->default(false)
                            ->helperText('Jika aktif, SOW ini muncul sebagai opsi "Custom / Tulis Manual"'),
                    ]),
                ]),
        ]);
    }
}
