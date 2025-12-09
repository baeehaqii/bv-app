<?php

namespace App\Filament\Resources\MediaPlans\Schemas;

use Filament\Schemas\Schema;
use App\Models\DataKol;
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
use Filament\Schemas\Components\Wizard;
use Filament\Schemas\Components\Wizard\Step;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Fieldset;
use Filament\Notifications\Notification;

class MediaPlanForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Wizard::make([
                    Step::make('Campaign Information')
                        ->icon('heroicon-m-document-text')
                        ->description('Campaign details & client info')
                        ->schema([
                            TextInput::make('brand')
                                ->label('Brand/Client')
                                ->placeholder('e.g., ICHITAN CHIZ TEA')
                                ->required(),
                            TextInput::make('pic_client')
                                ->label('PIC Client')
                                ->placeholder('e.g., Rohmah')
                                ->required(),
                            TextInput::make('campaign_type')
                                ->label('Campaign Type')
                                ->placeholder('e.g., Content Creation'),
                            TextInput::make('campaign_name')
                                ->label('Campaign Name')
                                ->placeholder('e.g., Ichitan Monthly Creator')
                                ->required(),
                            TextInput::make('campaign_period_start')
                                ->label('Campaign Period Start')
                                ->placeholder('e.g., November 2025'),
                            TextInput::make('campaign_period_end')
                                ->label('Campaign Period End')
                                ->placeholder('e.g., December 2025'),
                            TextInput::make('platform')
                                ->label('Platform')
                                ->placeholder('e.g., Social Media'),
                            TextInput::make('domisili')
                                ->label('Domisili')
                                ->placeholder('e.g., Jakarta'),
                        ])->columns(2),

                    Step::make('Select KOL')
                        ->icon('heroicon-m-user-group')
                        ->description('Choose or search for multiple KOLs')
                        ->schema([
                            // Header Summary Section (Live Accumulation)
                            Section::make('📊 Summary (Selected Only)')
                                ->description('Ringkasan otomatis dari KOL yang dicentang')
                                ->schema([
                                    Grid::make(4)
                                        ->schema([
                                            Placeholder::make('selected_count_display')
                                                ->label('Selected KOLs')
                                                ->content(fn(callable $get) => self::getSelectedCount($get('kols') ?? [])),
                                            Placeholder::make('total_rate_display')
                                                ->label('Total Rate')
                                                ->content(fn(callable $get) => 'Rp ' . number_format(self::getTotalRate($get('kols') ?? []), 0, ',', '.')),
                                            Placeholder::make('total_impression_display')
                                                ->label('Total Est. Views')
                                                ->content(fn(callable $get) => number_format(self::getTotalImpression($get('kols') ?? []), 0, ',', '.')),
                                            Placeholder::make('total_engagement_display')
                                                ->label('Total Est. Engagement')
                                                ->content(fn(callable $get) => number_format(self::getTotalEngagement($get('kols') ?? []), 0, ',', '.')),
                                        ]),
                                ])
                                ->collapsible()
                                ->extraAttributes(['class' => 'bg-primary-50 dark:bg-primary-900/20']),

                            Repeater::make('kols')
                                ->label('KOL List')
                                ->schema([
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

                                    // Row 2: Channel & Category & KOL Selection
                                    Fieldset::make('KOL Information')
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
                                                ->afterStateUpdated(function (callable $set) {
                                                    $set('categories', null);
                                                    $set('data_kol_id', null);
                                                })
                                                ->required()
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
                                                ->columnSpan(1),

                                            Select::make('data_kol_id')
                                                ->label('Select from Database')
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
                                                })
                                                ->searchable()
                                                ->columnSpan(2),

                                            TextInput::make('search_link')
                                                ->label('Or Search by Link')
                                                ->placeholder('https://instagram.com/username')
                                                ->helperText('Jika KOL tidak ada di database, input link profile')
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
                                                        $set('name', $profile['username']);
                                                        $set('links', [$profile['link_userprofile'] ?? $state]);
                                                        $set('followers', $profile['followers_count']);
                                                        $set('tier', $profile['tier']);
                                                        $set('er_percent', $profile['engagement_rate']);
                                                        $set('impression', $profile['average_impressions']);

                                                        // Calculate engagement
                                                        $followers = $profile['followers_count'];
                                                        $er = $profile['engagement_rate'];
                                                        $engagement = intval($followers * ($er / 100));
                                                        $set('engagement', $engagement);

                                                        // Show success notification
                                                        Notification::make()
                                                            ->title("✅ Data {$channel} berhasil diambil!")
                                                            ->success()
                                                            ->body("Profile @{$profile['username']} dengan " . number_format($followers) . " followers.")
                                                            ->send();

                                                        $set('search_link', null);

                                                    } catch (\Exception $e) {
                                                        Notification::make()
                                                            ->title("❌ Gagal mengambil data")
                                                            ->danger()
                                                            ->body($e->getMessage())
                                                            ->send();
                                                    }
                                                })
                                                ->columnSpan(2),
                                        ])->columns(3),

                                    // Row 3: KOL Details
                                    Fieldset::make('KOL Details')
                                        ->schema([
                                            TextInput::make('name')
                                                ->label('KOL Name')
                                                ->placeholder('Username / Nama')
                                                ->required()
                                                ->columnSpan(1),

                                            // Multiple Links Support
                                            TagsInput::make('links')
                                                ->label('Links')
                                                ->placeholder('Tekan Enter untuk tambah link')
                                                ->helperText('Bisa input multiple links (Profile + Portfolio)')
                                                ->columnSpan(2),

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
                                                })
                                                ->columnSpan(1),

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
                                                })
                                                ->columnSpan(1),
                                        ])->columns(3),

                                    // Row 4: Performance Metrics
                                    Fieldset::make('Performance Metrics')
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
                                                ->numeric()
                                                ->prefix('Rp')
                                                ->readOnly()
                                                ->dehydrated()
                                                ->helperText('Rate / Impression')
                                                ->columnSpan(1),

                                            TextInput::make('cpe')
                                                ->label('CPE')
                                                ->numeric()
                                                ->prefix('Rp')
                                                ->readOnly()
                                                ->dehydrated()
                                                ->helperText('Rate / Engagement')
                                                ->columnSpan(1),
                                        ])->columns(4),

                                    // Row 5: Scope of Work
                                    Fieldset::make('Scope of Work')
                                        ->schema([
                                            TagsInput::make('scope_items')
                                                ->label('Item Descriptions')
                                                ->placeholder('Ketik lalu tekan Enter untuk tambah item')
                                                ->helperText('Contoh: IG Reels, IG Story, IG Post')
                                                ->required()
                                                ->live()
                                                ->default([])
                                                ->columnSpan(3),

                                            TextInput::make('rate')
                                                ->label('Rate (from Internal Budget)')
                                                ->numeric()
                                                ->prefix('Rp')
                                                ->readOnly()
                                                ->dehydrated()
                                                ->default(0)
                                                ->helperText('Auto-filled from Internal Budget (Rounded)')
                                                ->columnSpan(1),
                                        ])->columns(4),

                                    // Notes
                                    Textarea::make('notes')
                                        ->label('Notes')
                                        ->placeholder('Special instructions or notes')
                                        ->rows(2)
                                        ->columnSpanFull(),
                                ])
                                ->columns(1)
                                ->collapsible()
                                ->itemLabel(function (array $state): ?string {
                                    $name = $state['name'] ?? 'New KOL';
                                    $selected = ($state['is_selected'] ?? false) ? '✅ ' : '';
                                    $rate = isset($state['rate']) && $state['rate'] > 0
                                        ? ' - Rp ' . number_format($state['rate'], 0, ',', '.')
                                        : '';
                                    return $selected . $name . $rate;
                                })
                                ->defaultItems(1)
                                ->addActionLabel('➕ Add Another KOL')
                                ->reorderable()
                                ->columnSpanFull()
                                ->live(),
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
            ->sum(fn($kol) => (float) ($kol['rate'] ?? 0));
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
