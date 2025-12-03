<?php

namespace App\Filament\Resources\MediaPlans\Schemas;

use Filament\Schemas\Schema;
use App\Models\DataKol;
use App\Service\InstagramService;
use App\Service\TiktokService;
use App\Helpers\QuotationNumberGenerator;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Wizard;
use Filament\Schemas\Components\Wizard\Step;
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
                        ->description('Choose or search for KOL')
                        ->schema([
                            Select::make('channel')
                                ->label('Channel')
                                ->options([
                                    'Instagram' => 'Instagram',
                                    'Tiktok' => 'Tiktok',
                                ])
                                ->live(onBlur: true)
                                ->afterStateUpdated(fn(callable $set) => $set('categories', null))
                                ->afterStateUpdated(fn(callable $set) => $set('username', null))
                                ->required(),

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
                                ->afterStateUpdated(fn(callable $set) => $set('username', null))
                                ->searchable(),

                            Select::make('username')
                                ->label('Select KOL from Database')
                                ->options(function (callable $get) {
                                    $channel = $get('channel');
                                    $category = $get('categories');

                                    if (!$channel || !$category)
                                        return [];

                                    return DataKol::where('channel', $channel)
                                        ->where('category', $category)
                                        ->pluck('username', 'id')
                                        ->toArray();
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
                                    $set('link', $kol->link_userprofile);
                                    $set('followers', $kol->followers);
                                    $set('tier', $kol->tier);
                                    $set('er', $kol->engagement_rate);
                                    $set('avg_views', $kol->impressions);

                                    // Calculate derived fields
                                    $followers = (int) $kol->followers;
                                    $er = (float) $kol->engagement_rate;

                                    $engagement = intval($followers * ($er / 100));
                                    $set('engagement', $engagement);

                                    // Reset pricing
                                    $set('cpi_cpv', null);
                                    $set('cpe', null);
                                })
                                ->searchable(),

                            TextInput::make('username_manual')
                                ->label('Or Search KOL by Link')
                                ->placeholder('https://instagram.com/username or @username')
                                ->helperText('Jika KOL tidak ditemukan dalam database, cari berdasarkan link profile')
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
                                            default => null,
                                        };

                                        if (!$profile) {
                                            throw new \Exception('Channel tidak didukung');
                                        }

                                        // Auto-fill fields
                                        $set('username', $profile['username']);
                                        $set('link', $profile['link_userprofile'] ?? $state);
                                        $set('followers', $profile['followers_count']);
                                        $set('tier', $profile['tier']);
                                        $set('er', $profile['engagement_rate']);
                                        $set('avg_views', $profile['average_impressions']);
                                        $set('categories', $profile['category_name'] ?? '');

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

                                        $set('username_manual', null);

                                    } catch (\Exception $e) {
                                        Notification::make()
                                            ->title("❌ Gagal mengambil data")
                                            ->danger()
                                            ->body($e->getMessage())
                                            ->send();
                                    }
                                }),
                        ])->columns(2),

                    Step::make('KOL Metrics')
                        ->icon('heroicon-m-chart-bar')
                        ->description('Review KOL performance metrics')
                        ->schema([
                            TextInput::make('link')
                                ->label('Profile Link')
                                ->url()
                                ->readOnly()
                                ->dehydrated()
                                ->columnSpanFull(),

                            TextInput::make('followers')
                                ->label('Followers')
                                ->numeric()
                                ->readOnly()
                                ->dehydrated(),

                            TextInput::make('tier')
                                ->label('Tier')
                                ->readOnly()
                                ->dehydrated(),

                            TextInput::make('er')
                                ->label('ER %')
                                ->numeric()
                                ->suffix('%')
                                ->readOnly()
                                ->dehydrated(),

                            TextInput::make('avg_views')
                                ->label('Avg Views/Impression')
                                ->numeric()
                                ->readOnly()
                                ->dehydrated(),

                            TextInput::make('engagement')
                                ->label('Engagement')
                                ->numeric()
                                ->readOnly()
                                ->dehydrated(),
                        ])->columns(3),

                    Step::make('Scope & Pricing')
                        ->icon('heroicon-m-banknotes')
                        ->description('Define scope and pricing details')
                        ->schema([
                            TextInput::make('scopeofwork')
                                ->label('Scope of Work (Item)')
                                ->placeholder('e.g., IG Reels, TT Video')
                                ->required(),

                            TextInput::make('rate')
                                ->label('Rate')
                                ->numeric()
                                ->prefix('Rp ')
                                ->live(onBlur: true)
                                ->afterStateUpdated(function (?string $state, callable $set, callable $get) {
                                    if (empty($state))
                                        return;

                                    $rate = (int) $state;
                                    $avg_views = (int) $get('avg_views');
                                    $engagement = (int) $get('engagement');

                                    // Calculate CPI/CPV
                                    if ($avg_views > 0) {
                                        $cpi_cpv = $rate / $avg_views;
                                        $set('cpi_cpv', intval($cpi_cpv));
                                    }

                                    // Calculate CPE
                                    if ($engagement > 0) {
                                        $cpe = $rate / $engagement;
                                        $set('cpe', intval($cpe));
                                    }
                                })
                                ->required(),

                            TextInput::make('cpi_cpv')
                                ->label('CPI/CPV')
                                ->numeric()
                                ->prefix('Rp ')
                                ->readOnly()
                                ->dehydrated(),

                            TextInput::make('cpe')
                                ->label('CPE')
                                ->numeric()
                                ->prefix('Rp ')
                                ->readOnly()
                                ->dehydrated(),

                            Textarea::make('notes')
                                ->label('Notes')
                                ->placeholder('Add any special instructions or deliverable details')
                                ->columnSpanFull(),
                        ])->columns(2),
                ])
                    ->columnSpanFull()
                    ->skippable()
            ]);
    }
}
