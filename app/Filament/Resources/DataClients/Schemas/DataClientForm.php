<?php

namespace App\Filament\Resources\DataClients\Schemas;

use App\Models\BvSalesList;
use Filament\Forms\Components\DatePicker;
use Filament\Schemas\Components\Group;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
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
            // ─── Client Information ───────────────────────────────────────────
            Section::make('Client Information')
                ->description('Detail dasar mengenai client')
                ->schema([
                    Grid::make(2)->schema([
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
                            ->label('Nama Brand / Client')
                            ->required(),

                        // Nama Agency — muncul hanya ketika type = agency (DC-02)
                        TextInput::make('agency_name')
                            ->label('Nama Agency')
                            ->placeholder('Nama perusahaan agency...')
                            ->visible(fn(Get $get) => $get('type') === 'agency')
                            ->columnSpan(1),

                        TextInput::make('produk')
                            ->label('Produk'),
                        TextInput::make('category')
                            ->label('Kategori'),
                        TextInput::make('priority')
                            ->label('Prioritas'),
                        TextInput::make('website')
                            ->label('Website')
                            ->url(),
                        TextInput::make('parent_brand')
                            ->label('Parent Brand'),
                        TextInput::make('instagram')
                            ->label('Instagram'),
                        TextInput::make('tiktok')
                            ->label('TikTok'),
                        TextInput::make('top')
                            ->label('Term of Payment (hari)')
                            ->numeric()
                            ->suffix('hari'),
                    ]),
                ])
                ->collapsible(),

            // ─── PIC Section ─────────────────────────────────────────────────
            Section::make('PIC')
                ->description('Informasi Person in Charge')
                ->schema([

                    // DC-03: PIC Internal (Sales) — selalu tampil, pilih dari data sales BV
                    Select::make('pic_internal_sales_id')
                        ->label('PIC Internal (Sales)')
                        ->helperText('Sales person internal BV yang bertanggung jawab untuk client ini')
                        ->options(fn() => BvSalesList::orderBy('nama_sales')->pluck('nama_sales', 'id'))
                        ->searchable()
                        ->native(false)
                        ->nullable()
                        ->columnSpanFull(),

                    // DC-03: PIC sesuai Client Type — Direct
                    Grid::make(2)
                        ->schema([
                            TextInput::make('nama_pic')
                                ->label('Nama PIC Client'),
                            TextInput::make('role_pic')
                                ->label('Jabatan PIC'),
                            TextInput::make('email_pic')
                                ->email()
                                ->label('Email PIC')
                                ->columnSpanFull(),
                        ])
                        ->visible(fn(Get $get) => $get('type') !== 'agency'),

                    // DC-03: PIC sesuai Client Type — Agency (repeater)
                    Repeater::make('pics')
                        ->label('PIC Agency')
                        ->helperText('Daftar kontak dari Agency')
                        ->schema([
                            Grid::make(2)->schema([
                                TextInput::make('name')
                                    ->label('Nama')
                                    ->required(),
                                TextInput::make('wa_number')
                                    ->label('Nomor WhatsApp')
                                    ->tel()
                                    ->required(),
                                TextInput::make('email')
                                    ->email()
                                    ->label('Email'),
                                TextInput::make('role')
                                    ->label('Jabatan'),
                            ]),
                        ])
                        ->visible(fn(Get $get) => $get('type') === 'agency')
                        ->columnSpanFull()
                        ->addActionLabel('Tambah PIC Agency'),
                ])
                ->collapsible(),

            // ─── Tracking & Notes ────────────────────────────────────────────
            Section::make('Tracking & Catatan')
                ->description('Status, jadwal outreach, dan catatan tambahan')
                ->schema([
                    Grid::make(2)->schema([
                        Select::make('status')
                            ->options([
                                'Newest' => 'Newest',
                                'Number of Meeting' => 'Number of Meeting',
                                'Brief' => 'Brief',
                                'Waiting Feedback' => 'Waiting Feedback',
                                'Not Available' => 'Not Available',
                            ])
                            ->native(false),
                        TextInput::make('account_owner')
                            ->label('Account Owner'),
                        DatePicker::make('date_outreach')
                            ->label('Tanggal Outreach'),
                        DatePicker::make('date_follow_up')
                            ->label('Tanggal Follow Up'),
                    ]),
                    Textarea::make('notes')
                        ->label('Catatan')
                        ->columnSpanFull(),
                ])
                ->collapsible(),
        ];
    }
}
