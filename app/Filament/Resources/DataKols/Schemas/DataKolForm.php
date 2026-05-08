<?php

namespace App\Filament\Resources\DataKols\Schemas;

use Filament\Schemas\Schema;
use App\Service\InstagramService;
use App\Service\TiktokService;
use App\Service\YoutubeChannelsService;
use App\Service\YoutubeShortsService;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\Section;
use Filament\Forms\Components\DatePicker;
use Filament\Support\RawJs;

class DataKolForm
{
    private static array $channelOptions = [
        'Instagram' => 'Instagram',
        'Tiktok' => 'TikTok',
        'Threads' => 'Threads',
        'Youtube Channels' => 'YouTube Channels',
        'Youtube Shorts' => 'YouTube Shorts',
        'Facebook' => 'Facebook',
        'Talent' => 'Talent',
        'X' => 'X (Twitter)',
    ];

    private static array $scrapableChannels = ['Instagram', 'Tiktok', 'Youtube Channels', 'Youtube Shorts'];

    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Rate Card Per Channel')
                    ->description('Input rate card untuk setiap channel dan SOW. Setiap perubahan rate otomatis tersimpan sebagai histori.')
                    ->columnSpanFull()
                    ->schema([
                        Repeater::make('rateCards')
                            ->label('')
                            ->relationship('rateCards')
                            ->schema([
                                Select::make('channel')
                                    ->label('Channel')
                                    ->options([
                                        'Instagram' => 'Instagram',
                                        'Tiktok' => 'TikTok',
                                        'Threads' => 'Threads',
                                        'Youtube Channels' => 'YouTube Channels',
                                        'Youtube Shorts' => 'YouTube Shorts',
                                        'Facebook' => 'Facebook',
                                        'Talent' => 'Talent',
                                        'X' => 'X (Twitter)',
                                    ])
                                    ->required()
                                    ->columnSpan(1),

                                TextInput::make('sow')
                                    ->label('SOW (Scope of Work)')
                                    ->placeholder('Contoh: 1 Feed + 3 Stories')
                                    ->columnSpan(1),

                                TextInput::make('rate')
                                    ->label('Rate Card')
                                    ->prefix('Rp')
                                    ->numeric()
                                    ->mask(RawJs::make('$money($input)'))
                                    ->dehydrateStateUsing(fn($state) => $state ? (float) str_replace(['.', ','], ['', '.'], preg_replace('/[^\d,.]/', '', $state)) : null)
                                    ->formatStateUsing(fn($state) => $state ? number_format((float) $state, 0, ',', '.') : null)
                                    ->placeholder('0')
                                    ->columnSpan(1),

                                DatePicker::make('valid_from')
                                    ->label('Berlaku Dari')
                                    ->default(now())
                                    ->columnSpan(1),

                                FileUpload::make('file_path')
                                    ->label('Upload File Rate Card')
                                    ->helperText('PDF atau gambar (maks. 5MB)')
                                    ->acceptedFileTypes(['application/pdf', 'image/jpeg', 'image/png', 'image/webp'])
                                    ->maxSize(5120)
                                    ->directory('kol-rate-cards')
                                    ->downloadable()
                                    ->openable()
                                    ->columnSpan(2),

                                Textarea::make('notes')
                                    ->label('Catatan')
                                    ->rows(2)
                                    ->placeholder('Catatan tambahan tentang rate ini...')
                                    ->columnSpan(2),
                            ])
                            ->columns(4)
                            ->addActionLabel('+ Tambah Rate Card')
                            ->defaultItems(0)
                            ->reorderable(false)
                            ->collapsible()
                            ->itemLabel(fn(array $state): ?string => collect([
                                $state['channel'] ?? null,
                                $state['sow'] ?? null,
                                $state['rate'] ? 'Rp ' . number_format((float) $state['rate'], 0, ',', '.') : null,
                            ])->filter()->implode(' — ') ?: 'Rate Card Baru'),
                    ]),

                Section::make('Social Media Data')
                    ->columnSpanFull()
                    ->schema([
                        Select::make('channel')
                            ->label('Channel')
                            ->options(self::$channelOptions)
                            ->live(onBlur: true)
                            ->afterStateUpdated(fn(callable $set) => $set('link_userprofile', null))
                            ->required(),

                        TextInput::make('link_userprofile')
                            ->label(fn(callable $get) => match ($get('channel')) {
                                'Instagram' => 'Instagram Profile URL',
                                'Tiktok' => 'TikTok Profile URL',
                                'Youtube Channels' => 'YouTube Channel URL',
                                'Youtube Shorts' => 'YouTube Shorts URL',
                                'Threads' => 'Threads Profile URL',
                                'Facebook' => 'Facebook Profile/Page URL',
                                'Talent' => 'Profil Talent / Portfolio URL',
                                'X' => 'X (Twitter) Profile URL',
                                default => 'Profile URL',
                            })
                            ->placeholder(fn(callable $get) => match ($get('channel')) {
                                'Instagram' => 'https://www.instagram.com/username/ atau username saja',
                                'Tiktok' => 'https://www.tiktok.com/@username atau @username saja',
                                'Youtube Channels' => 'https://www.youtube.com/@username atau username saja',
                                'Youtube Shorts' => 'https://www.youtube.com/@username atau username saja',
                                'Threads' => 'https://www.threads.net/@username',
                                'Facebook' => 'https://www.facebook.com/pagename',
                                'Talent' => 'Link portfolio atau profil talent',
                                'X' => 'https://x.com/username atau @username',
                                default => 'Profile URL',
                            })
                            ->helperText(fn(callable $get) => in_array($get('channel'), self::$scrapableChannels)
                                ? '📋 Masukkan URL/username, tekan Tab/Enter, kemudian tunggu data ter-fetch otomatis'
                                : '📋 Masukkan URL/link profil channel ini')
                            ->required(fn(callable $get) => !empty($get('channel')))
                            ->live(onBlur: true)
                            ->afterStateUpdated(function (?string $state, callable $set, callable $get) {
                                if (empty($state) || empty($get('channel'))) {
                                    return;
                                }

                                $channel = $get('channel');

                                if (!in_array($channel, self::$scrapableChannels)) {
                                    return;
                                }

                                try {
                                    $profile = match ($channel) {
                                        'Instagram' => (new InstagramService())->getProfile($state),
                                        'Tiktok' => (new TiktokService())->getProfile($state),
                                        'Youtube Channels' => (new YoutubeChannelsService())->getProfile($state),
                                        'Youtube Shorts' => (new YoutubeShortsService())->getProfile($state),
                                        default => null,
                                    };

                                    if (!$profile) {
                                        throw new \Exception('Channel tidak didukung untuk auto-fetch');
                                    }

                                    $set('username', $profile['username']);
                                    $set('followers', $profile['followers_count']);
                                    $set('tier', $profile['tier']);
                                    $set('engagement_rate', $profile['engagement_rate']);
                                    $set('engagements', $profile['total_engagements']);
                                    $set('impressions', $profile['average_impressions']);

                                    if (!empty($profile['business_category_name']) && $profile['business_category_name'] !== 'None') {
                                        $set('category', [$profile['category_name'] ?? $profile['business_category_name']]);
                                    } elseif (!empty($profile['category_name'])) {
                                        $set('category', [$profile['category_name']]);
                                    }

                                    // Auto-fill contact fields
                                    if (!empty($profile['full_name'])) {
                                        $set('full_name', $profile['full_name']);
                                    }
                                    if (!empty($profile['business_email'])) {
                                        $set('email', $profile['business_email']);
                                        $set('contact', $profile['business_email']);
                                    }
                                    if (!empty($profile['business_phone_number'])) {
                                        $set('wa_number', $profile['business_phone_number']);
                                        if (empty($profile['business_email'])) {
                                            $set('contact', $profile['business_phone_number']);
                                        }
                                    }

                                    $notes = [];
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
                                        'Tiktok', 'Youtube Channels' => 'Videos',
                                        'Youtube Shorts' => 'Shorts',
                                        default => 'Posts',
                                    };
                                    $notes[] = "{$mediaLabel}: " . number_format($profile['media_count']);

                                    if (!empty($profile['external_url'])) {
                                        $notes[] = "Website: {$profile['external_url']}";
                                    }

                                    $set('notes', implode("\n", $notes));
                                    $set('terakhir_update', now()->format('Y-m-d'));

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
                                        ->body("Profile @{$profile['username']} dengan " . number_format($profile['followers_count']) . " {$followerLabel}.")
                                        ->send();

                                } catch (\Exception $e) {
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
                            ->multiple()
                            ->label('Category')
                            ->searchable(),

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
                        TextInput::make('full_name')
                            ->label('Nama Lengkap KOL')
                            ->placeholder('Nama asli / nama lengkap')
                            ->prefixIcon('heroicon-o-user'),

                        TextInput::make('email')
                            ->label('Email')
                            ->email()
                            ->placeholder('email@example.com')
                            ->prefixIcon('heroicon-o-envelope'),

                        TextInput::make('wa_number')
                            ->label('No WhatsApp')
                            ->tel()
                            ->placeholder('08xxxxxxxxxx')
                            ->prefixIcon('heroicon-o-phone'),

                        TextInput::make('contact')
                            ->label('Contact (Legacy)')
                            ->helperText('Field lama — gunakan Email & WA di atas')
                            ->disabled()
                            ->dehydrated()
                            ->visible(fn($state) => !empty($state)),

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
