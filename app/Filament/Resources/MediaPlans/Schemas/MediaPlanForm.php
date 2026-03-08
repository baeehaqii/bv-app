<?php

namespace App\Filament\Resources\MediaPlans\Schemas;

use Filament\Schemas\Schema;
use App\Models\DataKol;
use App\Models\MasterPph;
use App\Enums\VendorTaxType;
use App\Service\InstagramService;
use App\Service\TiktokService;
use App\Service\YoutubeChannelsService;
use App\Service\YoutubeShortsService;
use App\Helpers\QuotationNumberGenerator;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\Checkbox;
use Filament\Forms\Components\TagsInput;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\ToggleButtons;
use Filament\Forms\Components\ViewField;
use Filament\Schemas\Components\Wizard;
use Filament\Schemas\Components\Wizard\Step;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Fieldset;
use Filament\Forms\Components\DatePicker;
use Filament\Notifications\Notification;
use Filament\Actions\Action;
use Filament\Schemas\Components\Actions;
use Filament\Support\RawJs;

class MediaPlanForm
{
    /**
     * Parse formatted number string to float
     * Converts "2.000.000" or "2.000.000,50" to 2000000.50
     */
    private static function parseNumber($value): float
    {
        if (empty($value))
            return 0;
        if (is_numeric($value))
            return (float) $value;

        $value = (string) $value;

        // Remove all non-numeric except . and ,
        $value = preg_replace('/[^\d.,]/', '', $value);

        $dotCount = substr_count($value, '.');
        $commaCount = substr_count($value, ',');

        // Case 1: Only commas (US format from $money mask) - "400,000"
        if ($commaCount > 0 && $dotCount == 0) {
            return (float) str_replace(',', '', $value);
        }

        // Case 2: Only dots (Indonesia format) - "400.000"
        if ($dotCount > 0 && $commaCount == 0) {
            // Check if it's thousand separator (more than 1 dot or position)
            if ($dotCount > 1) {
                return (float) str_replace('.', '', $value);
            }
            // Check position - if 3 digits after single dot, it's thousand separator
            $parts = explode('.', $value);
            if (count($parts) == 2 && strlen($parts[1]) == 3) {
                return (float) str_replace('.', '', $value);
            }
            // Otherwise treat as decimal
            return (float) $value;
        }

        // Case 3: Both (e.g., "1.234,56" Indonesia or "1,234.56" US)
        if ($dotCount > 0 && $commaCount > 0) {
            $lastDot = strrpos($value, '.');
            $lastComma = strrpos($value, ',');

            if ($lastDot > $lastComma) {
                // US format: "1,234.56" - comma thousand, dot decimal
                return (float) str_replace(',', '', $value);
            } else {
                // Indonesia format: "1.234,56" - dot thousand, comma decimal
                $cleaned = str_replace('.', '', $value);
                $cleaned = str_replace(',', '.', $cleaned);
                return (float) $cleaned;
            }
        }

        return (float) $value;
    }

    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Wizard::make([
                    Step::make('Campaign Information')
                        ->icon('heroicon-m-document-text')
                        ->description('Campaign details & client info')
                        ->schema([
                            Section::make('Campaign Information')
                                ->schema([

                                    Select::make('campaign_type')
                                        ->label('Campaign Type')->required()
                                        ->options([
                                            'Content Creation' => 'Content Creation',
                                            'Social Media' => 'Social Media',
                                            'Digital Marketing' => 'Digital Marketing',
                                        ])
                                        ->placeholder('Pilih Campaign Type'),
                                    Select::make('campaign_name')
                                        ->label('Campaign Name')
                                        ->options(fn() => \App\Models\BvSales::pluck('event_name', 'event_name'))
                                        ->searchable()
                                        ->preload()
                                        ->placeholder('Pilih Sales Activity')
                                        ->required(),
                                    Datepicker::make('campaign_period_start')
                                        ->label('Campaign Period Start')->native(false)->displayFormat('d/m/Y')
                                        ->placeholder('e.g., November 2025')->default(now())->required(),
                                    Datepicker::make('campaign_period_end')
                                        ->label('Campaign Period End')->native(false)->displayFormat('d/m/Y')
                                        ->placeholder('e.g., December 2025')->required(),
                                    TextInput::make('platform')
                                        ->label('Platform')->required()
                                        ->placeholder('e.g., Social Media'),

                                ])->columns(2),

                            Section::make('Detail Brand')
                                ->schema([
                                    Select::make('brand')
                                        ->label('Brand/Client')
                                        ->options(\App\Models\DataClient::pluck('nama_brand', 'nama_brand'))
                                        ->searchable()
                                        ->preload()
                                        ->placeholder('Pilih Brand/Client')
                                        ->required()
                                        ->live()
                                        ->afterStateUpdated(function ($state, callable $set) {
                                            if ($state) {
                                                $client = \App\Models\DataClient::where('nama_brand', $state)->first();
                                                if ($client) {
                                                    $set('pic_client', $client->nama_pic);
                                                }
                                            }
                                        }),
                                    TextInput::make('pic_client')
                                        ->label('PIC Client')
                                        ->placeholder('e.g., Rohmah')
                                        ->required(),
                                    TextInput::make('domisili')
                                        ->label('Domisili')->required()
                                        ->placeholder('e.g., Jakarta'),
                                    Select::make('pic_campaign_id')
                                        ->label('Assign Tugas Brief Ke (PIC Campaign/Sales)')
                                        ->options(\App\Models\BvSalesList::pluck('nama_sales', 'id'))
                                        ->searchable()
                                        ->preload()
                                        ->nullable()
                                        ->helperText('Assign tugas brief media plan ke PIC tim internal'),
                                ])->columns(4),
                        ]),

                    Step::make('Select KOL')
                        ->icon('heroicon-m-user-group')
                        ->description('Choose or search for multiple KOLs')
                        ->schema([
                            Section::make('📊 Summary List KOL')
                                ->description('Ringkasan otomatis dari KOL yang dicentang')
                                ->schema([
                                    Grid::make(4)
                                        ->schema([
                                            Placeholder::make('selected_count_display')
                                                ->label('Selected KOLs')
                                                ->extraAttributes(['class' => 'text-primary-600 dark:text-primary-400'])
                                                ->content(fn(callable $get) => self::getSelectedCount($get('kols') ?? [])),
                                            Placeholder::make('total_rate_display')
                                                ->label('Total Rate')
                                                ->extraAttributes(['class' => 'text-primary-600 dark:text-primary-400'])
                                                ->content(fn(callable $get) => 'Rp ' . number_format(self::getTotalRate($get('kols') ?? []), 0, ',', '.')),
                                            Placeholder::make('total_impression_display')
                                                ->label('Total Est. Views')
                                                ->extraAttributes(['class' => 'text-primary-600 dark:text-primary-400'])
                                                ->content(fn(callable $get) => number_format(self::getTotalImpression($get('kols') ?? []), 0, ',', '.')),
                                            Placeholder::make('total_engagement_display')
                                                ->label('Total Est. Engagement')
                                                ->extraAttributes(['class' => 'text-primary-600 dark:text-primary-400'])
                                                ->content(fn(callable $get) => number_format(self::getTotalEngagement($get('kols') ?? []), 0, ',', '.')),
                                        ]),
                                ])
                                ->collapsible(),

                            Repeater::make('kols')
                                ->label('KOL List')
                                ->schema([
                                    Section::make('KOL Information')
                                        ->description('Pilih apakah akan menggunakan KOL yang sudah ada di database atau menambahkan KOL baru')
                                        ->schema([
                                            ToggleButtons::make('kol_source')
                                                ->label('Sumber KOL')
                                                ->options([
                                                    'existing' => 'KOL Existing',
                                                    'new' => 'KOL Baru',
                                                ])
                                                ->icons([
                                                    'existing' => 'heroicon-m-user-group',
                                                    'new' => 'heroicon-m-plus-circle',
                                                ])
                                                ->inline()
                                                ->default('existing')
                                                ->live()
                                                ->dehydrated(false)
                                                ->afterStateUpdated(function (callable $set) {
                                                    // Reset related fields when switching
                                                    $set('data_kol_id', null);
                                                    $set('channel', null);
                                                    $set('categories', null);
                                                })
                                                ->columnSpanFull(),

                                            // === EXISTING KOL FIELDS (only visible when 'existing' selected) ===
                                            Select::make('channel')
                                                ->label('Channel')
                                                ->options([
                                                    'Instagram' => 'Instagram',
                                                    'Tiktok' => 'Tiktok',
                                                    'Youtube Channels' => 'Youtube Channels',
                                                    'Youtube Shorts' => 'Youtube Shorts',
                                                ])
                                                ->live(onBlur: true)
                                                ->afterStateUpdated(function (callable $set) {
                                                    $set('categories', null);
                                                    $set('data_kol_id', null);
                                                })
                                                ->required()
                                                ->visible(fn(callable $get) => $get('kol_source') === 'existing')
                                                ->columnSpan(1),

                                            Select::make('categories')
                                                ->label('Categories')
                                                ->options(function (callable $get) {
                                                    $channel = $get('channel');
                                                    if (!$channel)
                                                        return [];

                                                    return DataKol::where('channel', $channel)
                                                        ->whereNotNull('category')
                                                        ->distinct()
                                                        ->pluck('category', 'category')
                                                        ->toArray();
                                                })
                                                ->live(onBlur: true)
                                                ->afterStateUpdated(fn(callable $set) => $set('data_kol_id', null))
                                                ->searchable()
                                                ->visible(fn(callable $get) => $get('kol_source') === 'existing')
                                                ->columnSpan(1),

                                            Select::make('data_kol_id')
                                                ->label('Pilih KOL dari Database')
                                                ->options(function (callable $get) {
                                                    $channel = $get('channel');
                                                    $category = $get('categories');

                                                    if (!$channel)
                                                        return [];

                                                    $query = DataKol::where('channel', $channel);

                                                    if ($category) {
                                                        $query->where('category', $category);
                                                    }

                                                    return $query->pluck('username', 'id')->toArray();
                                                })
                                                ->live(onBlur: true)
                                                ->afterStateUpdated(function (?string $state, callable $set, callable $get) {
                                                    if (empty($state))
                                                        return;

                                                    $kol = DataKol::find($state);
                                                    if (!$kol) {
                                                        return;
                                                    }

                                                    // Auto-fill KOL data
                                                    $set('name', $kol->username);
                                                    $set('links', [$kol->link_userprofile]);
                                                    $set('followers', (int) $kol->followers);
                                                    $set('tier', $kol->tier);
                                                    $set('er_percent', (float) $kol->engagement_rate);
                                                    $set('impression', (int) $kol->impressions);

                                                    // Calculate engagement
                                                    $followers = (int) $kol->followers;
                                                    $er = (float) $kol->engagement_rate;
                                                    $engagement = intval($followers * ($er / 100));
                                                    $set('engagement', $engagement);

                                                    Notification::make()
                                                        ->title('✅ KOL berhasil dipilih!')
                                                        ->success()
                                                        ->body("Data @{$kol->username} berhasil diambil dari database.")
                                                        ->send();
                                                })
                                                ->searchable()
                                                ->preload()
                                                ->visible(fn(callable $get) => $get('kol_source') === 'existing')
                                                ->helperText('Pilih KOL yang sudah tersimpan di database')
                                                ->columnSpan(1),

                                            // === NEW KOL - Action Button (only visible when 'new' selected) ===
                                            Actions::make([
                                                Action::make('create_new_kol')
                                                    ->label('Tambah KOL Baru ke Database')
                                                    ->icon('heroicon-o-user-plus')
                                                    ->size('lg')
                                                    ->slideOver()
                                                    ->modalWidth('4xl')
                                                    ->modalHeading('Tambah KOL Baru ke Database')
                                                    ->modalDescription('Data KOL akan disimpan ke database dan otomatis terhubung ke Media Plan ini.')
                                                    ->modalIcon('heroicon-o-user-plus')
                                                    ->form([
                                                        Section::make('Social Media Data')
                                                            ->columnSpanFull()
                                                            ->schema([
                                                                Select::make('channel')
                                                                    ->label('Channel')
                                                                    ->options([
                                                                        'Instagram' => 'Instagram',
                                                                        'Tiktok' => 'Tiktok',
                                                                        'Youtube Channels' => 'Youtube Channels',
                                                                        'Youtube Shorts' => 'Youtube Shorts',
                                                                    ])
                                                                    ->live(onBlur: true)
                                                                    ->afterStateUpdated(fn(callable $set) => $set('link_userprofile', null))
                                                                    ->required(),

                                                                TextInput::make('link_userprofile')
                                                                    ->label(fn(callable $get) => match ($get('channel')) {
                                                                        'Instagram' => 'Instagram Profile URL',
                                                                        'Tiktok' => 'TikTok Profile URL',
                                                                        'Youtube Channels' => 'YouTube Channel URL',
                                                                        'Youtube Shorts' => 'YouTube Channel URL',
                                                                        default => 'Profile URL',
                                                                    })
                                                                    ->placeholder(fn(callable $get) => match ($get('channel')) {
                                                                        'Instagram' => 'https://www.instagram.com/username/',
                                                                        'Tiktok' => 'https://www.tiktok.com/@username',
                                                                        'Youtube Channels' => 'https://www.youtube.com/@username',
                                                                        'Youtube Shorts' => 'https://www.youtube.com/@username',
                                                                        default => 'Profile URL',
                                                                    })
                                                                    ->helperText('📋 Masukkan URL/username, tekan Tab/Enter untuk fetch data')
                                                                    ->required(fn(callable $get) => !empty($get('channel')))
                                                                    ->live(onBlur: true)
                                                                    ->afterStateUpdated(function (?string $state, callable $set, callable $get) {
                                                                        if (empty($state) || empty($get('channel'))) {
                                                                            return;
                                                                        }

                                                                        $channel = $get('channel');

                                                                        try {
                                                                            $profile = match ($channel) {
                                                                                'Instagram' => (new InstagramService())->getProfile($state),
                                                                                'Tiktok' => (new TiktokService())->getProfile($state),
                                                                                'Youtube Channels' => (new YoutubeChannelsService())->getProfile($state),
                                                                                'Youtube Shorts' => (new YoutubeShortsService())->getProfile($state),
                                                                                default => null,
                                                                            };

                                                                            if (!$profile) {
                                                                                throw new \Exception('Channel tidak didukung');
                                                                            }

                                                                            // Auto-fill fields
                                                                            $set('username', $profile['username']);
                                                                            $set('followers', $profile['followers_count']);
                                                                            $set('tier', $profile['tier']);
                                                                            $set('engagement_rate', $profile['engagement_rate']);
                                                                            $set('engagements', $profile['total_engagements']);
                                                                            $set('impressions', $profile['average_impressions']);

                                                                            if (!empty($profile['category_name'])) {
                                                                                $set('category', $profile['category_name']);
                                                                            }

                                                                            Notification::make()
                                                                                ->title("✅ Data {$channel} berhasil diambil!")
                                                                                ->success()
                                                                                ->body("Profile @{$profile['username']} dengan " . number_format($profile['followers_count']) . " followers.")
                                                                                ->send();

                                                                        } catch (\Exception $e) {
                                                                            Notification::make()
                                                                                ->title("❌ Gagal mengambil data")
                                                                                ->danger()
                                                                                ->body($e->getMessage())
                                                                                ->send();
                                                                        }
                                                                    }),

                                                                TextInput::make('username')
                                                                    ->label('Username')
                                                                    ->readOnly()
                                                                    ->dehydrated()
                                                                    ->prefixIcon('heroicon-o-at-symbol'),

                                                                TextInput::make('followers')
                                                                    ->label('Followers')
                                                                    ->numeric()
                                                                    ->readOnly()
                                                                    ->dehydrated()
                                                                    ->prefixIcon('heroicon-o-users'),

                                                                TextInput::make('tier')
                                                                    ->label('Tier')
                                                                    ->readOnly()
                                                                    ->dehydrated()
                                                                    ->prefixIcon('heroicon-o-star'),

                                                                TextInput::make('engagement_rate')
                                                                    ->label('Engagement Rate')
                                                                    ->suffix('%')
                                                                    ->numeric()
                                                                    ->readOnly()
                                                                    ->dehydrated()
                                                                    ->prefixIcon('heroicon-o-chart-bar'),

                                                                TextInput::make('engagements')
                                                                    ->label('Total Engagements')
                                                                    ->numeric()
                                                                    ->readOnly()
                                                                    ->dehydrated()
                                                                    ->prefixIcon('heroicon-o-heart'),

                                                                TextInput::make('impressions')
                                                                    ->label('Avg Impressions')
                                                                    ->numeric()
                                                                    ->readOnly()
                                                                    ->dehydrated()
                                                                    ->prefixIcon('heroicon-o-eye'),

                                                                Select::make('category')
                                                                    ->options([
                                                                        'Gamers & Lifestyle' => 'Gamers & Lifestyle',
                                                                        'Lifestyle' => 'Lifestyle',
                                                                        'Techno' => 'Techno',
                                                                        'Beauty' => 'Beauty',
                                                                        'Kpop' => 'Kpop',
                                                                        'Otomotif' => 'Otomotif',
                                                                        'Sport' => 'Sport',
                                                                        'Family' => 'Family',
                                                                        'Comedy' => 'Comedy',
                                                                        'Sport & Lifestyle' => 'Sport & Lifestyle',
                                                                        'Fashion & Lifestyle' => 'Fashion & Lifestyle',
                                                                        'DIY' => 'DIY',
                                                                        'Travel' => 'Travel',
                                                                        'Home Living' => 'Home Living',
                                                                        'Photography' => 'Photography',
                                                                        'Beauty & Lifestyle' => 'Beauty & Lifestyle',
                                                                        'Music' => 'Music',
                                                                        'Home Cook' => 'Home Cook',
                                                                        'Couple' => 'Couple',
                                                                        'Foodies' => 'Foodies',
                                                                    ])
                                                                    ->label('Category')
                                                                    ->searchable(),

                                                                Select::make('status')
                                                                    ->label('Status')
                                                                    ->options([
                                                                        'New List' => 'New List',
                                                                        'Approaching' => 'Approaching',
                                                                        'Waiting Feedback' => 'Waiting Feedback',
                                                                        'Not Available' => 'Not Available',
                                                                    ])
                                                                    ->default('New List'),
                                                            ])->columns(3),

                                                        Section::make('Additional Info')
                                                            ->columnSpanFull()
                                                            ->schema([
                                                                TextInput::make('contact')
                                                                    ->label('Contact')
                                                                    ->email(),

                                                                DatePicker::make('terakhir_update')
                                                                    ->label('Terakhir Update')
                                                                    ->default(now()),

                                                                Textarea::make('notes')
                                                                    ->label('Notes')
                                                                    ->rows(3)
                                                                    ->columnSpanFull(),
                                                            ])->columns(2),
                                                    ])
                                                    ->action(function (array $data, callable $set) {
                                                        // Validate required fields
                                                        if (empty($data['username']) || empty($data['channel'])) {
                                                            Notification::make()
                                                                ->danger()
                                                                ->title('Data belum lengkap')
                                                                ->body('Pastikan data sudah ter-fetch dari API sebelum menyimpan.')
                                                                ->send();
                                                            return;
                                                        }

                                                        // Create new KOL
                                                        $kol = DataKol::create([
                                                            'channel' => $data['channel'],
                                                            'link_userprofile' => $data['link_userprofile'],
                                                            'username' => $data['username'],
                                                            'followers' => $data['followers'] ?? 0,
                                                            'tier' => $data['tier'] ?? null,
                                                            'engagement_rate' => $data['engagement_rate'] ?? 0,
                                                            'engagements' => $data['engagements'] ?? 0,
                                                            'impressions' => $data['impressions'] ?? 0,
                                                            'category' => $data['category'] ?? null,
                                                            'status' => $data['status'] ?? 'New List',
                                                            'contact' => $data['contact'] ?? null,
                                                            'terakhir_update' => $data['terakhir_update'] ?? now(),
                                                            'notes' => $data['notes'] ?? null,
                                                        ]);

                                                        // Auto-fill KOL data in the parent form
                                                        $set('data_kol_id', $kol->id);
                                                        $set('channel', $kol->channel);
                                                        $set('name', $kol->username);
                                                        $set('links', [$kol->link_userprofile]);
                                                        $set('followers', (int) $kol->followers);
                                                        $set('tier', $kol->tier);
                                                        $set('er_percent', (float) $kol->engagement_rate);
                                                        $set('impression', (int) $kol->impressions);
                                                        $engagement = intval($kol->followers * ($kol->engagement_rate / 100));
                                                        $set('engagement', $engagement);

                                                        Notification::make()
                                                            ->success()
                                                            ->title('✅ KOL berhasil ditambahkan!')
                                                            ->body("@{$kol->username} telah disimpan ke database dan data form telah terisi otomatis.")
                                                            ->send();
                                                    }),
                                            ])
                                                ->visible(fn(callable $get) => $get('kol_source') === 'new')
                                                ->extraAttributes([
                                                    'x-init' => '$nextTick(() => { $el.querySelector("button")?.click() })',
                                                ])
                                                ->columnSpanFull(),
                                        ])->columns(3),

                                    // Row 3: KOL Details
                                    section::make('KOL Details')
                                        ->schema([
                                            Select::make('channel')
                                                ->label('Channel')
                                                ->options([
                                                    'Instagram' => 'Instagram',
                                                    'Tiktok' => 'Tiktok',
                                                    'Youtube Channels' => 'Youtube Channels',
                                                    'Youtube Shorts' => 'Youtube Shorts',
                                                ])
                                                ->required()
                                                ->default('Instagram')
                                                ->columnSpan(1),

                                            TextInput::make('name')
                                                ->label('KOL Name')
                                                ->placeholder('Username / Nama')
                                                ->required()
                                                ->columnSpan(2),

                                            TextInput::make('domisili')
                                                ->label('Domisili')
                                                ->placeholder('e.g., Jakarta, Bandung'),

                                            Select::make('tipe_pajak_kol')
                                                ->label('Golongan Pajak')
                                                ->options(function () {
                                                    return MasterPph::active()
                                                        ->ordered()
                                                        ->get()
                                                        ->mapWithKeys(function ($pph) {
                                                            $label = $pph->name;
                                                            if ($pph->include_ppn) {
                                                                $label .= " ({$pph->coefficient} + PPN {$pph->ppn_percent}%)";
                                                            } else {
                                                                $label .= " ({$pph->coefficient})";
                                                            }
                                                            return [$pph->id => $label];
                                                        })
                                                        ->toArray();
                                                })
                                                ->default(fn() => MasterPph::active()->ordered()->first()?->id)
                                                ->helperText('Menentukan besaran pajak untuk influencer')
                                                ->required()
                                                ->columnSpan(1),

                                            // Multiple Links Support
                                            TagsInput::make('links')
                                                ->label('Links')
                                                ->placeholder('Tekan Enter untuk tambah link')
                                                ->helperText('Bisa input multiple links (Profile + Portfolio)'),

                                            TextInput::make('followers')
                                                ->label('Followers')
                                                ->numeric()
                                                ->live(onBlur: true)
                                                ->afterStateUpdated(function ($state, callable $set, callable $get) {
                                                    // Recalculate tier
                                                    $followers = (int) $state;
                                                    $tier = \App\Models\MediaPlanKol::calculateTier($followers);
                                                    $set('tier', $tier);

                                                    // Recalculate engagement
                                                    $er = (float) $get('er_percent');
                                                    $engagement = intval($followers * ($er / 100));
                                                    $set('engagement', $engagement);
                                                }),

                                            TextInput::make('tier')
                                                ->label('Tier')
                                                ->placeholder('Nano/Micro/Macro/Mega')
                                                ->readOnly()
                                                ->dehydrated()
                                                ->columnSpan(1),

                                            TextInput::make('er_percent')
                                                ->label('ER %')
                                                ->numeric()
                                                ->suffix('%')
                                                ->live(onBlur: true)
                                                ->afterStateUpdated(function ($state, callable $set, callable $get) {
                                                    // Recalculate engagement
                                                    $followers = (int) $get('followers');
                                                    $er = (float) $state;
                                                    $engagement = intval($followers * ($er / 100));
                                                    $set('engagement', $engagement);
                                                }),
                                        ])->columns(3),

                                    // Row 4: Performance Metrics
                                    section::make('Performance Metrics')
                                        ->schema([
                                            TextInput::make('impression')
                                                ->label('Impression/Avg Views')
                                                ->numeric()
                                                ->live(onBlur: true)
                                                ->columnSpan(1),

                                            TextInput::make('engagement')
                                                ->label('Engagement (Followers × ER)')
                                                ->numeric()
                                                ->readOnly()
                                                ->dehydrated()
                                                ->columnSpan(1),

                                            TextInput::make('cpi_cpv')
                                                ->label('CPI/CPV')
                                                ->prefix('Rp')
                                                ->mask(RawJs::make('$money($input)'))
                                                ->formatStateUsing(fn($state) => $state ? number_format(round($state), 0, '.', ',') : '0')
                                                ->dehydrateStateUsing(fn($state) => round(self::parseNumber($state)))
                                                ->readOnly()
                                                ->helperText('Rate / Impression')
                                                ->columnSpan(1),

                                            TextInput::make('cpe')
                                                ->label('CPE')
                                                ->prefix('Rp')
                                                ->mask(RawJs::make('$money($input)'))
                                                ->formatStateUsing(fn($state) => $state ? number_format(round($state), 0, '.', ',') : '0')
                                                ->dehydrateStateUsing(fn($state) => round(self::parseNumber($state)))
                                                ->readOnly()
                                                ->helperText('Rate / Engagement')
                                                ->columnSpan(1),
                                        ])->columns(4),

                                    // Row 5: Scope of Work
                                    section::make('Scope of Work')
                                        ->schema([
                                            Select::make('scope_items')
                                                ->label('Item Descriptions')
                                                ->multiple()
                                                ->options([
                                                    'IG Post' => 'IG Post',
                                                    'IG Reels' => 'IG Reels',
                                                    'IG Story' => 'IG Story',
                                                    'TikTok Post' => 'TikTok Post',
                                                    'TikTok Video' => 'TikTok Video',
                                                    'TikTok Story' => 'TikTok Story',
                                                    'YouTube Video' => 'YouTube Video',
                                                    'YouTube Shorts' => 'YouTube Shorts',
                                                ])
                                                ->searchable()
                                                ->required()
                                                ->live()
                                                ->default([])
                                                ->columnSpan(2)
                                                ->hintAction(
                                                    Action::make('add_custom_scope')
                                                        ->icon('heroicon-m-plus')
                                                        ->tooltip('Tambah opsi custom')
                                                        ->modalWidth('xs')
                                                        ->modalHeading('Tambah Scope of Work lainnya')
                                                        ->form([
                                                            TextInput::make('custom_scope')
                                                                ->label('Custom Scope Item')
                                                                ->placeholder('e.g., TT Live, IG Live, etc.')
                                                                ->required(),
                                                        ])
                                                        ->action(function (array $data, callable $get, callable $set) {
                                                            $customScope = $data['custom_scope'];
                                                            $currentItems = $get('scope_items') ?? [];

                                                            // Add the custom scope to selected items
                                                            $currentItems[] = $customScope;
                                                            $set('scope_items', $currentItems);

                                                            Notification::make()
                                                                ->success()
                                                                ->title('Custom scope ditambahkan!')
                                                                ->body("'{$customScope}' berhasil ditambahkan.")
                                                                ->send();
                                                        })
                                                ),

                                            TextInput::make('rate')
                                                ->label('Rate (from Internal Budget)')
                                                ->prefix('Rp')
                                                ->mask(RawJs::make('$money($input)'))
                                                ->formatStateUsing(fn($state) => $state ? number_format(round($state), 0, '.', ',') : '0')
                                                ->dehydrateStateUsing(fn($state) => round(self::parseNumber($state)))
                                                ->readOnly()
                                                ->default(0)
                                                ->helperText('Auto-filled from Internal Budget (Rounded)')
                                                ->columnSpan(1),
                                        ])->columns(3),

                                    // Row 1: Selection & Status
                                    Fieldset::make('Selection')
                                        ->schema([
                                            Checkbox::make('is_selected')
                                                ->label('Select for Quotation')
                                                ->default(false)
                                                ->live()
                                                ->columnSpan(1),

                                            Hidden::make('row_number'),

                                            Select::make('status')
                                                ->label('Status')
                                                ->options([
                                                    'New List' => 'New List',
                                                    'Approaching' => 'Approaching',
                                                    'Locked' => 'Locked',
                                                    'Canceled' => 'Canceled',
                                                ])
                                                ->default('New List')
                                                ->columnSpan(1),

                                            Select::make('pic')
                                                ->label('PIC')
                                                ->options([
                                                    'ROHMAH' => 'ROHMAH',
                                                    'NABILLA' => 'NABILLA',
                                                ])
                                                ->columnSpan(1),
                                        ])->columns(3),

                                    // Notes
                                    Textarea::make('notes')
                                        ->label('Notes')
                                        ->placeholder('Special instructions or notes')
                                        ->rows(2)
                                        ->columnSpanFull(),
                                ])
                                ->columns(1)
                                ->collapsible()
                                ->collapsed()
                                ->itemLabel(function (array $state): ?string {
                                    $name = $state['name'] ?? 'New KOL';
                                    $channel = $state['channel'] ?? '';
                                    $selected = ($state['is_selected'] ?? false) ? '✅ ' : '';
                                    $rateValue = self::parseNumber($state['rate'] ?? 0);
                                    $rate = $rateValue > 0
                                        ? ' - Rp ' . number_format($rateValue, 0, ',', '.')
                                        : '';
                                    $channelLabel = $channel ? " ({$channel})" : '';
                                    return $selected . $name . $channelLabel . $rate;
                                })
                                ->defaultItems(1)
                                ->addActionLabel('Add Another KOL')
                                ->reorderable()
                                ->columnSpanFull()
                                ->live(),
                        ])
                        ->afterStateUpdated(function (callable $get, callable $set) {
                            $kols = $get('kols') ?? [];
                            $margins = $get('kol_margins') ?? [];
                            $useGlobal = $get('use_global_margin') ?? true;

                            // Always sync name, but only re-init structure if counts mismatch or forced
                            // Simple approach: Rebuild margin array preserving values for existing indices
                
                            $newMargins = [];
                            $defaultMargin = $get('margin_percent') ?? 30;

                            foreach ($kols as $index => $kol) {
                                // Try to preserve existing margin for this index
                                $currentMargin = $margins[$index]['margin'] ?? $defaultMargin;

                                $newMargins[] = [
                                    'name' => $kol['name'] ?? 'New KOL',
                                    'margin' => $currentMargin,
                                ];
                            }

                            $set('kol_margins', $newMargins);
                        }),

                    Step::make('Margin Setting')
                        ->icon('heroicon-m-calculator')
                        ->description('Configure margin settings for this campaign')
                        ->schema([
                            Section::make('🎯 Margin Configuration')
                                ->description('Setting margin akan diaplikasikan ke semua KOL dalam campaign ini saat kalkulasi Internal Budget')
                                ->schema([
                                    ToggleButtons::make('margin_type')
                                        ->label('Margin Type')
                                        ->options([
                                            'auto' => 'Auto (Based on Budget Range)',
                                            'custom' => 'Custom Margin',
                                        ])
                                        ->icons([
                                            'auto' => 'heroicon-m-cpu-chip',
                                            'custom' => 'heroicon-m-pencil-square',
                                        ])
                                        ->colors([
                                            'auto' => 'info',
                                            'custom' => 'warning',
                                        ])
                                        ->inline()
                                        ->default('auto')
                                        ->live()
                                        ->columnSpanFull()
                                        ->helperText('Auto: Margin dihitung otomatis berdasarkan Master Margin. Custom: Anda tentukan sendiri.'),

                                    TextInput::make('margin_percent')
                                        ->label('Custom Margin %')
                                        ->suffix('%')
                                        ->numeric()
                                        ->step('0.01')
                                        ->minValue(0)
                                        ->maxValue(100)
                                        ->default(30)
                                        ->visible(fn(callable $get) => $get('margin_type') === 'custom')
                                        ->required(fn(callable $get) => $get('margin_type') === 'custom')
                                        ->helperText('Contoh: 30 untuk 30%, 40 untuk 40%, dll'),

                                    Toggle::make('use_global_margin')
                                        ->label('Apply to All KOLs')
                                        ->helperText('Jika aktif, margin ini akan diterapkan ke semua KOL. Jika tidak, setiap KOL bisa memiliki margin berbeda di Internal Budget.')
                                        ->inline()
                                        ->default(true)
                                        ->live()
                                        ->afterStateUpdated(function ($state, callable $get, callable $set) {
                                            if (!$state) {
                                                // Sync kols to margins when toggled OFF
                                                $kols = $get('kols') ?? [];
                                                $margins = [];
                                                $default = $get('margin_percent') ?? 30;

                                                foreach ($kols as $kol) {
                                                    $margins[] = [
                                                        'name' => $kol['name'] ?? 'New KOL',
                                                        'margin' => $default,
                                                    ];
                                                }
                                                $set('kol_margins', $margins);
                                            }
                                        })
                                        ->columnSpanFull(),

                                    Repeater::make('kol_margins')
                                        ->label('Custom Margin per KOL')
                                        ->hidden(fn(callable $get) => $get('use_global_margin') === true)
                                        ->schema([
                                            TextInput::make('name')
                                                ->disabled()
                                                ->dehydrated(false)
                                                ->columnSpan(2),
                                            TextInput::make('margin')
                                                ->label('Margin %')
                                                ->numeric()
                                                ->suffix('%')
                                                ->required()
                                                ->maxValue(100)
                                                ->minValue(0)
                                                ->columnSpan(1),
                                        ])
                                        ->addable(false)
                                        ->deletable(false)
                                        ->reorderable(false)
                                        ->columns(3)
                                        ->columnSpanFull(),

                                    // Master Margin Reference removed
                                ])
                                ->columns(2),
                        ]),
                ])
                    ->columnSpanFull()
                    ->skippable()
            ]);
    }

    /**
     * Helper: Get count of selected KOLs
     */
    private static function getSelectedCount(array $kols): string
    {
        $count = collect($kols)->filter(fn($kol) => $kol['is_selected'] ?? false)->count();
        return "{$count} KOL(s) selected";
    }

    /**
     * Helper: Get total rate of selected KOLs
     */
    private static function getTotalRate(array $kols): float
    {
        return collect($kols)
            ->filter(fn($kol) => $kol['is_selected'] ?? false)
            ->sum(fn($kol) => self::parseNumber($kol['rate'] ?? 0));
    }

    /**
     * Helper: Get total impression of selected KOLs
     */
    private static function getTotalImpression(array $kols): int
    {
        return collect($kols)
            ->filter(fn($kol) => $kol['is_selected'] ?? false)
            ->sum(fn($kol) => (int) ($kol['impression'] ?? 0));
    }

    /**
     * Helper: Get total engagement of selected KOLs
     */
    private static function getTotalEngagement(array $kols): int
    {
        return collect($kols)
            ->filter(fn($kol) => $kol['is_selected'] ?? false)
            ->sum(fn($kol) => (int) ($kol['engagement'] ?? 0));
    }
}
