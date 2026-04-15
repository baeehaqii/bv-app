<?php

namespace App\Filament\Resources\DataClients\Schemas;

use App\Enums\SalesStatus;
use App\Models\BvSalesList;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Repeater\TableColumn;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;

class DataClientForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->columns(1)->components(self::getFormSchema());
    }

    public static function getFormSchema(): array
    {
        return [
            // ─── Client Information ───────────────────────────────────────────
            Section::make('Client Information')
                ->description('Detail dasar mengenai client')
                ->schema([
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
                        ->label('Nama Brand')->placeholder('Masukan nama brand...')
                        ->visible(fn(Get $get) => $get('type') === 'agency')
                        ->required(fn(Get $get) => $get('type') === 'agency'),

                    Select::make('category')
                        ->label('Kategori')->required()
                        ->options([
                            'FMCG' => 'FMCG',
                            'E-Commerce & Tech' => 'E-Commerce & Tech',
                            'Fintech & Banking' => 'Fintech & Banking',
                            'Beauty & Skincare' => 'Beauty & Skincare',
                            'Automotive' => 'Automotive',
                            'Telecommunication' => 'Telecommunication',
                            'Pharmaceuticals' => 'Pharmaceuticals',
                            'Retail & Fashion' => 'Retail & Fashion',
                        ])
                        ->searchable()
                        ->native(false)
                        ->createOptionForm([
                            TextInput::make('category')
                                ->label('Kategori Baru')
                                ->required(),
                        ])
                        ->createOptionUsing(fn(array $data): string => $data['category'])
                        ->createOptionAction(fn($action) => $action->label('Tambah Kategori')),
                    Select::make('priority')
                        ->label('Prioritas')->required()
                        ->options([
                            'P0' => 'P0',
                            'P1' => 'P1',
                            'P2' => 'P2',
                            'P3' => 'P3',
                        ])
                        ->searchable()
                        ->native(false),
                    TextInput::make('website')
                        ->label('Website')->placeholder('https://www.contohwebsite.com')
                        ->url()
                        ->required(fn(Get $get) => $get('type') === 'direct'),
                    TextInput::make('parent_brand')
                        ->placeholder('Jika ini sub-brand, isi nama brand induknya')
                        ->label('Parent Brand')
                        ->required(fn(Get $get) => $get('type') === 'direct'),
                    TextInput::make('instagram')
                        ->label('Instagram')->placeholder('@contohbrand')->required(),
                    TextInput::make('tiktok')->placeholder('@contohbrand')
                        ->label('TikTok'),
                    TextInput::make('youtube')->placeholder('@contohchannel')
                        ->label('YouTube'),
                    TextInput::make('threads')->placeholder('@contohbrand')
                        ->label('Threads'),
                    TextInput::make('top')
                        ->label('Term of Payment (hari)')
                        ->numeric()->placeholder('Term of Payment')
                        ->suffix('hari'),
                    Select::make(name: 'status_client')
                        ->label('Status Client')->required()
                        ->options([
                            'Active' => 'Active',
                            'Inactive' => 'Inactive',
                        ]),
                    Toggle::make('has_agency')
                        ->label('Memiliki Agency?')
                        ->visible(fn(Get $get) => $get('type') === 'direct')
                        ->live()
                ])
                ->collapsible()->columns(3),

            // ─── Detail Agency ───────────────────────────────────────────────
            Section::make('Detail Agency')
                ->description('Daftar agency beserta informasi PIC masing-masing')
                ->visible(fn(Get $get) => $get('type') === 'agency' || ($get('type') === 'direct' && (bool) $get('has_agency')))
                ->schema([
                    Repeater::make('pics')
                        ->hiddenLabel()
                        ->schema([
                            TextInput::make('agency')
                                ->label('Nama Agency')
                                ->placeholder('Nama agency...')
                                ->required(),
                            TextInput::make('name')
                                ->label('Nama PIC Agency')
                                ->placeholder('Nama PIC...')
                                ->required(),
                            TextInput::make('email')
                                ->label('Email Agency')
                                ->email()
                                ->placeholder('email@contoh.com')
                                ->required(),
                            TextInput::make('wa_number')
                                ->label('No WhatsApp')
                                ->tel()
                                ->placeholder('081234567890')
                                ->required(),
                            Textarea::make('description')
                                ->label('Deskripsi')
                                ->placeholder('Deskripsi PIC...')
                                ->rows(1),
                        ])
                        ->table([
                            TableColumn::make('Nama Agency'),
                            TableColumn::make('Nama PIC Agency'),
                            TableColumn::make('Email Agency'),
                            TableColumn::make('No WhatsApp'),
                            TableColumn::make('Deskripsi'),
                        ])
                        ->addActionLabel('Tambah Agency')
                        ->defaultItems(0)
                        ->reorderable(false)
                        ->columnSpanFull(),
                ])
                ->collapsible(),

            // ─── PIC Section ─────────────────────────────────────────────────
            Section::make('PIC')
                ->description('Informasi Person in Charge')
                ->schema([
                    Select::make('pic_internal_sales_id')
                        ->label('PIC Internal (Sales)')
                        ->options(fn() => BvSalesList::orderBy('nama_sales')->pluck('nama_sales', 'id'))
                        ->searchable()
                        ->native(false)
                        ->nullable(),

                    Repeater::make('pic_clients')
                        ->hiddenLabel()
                        ->schema([
                            TextInput::make('name')
                                ->label('Nama PIC Client')
                                ->placeholder('Nama PIC Client...')
                                ->required(),
                            TextInput::make('role')
                                ->label('Jabatan')
                                ->placeholder('Jabatan PIC...'),
                            TextInput::make('email')
                                ->label('Email PIC Client')
                                ->email()
                                ->placeholder('email@contoh.com')
                                ->required(),
                            TextInput::make('wa_number')
                                ->label('No WhatsApp PIC Client')
                                ->tel()
                                ->placeholder('081234567890'),
                            TextInput::make('pic_leads')
                                ->label('PIC Leads')
                                ->placeholder('Nama leads terkait...'),
                        ])
                        ->table([
                            TableColumn::make('Nama PIC Client'),
                            TableColumn::make('Jabatan'),
                            TableColumn::make('Email PIC Client'),
                            TableColumn::make('No WhatsApp PIC Client'),
                            TableColumn::make('PIC Leads'),
                        ])
                        ->addActionLabel('Tambah PIC Client')
                        ->defaultItems(0)
                        ->reorderable(false)
                        ->columnSpanFull(),
                ])
                ->collapsible(),

            // ─── Tracking & Notes ────────────────────────────────────────────
            Section::make('Tracking & Catatan')
                ->description('Status, jadwal outreach, dan catatan tambahan')
                ->schema([
                    Select::make('status')
                        ->label('Status Campaign')->required()
                        ->options(fn() => collect(SalesStatus::cases())->mapWithKeys(
                            fn(SalesStatus $s) => [$s->value => $s->getLabel()]
                        ))
                        ->default(SalesStatus::NOT_STARTED->value)
                        ->live()
                        ->native(false),
                    DatePicker::make('date_outreach')
                        ->label('Tanggal Outreach')->required()->default(now()),
                    DatePicker::make('date_follow_up')
                        ->label('Tanggal Follow Up')
                        ->visible(fn(Get $get) => in_array($get('status'), ['draft', 'upcoming', 'ongoing'])),
                    Textarea::make('notes')
                        ->label('Catatan')->columnSpanFull()
                        ->placeholder('Catatan tambahan mengenai client, hasil meeting, dll...'),
                ])->columns(3)
                ->collapsible(),
        ];
    }
}
