<?php

namespace App\Filament\Resources\DataKols\Schemas;

use Filament\Schemas\Schema;
use App\Service\InstagramService;
use App\Service\TiktokService;
use App\Service\YoutubeChannelsService;
use App\Service\YoutubeShortsService;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\Section;
use Filament\Forms\Components\DatePicker;

class DataKolForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
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
                                'Instagram' => 'https://www.instagram.com/username/ atau username saja',
                                'Tiktok' => 'https://www.tiktok.com/@username atau @username saja',
                                'Youtube Channels' => 'https://www.youtube.com/@username atau username saja',
                                'Youtube Shorts' => 'https://www.youtube.com/@username atau username saja',
                                default => 'Profile URL',
                            })
                            ->helperText(fn(callable $get) => match ($get('channel')) {
                                'Instagram' => '📋 Masukkan URL/username, tekan Tab/Enter, kemudian tunggu data ter-fetch dari Instagram',
                                'Tiktok' => '📋 Masukkan URL/username, tekan Tab/Enter, kemudian tunggu data ter-fetch dari TikTok',
                                'Youtube Channels' => '📋 Masukkan URL/username, tekan Tab/Enter, kemudian tunggu data ter-fetch dari YouTube',
                                'Youtube Shorts' => '📋 Masukkan URL/username, tekan Tab/Enter, kemudian tunggu data ter-fetch dari YouTube Shorts',
                                default => '📋 Masukkan URL/username dan tunggu data ter-fetch',
                            })
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

                                    // Auto-fill fields dari profile data
                                    $set('username', $profile['username']);
                                    $set('followers', $profile['followers_count']);
                                    $set('tier', $profile['tier']);
                                    $set('engagement_rate', $profile['engagement_rate']);
                                    $set('engagements', $profile['total_engagements']);
                                    $set('impressions', $profile['average_impressions']);

                                    // Set category jika ada
                                    if (!empty($profile['business_category_name']) && $profile['business_category_name'] !== 'None') {
                                        $set('category', $profile['category_name'] ?? $profile['business_category_name']);
                                    } elseif (!empty($profile['category_name'])) {
                                        $set('category', $profile['category_name']);
                                    }

                                    // Set contact
                                    if (!empty($profile['business_email'])) {
                                        $set('contact', $profile['business_email']);
                                    } elseif (!empty($profile['business_phone_number'])) {
                                        $set('contact', $profile['business_phone_number']);
                                    }

                                    // Set notes dengan info tambahan
                                    $notes = [];
                                    if (!empty($profile['full_name'])) {
                                        $notes[] = "Nama: {$profile['full_name']}";
                                    }
                                    if (!empty($profile['biography'])) {
                                        $notes[] = "Bio: {$profile['biography']}";
                                    }
                                    if ($profile['is_verified']) {
                                        $notes[] = "✓ Verified Account";
                                    }
                                    if ($profile['is_business_account']) {
                                        $notes[] = "Business Account";
                                    }
                                    $notes[] = "Tier: {$profile['tier']}";
                                    $notes[] = "Engagement Rate: {$profile['engagement_rate']}%";
                                    $notes[] = "Avg Impressions: " . number_format($profile['average_impressions']);
                                    $notes[] = "Avg Likes: " . number_format($profile['average_likes']);
                                    $notes[] = "Avg Comments: " . number_format($profile['average_comments']);
                                    $notes[] = "Following: " . number_format($profile['following_count']);

                                    $mediaLabel = match ($channel) {
                                        'Tiktok' => 'Videos',
                                        'Youtube Channels' => 'Videos',
                                        'Youtube Shorts' => 'Shorts',
                                        default => 'Posts',
                                    };
                                    $notes[] = "{$mediaLabel}: " . number_format($profile['media_count']);

                                    if (!empty($profile['external_url'])) {
                                        $notes[] = "Website: {$profile['external_url']}";
                                    }

                                    $set('notes', implode("\n", $notes));
                                    $set('terakhir_update', now()->format('Y-m-d'));

                                    // Show success notification
                                    $channelLabel = match ($channel) {
                                        'Instagram' => 'Instagram',
                                        'Tiktok' => 'TikTok',
                                        'Youtube Channels' => 'YouTube Channels',
                                        'Youtube Shorts' => 'YouTube Shorts',
                                        default => $channel,
                                    };

                                    $followerLabel = match ($channel) {
                                        'Youtube Channels', 'Youtube Shorts' => 'subscribers',
                                        default => 'followers',
                                    };

                                    Notification::make()
                                        ->title("✅ Data {$channelLabel} berhasil diambil!")
                                        ->success()
                                        ->body("Profile @{$profile['username']} dengan " . number_format($profile['followers_count']) . " {$followerLabel}. Silahkan klik Create untuk menyimpan.")
                                        ->send();

                                } catch (\Exception $e) {
                                    // Show error notification
                                    $channelLabel = match ($get('channel')) {
                                        'Instagram' => 'Instagram',
                                        'Tiktok' => 'TikTok',
                                        'Youtube Channels' => 'YouTube Channels',
                                        'Youtube Shorts' => 'YouTube Shorts',
                                        default => $get('channel'),
                                    };

                                    Notification::make()
                                        ->title("❌ Gagal mengambil data {$channelLabel}")
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

                        TextInput::make('engagement_rate')
                            ->label('Engagement Rate')
                            ->suffix('%')
                            ->numeric()
                            ->readOnly()
                            ->dehydrated()
                            ->helperText('Otomatis dihitung dari rata-rata 9 post terakhir')
                            ->prefixIcon('heroicon-o-chart-bar'),

                        TextInput::make('engagements')
                            ->label('Total Engagements')
                            ->numeric()
                            ->readOnly()
                            ->dehydrated()
                            ->hintIcon('heroicon-m-question-mark-circle', tooltip: 'Total likes + comments dari 12 post terakhir')
                            ->prefixIcon('heroicon-o-heart'),

                        TextInput::make('impressions')
                            ->label('Avg Impressions')
                            ->numeric()
                            ->readOnly()
                            ->dehydrated()
                            ->hintIcon('heroicon-m-question-mark-circle', tooltip: 'Otomatis dari video views atau estimasi 2.5x engagement')
                            ->prefixIcon('heroicon-o-eye'),

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
                            ->hintIcon('heroicon-m-question-mark-circle', tooltip: 'Otomatis: Nano (1K-9K) | Micro (10K-99K) | Macro (100K-999K) | Mega (1M+)')
                            ->prefixIcon('heroicon-o-star')
                            ->extraAttributes(fn($state) => [
                                'style' => match ($state) {
                                    'Mega' => 'color: #10b981; font-weight: bold;',
                                    'Macro' => 'color: #f59e0b; font-weight: bold;',
                                    'Micro' => 'color: #3b82f6; font-weight: bold;',
                                    'Nano' => 'color: #06b6d4; font-weight: bold;',
                                    default => 'color: #6b7280;',
                                }
                            ]),

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
                            ->label('Category'),

                        Select::make('status')
                            ->label('Status')
                            ->options([
                                'New List' => 'New List',
                                'Approching ' => 'Approching',
                                'Waiting Feedback' => 'Waiting Feedback',
                                'Not Available' => 'Not Available',
                            ])
                            ->placeholder('Pilih Status'),
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
                            ->rows(5)
                            ->columnSpanFull(),
                    ])->columns(3),
            ]);
    }
}
