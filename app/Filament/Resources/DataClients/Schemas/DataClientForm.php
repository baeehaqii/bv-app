<?php

namespace App\Filament\Resources\DataClients\Schemas;

use App\Enums\SalesStatus;
use App\Models\BvSalesList;
use App\Models\DataClient;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Repeater\TableColumn;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\ToggleButtons;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
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
                        ->label(fn(Get $get) => $get('type') === 'agency' ? 'Nama Agency' : 'Nama Brand')
                        ->placeholder(fn(Get $get) => $get('type') === 'agency' ? 'Masukan nama agency...' : 'Masukan nama brand...')
                        ->visible(fn(Get $get) => in_array($get('type'), ['direct', 'agency']))
                        ->required(fn(Get $get) => in_array($get('type'), ['direct', 'agency'])),

                    Select::make('category')
                        ->label('Kategori')
                        ->visible(fn(Get $get) => $get('type') !== 'agency')
                        ->required(fn(Get $get) => $get('type') === 'direct')
                        ->options(function () {
                            $defaults = [
                                'FMCG' => 'FMCG',
                                'E-Commerce & Tech' => 'E-Commerce & Tech',
                                'Fintech & Banking' => 'Fintech & Banking',
                                'Beauty & Skincare' => 'Beauty & Skincare',
                                'Automotive' => 'Automotive',
                                'Telecommunication' => 'Telecommunication',
                                'Pharmaceuticals' => 'Pharmaceuticals',
                                'Retail & Fashion' => 'Retail & Fashion',
                            ];

                            $existing = \App\Models\DataClient::whereNotNull('category')
                                ->distinct()
                                ->pluck('category', 'category')
                                ->toArray();

                            return array_merge($defaults, $existing);
                        })
                        ->searchable()
                        ->native(false)
                        ->createOptionForm([
                            TextInput::make('category')
                                ->label('Kategori Baru')
                                ->required(),
                        ])
                        ->getOptionLabelUsing(fn($value) => $value)
                        ->createOptionUsing(fn(array $data): string => $data['category'])
                        ->createOptionAction(
                            fn($action) => $action
                                ->label('Tambah Kategori')
                                ->modalHeading('Tambah Kategori Baru')
                                ->modalWidth('md')
                        ),
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
                        ->label('Instagram')->placeholder('@contohbrand')
                        ->required(fn(Get $get) => $get('type') === 'direct'),
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

                    // ─── PIC (dipindah dari section terpisah) ────────────
                    Select::make('pic_internal_sales_id')
                        ->label('PIC Internal (Sales)')
                        ->options(fn() => BvSalesList::orderBy('nama_sales')->pluck('nama_sales', 'id'))
                        ->searchable()
                        ->native(false)
                        ->nullable(),

                    // ─── PIC Client (dikomentari — dipindah ke section tersendiri untuk direct brand) ─
                    // Repeater::make('pic_clients')
                    //     ->label('PIC Client')
                    //     ->schema([
                    //         TextInput::make('name')
                    //             ->label('Nama PIC Client')
                    //             ->placeholder('Nama PIC Client...')
                    //             ->required(),
                    //         TextInput::make('role')
                    //             ->label('Jabatan')
                    //             ->placeholder('Jabatan PIC...'),
                    //         TextInput::make('email')
                    //             ->label('Email PIC Client')
                    //             ->email()
                    //             ->placeholder('email@contoh.com')
                    //             ->required(),
                    //         TextInput::make('wa_number')
                    //             ->label('No WhatsApp PIC Client')
                    //             ->tel()
                    //             ->placeholder('081234567890'),
                    //         TextInput::make('pic_leads')
                    //             ->label('PIC Leads')
                    //             ->placeholder('Nama leads terkait...'),
                    //     ])
                    //     ->table([
                    //         TableColumn::make('Nama PIC Client'),
                    //         TableColumn::make('Jabatan'),
                    //         TableColumn::make('Email PIC Client'),
                    //         TableColumn::make('No WhatsApp PIC Client'),
                    //         TableColumn::make('PIC Leads'),
                    //     ])
                    //     ->addActionLabel('Tambah PIC Client')
                    //     ->defaultItems(0)
                    //     ->reorderable(false)
                    //     ->columnSpanFull(),

                    Toggle::make('has_agency')
                        ->label('Memiliki Agency?')
                        ->visible(fn(Get $get) => $get('type') === 'direct')
                        ->live()
                ])
                ->collapsible()->columns(3),

            // ─── Detail Agency (hanya untuk Direct Brand yang memiliki agency) ─
            Section::make('Detail Agency')
                ->description('Pilih agency existing dari database atau tambahkan agency baru')
                ->visible(fn(Get $get) => $get('type') === 'direct' && (bool) $get('has_agency'))
                ->schema([
                    ToggleButtons::make('agency_source')
                        ->label('Sumber Agency')
                        ->options([
                            'existing' => 'Agency Existing',
                            'baru' => 'Tambah Baru',
                        ])
                        ->icons([
                            'existing' => 'heroicon-o-magnifying-glass',
                            'baru' => 'heroicon-o-plus-circle',
                        ])
                        ->default('existing')
                        ->inline()
                        ->live()
                        ->dehydrated(false)
                        ->columnSpanFull(),

                    Repeater::make('pics')
                        ->hiddenLabel()
                        ->schema([
                            // Mode Existing: Select dropdown dari database
                            Select::make('agency')
                                ->label('Nama Agency')
                                ->options(fn() => DataClient::where('type', 'agency')
                                    ->orderBy('nama_brand')
                                    ->pluck('nama_brand', 'nama_brand'))
                                ->searchable()
                                ->native(false)
                                ->live()
                                ->afterStateUpdated(function (?string $state, Set $set) {
                                    if (!$state) {
                                        return;
                                    }

                                    $agency = DataClient::where('type', 'agency')
                                        ->where('nama_brand', $state)
                                        ->first();

                                    if (!$agency) {
                                        return;
                                    }

                                    // Mapping dari PIC pertama agency
                                    $firstPic = collect($agency->pics ?? [])->first();

                                    $set('name', $firstPic['name'] ?? $agency->nama_brand);
                                    $set('email', $firstPic['email'] ?? null);
                                    $set('wa_number', $firstPic['wa_number'] ?? null);
                                    $set('description', $firstPic['description'] ?? null);
                                })
                                ->required(fn(Get $get) => ($get('../../agency_source') ?? 'existing') === 'existing')
                                ->visible(fn(Get $get) => ($get('../../agency_source') ?? 'existing') === 'existing'),

                            // Mode Baru: TextInput manual
                            TextInput::make('agency_new')
                                ->label('Nama Agency')
                                ->placeholder('Ketik nama agency...')
                                ->required(fn(Get $get) => $get('../../agency_source') === 'baru')
                                ->visible(fn(Get $get) => $get('../../agency_source') === 'baru')
                                ->dehydrated(false),

                            TextInput::make('name')
                                ->label('Nama PIC Agency')
                                ->placeholder('Nama PIC...')
                                ->required(),
                            TextInput::make('email')
                                ->label('Email Agency')
                                ->email()
                                ->placeholder('email@contoh.com'),
                            TextInput::make('wa_number')
                                ->label('No WhatsApp')
                                ->tel()
                                ->placeholder('081234567890'),
                            Textarea::make('description')
                                ->label('Deskripsi')
                                ->placeholder('Deskripsi PIC...')
                                ->rows(1),
                        ])
                        ->columns(5)
                        ->mutateDehydratedStateUsing(function (?array $state) {
                            if (!$state) {
                                return $state;
                            }

                            // Normalisasi: jika agency_new diisi (mode baru), copy ke agency
                            return collect($state)->map(function ($item) {
                                if (!empty($item['agency_new'])) {
                                    $item['agency'] = $item['agency_new'];
                                }
                                unset($item['agency_new']);

                                return $item;
                            })->values()->all();
                        })
                        ->addActionLabel('Tambah Agency')
                        ->defaultItems(0)
                        ->reorderable(false)
                        ->columnSpanFull(),
                ])
                ->collapsible(),

            // ─── Brand yang Di-handle (Agency) ──────────────────────────────
            Section::make('Brand yang Di-handle')
                ->description('Daftar brand yang di-handle oleh agency beserta PIC masing-masing brand')
                ->visible(fn(Get $get) => $get('type') === 'agency')
                ->schema([
                    Repeater::make('agency_brands')
                        ->hiddenLabel()
                        ->schema([
                            Select::make('nama_brand')
                                ->label('Nama Brand')
                                ->options(fn() => DataClient::where('type', 'direct')
                                    ->whereNotNull('nama_brand')
                                    ->orderBy('nama_brand')
                                    ->pluck('nama_brand', 'nama_brand'))
                                ->searchable()
                                ->native(false)
                                ->live()
                                ->afterStateUpdated(function (?string $state, Set $set) {
                                    if (!$state) {
                                        $set('category', null);
                                        $set('nama_pic', null);
                                        $set('email', null);
                                        $set('wa_number', null);
                                        $set('description', null);
                                        return;
                                    }
                                    $client = DataClient::where('type', 'direct')
                                        ->where('nama_brand', $state)
                                        ->first();
                                    if (!$client)
                                        return;
                                    $pic = collect($client->pic_clients)->first() ?? [];
                                    $set('category', $client->category);
                                    $set('nama_pic', $pic['name'] ?? $pic['nama_pic'] ?? null);
                                    $set('email', $pic['email'] ?? $pic['email_pic'] ?? null);
                                    $set('wa_number', $pic['wa_number'] ?? $pic['wa_pic'] ?? null);
                                    $set('description', $client->notes ?? null);
                                })
                                ->createOptionForm([
                                    TextInput::make('nama_brand')
                                        ->label('Nama Brand Baru')
                                        ->required(),
                                ])
                                ->getOptionLabelUsing(fn($value) => $value)
                                ->createOptionUsing(fn(array $data): string => $data['nama_brand'])
                                ->createOptionAction(
                                    fn($action) => $action
                                        ->label('Tambah Brand Baru')
                                        ->modalHeading('Tambah Brand Baru')
                                        ->modalWidth('sm')
                                )
                                ->required(),

                            Select::make('category')
                                ->label('Kategori')
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
                                ->disabled(fn(Get $get) => filled($get('nama_brand')) && DataClient::where('type', 'direct')->where('nama_brand', $get('nama_brand'))->exists())
                                ->dehydrated(),
                            TextInput::make('nama_pic')
                                ->label('Nama PIC Brand')
                                ->placeholder('Nama PIC brand...')
                                ->readOnly(fn(Get $get) => filled($get('nama_brand')) && DataClient::where('type', 'direct')->where('nama_brand', $get('nama_brand'))->exists()),
                            TextInput::make('email')
                                ->label('Email PIC Brand')
                                ->email()
                                ->placeholder('email@contoh.com')
                                ->readOnly(fn(Get $get) => filled($get('nama_brand')) && DataClient::where('type', 'direct')->where('nama_brand', $get('nama_brand'))->exists()),
                            TextInput::make('wa_number')
                                ->label('No WhatsApp PIC Brand')
                                ->tel()
                                ->placeholder('081234567890')
                                ->readOnly(fn(Get $get) => filled($get('nama_brand')) && DataClient::where('type', 'direct')->where('nama_brand', $get('nama_brand'))->exists()),
                            Textarea::make('description')
                                ->label('Deskripsi')
                                ->placeholder('Deskripsi brand atau catatan tambahan...')
                                ->rows(1)
                                ->readOnly(fn(Get $get) => filled($get('nama_brand')) && DataClient::where('type', 'direct')->where('nama_brand', $get('nama_brand'))->exists()),
                        ])
                        ->table([
                            TableColumn::make('Nama Brand'),
                            TableColumn::make('Kategori'),
                            TableColumn::make('Nama PIC Brand'),
                            TableColumn::make('Email PIC Brand'),
                            TableColumn::make('No WhatsApp PIC Brand'),
                            TableColumn::make('Deskripsi'),
                        ])
                        ->addActionLabel('Tambah Brand')
                        ->defaultItems(0)
                        ->reorderable(false)
                        ->columnSpanFull(),
                ])
                ->collapsible(),

            // ─── PIC Agency (Agency Only) ────────────────────────────────────
            Section::make('PIC Agency')
                ->description('Informasi Person in Charge dari sisi agency')
                ->visible(fn(Get $get) => $get('type') === 'agency')
                ->schema([
                    Repeater::make('pic_clients')
                        ->hiddenLabel()
                        ->schema([
                            TextInput::make('name')
                                ->label('Nama PIC Agency')
                                ->placeholder('Nama PIC...')
                                ->required(),
                            TextInput::make('role')
                                ->label('Jabatan')
                                ->placeholder('Jabatan PIC...'),
                            TextInput::make('email')
                                ->label('Email')
                                ->email()
                                ->placeholder('email@contoh.com')
                                ->required(),
                            TextInput::make('wa_number')
                                ->label('No WhatsApp')
                                ->tel()
                                ->placeholder('081234567890'),
                            TextInput::make('pic_leads')
                                ->label('PIC Leads')
                                ->placeholder('Nama leads terkait...'),
                        ])
                        ->table([
                            TableColumn::make('Nama PIC Agency'),
                            TableColumn::make('Jabatan'),
                            TableColumn::make('Email'),
                            TableColumn::make('No WhatsApp'),
                            TableColumn::make('PIC Leads'),
                        ])
                        ->addActionLabel('Tambah PIC Agency')
                        ->defaultItems(0)
                        ->reorderable(false)
                        ->columnSpanFull(),
                ])
                ->collapsible(),

            // ─── PIC Client (Direct Brand Only) ─────────────────────────────
            Section::make('PIC Client')
                ->description('Informasi Person in Charge dari sisi client')
                ->visible(fn(Get $get) => $get('type') === 'direct')
                ->schema([
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
