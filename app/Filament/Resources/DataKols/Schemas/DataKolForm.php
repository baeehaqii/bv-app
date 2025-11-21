<?php

namespace App\Filament\Resources\DataKols\Schemas;

use App\Service\InstagramService;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Schemas\Schema;
use Filament\Notifications\Notification;
use Filament\Forms\Components\Select;

class DataKolForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('link_userprofile')
                    ->label('Instagram Profile URL')
                    ->placeholder('https://www.instagram.com/username/ atau username saja')
                    ->required()
                    ->live(onBlur: true)
                    ->afterStateUpdated(function (?string $state, callable $set) {
                        if (empty($state)) {
                            return;
                        }

                        try {
                            $service = new InstagramService();
                            $profile = $service->getProfile($state);

                            // Auto-fill fields dari Instagram data
                            $set('username', $profile['username']);
                            $set('followers', $profile['followers_count']);
                            $set('channel', 'Instagram');
                            $set('tier', $profile['tier']); // Set tier otomatis
                            $set('engagement_rate', $profile['engagement_rate']); // Set ER otomatis
                            $set('engagements', $profile['total_engagements']); // Set total engagements
                            $set('impressions', $profile['average_impressions']); // Set avg impressions
            
                            // Set category dari business category atau category name
                            if (!empty($profile['business_category_name']) && $profile['business_category_name'] !== 'None') {
                                $set('category', $profile['category_name'] ?? $profile['business_category_name']);
                            } elseif (!empty($profile['category_name'])) {
                                $set('category', $profile['category_name']);
                            }

                            // Set contact dari business email jika ada
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
                            $notes[] = "Tier: {$profile['tier']}"; // Tambahkan tier di notes
                            $notes[] = "Engagement Rate: {$profile['engagement_rate']}%";
                            $notes[] = "Avg Impressions: " . number_format($profile['average_impressions']);
                            $notes[] = "Avg Likes: " . number_format($profile['average_likes']);
                            $notes[] = "Avg Comments: " . number_format($profile['average_comments']);
                            $notes[] = "Following: " . number_format($profile['following_count']);
                            $notes[] = "Posts: " . number_format($profile['media_count']);

                            if (!empty($profile['external_url'])) {
                                $notes[] = "Website: {$profile['external_url']}";
                            }

                            $set('notes', implode("\n", $notes));
                            $set('terakhir_update', now()->format('Y-m-d'));

                            // Show success notification
                            Notification::make()
                                ->title('Data Instagram berhasil diambil!')
                                ->success()
                                ->body("Profile @{$profile['username']} dengan " . number_format($profile['followers_count']) . " followers")
                                ->send();

                        } catch (\Exception $e) {
                            // Show error notification
                            Notification::make()
                                ->title('Gagal mengambil data Instagram')
                                ->danger()
                                ->body($e->getMessage())
                                ->send();
                        }
                    })
                    ->helperText('Data akan otomatis terisi saat Anda memasukkan URL Instagram'),

                TextInput::make('username')
                    ->label('Username')
                    ->disabled()
                    ->dehydrated()
                    ->prefixIcon('heroicon-o-at-symbol'),

                Select::make('channel')
                    ->label('Channel')
                    ->options([
                        'Instagram' => 'Instagram',
                        'Tiktok' => 'Tiktok',
                        'Youtube'=> 'Youtube',
                    ])
                    ->default('Instagram')
                    ->disabled()
                    ->dehydrated(),

                TextInput::make('followers')
                    ->label('Followers')
                    ->numeric()
                    ->disabled()
                    ->dehydrated()
                    ->prefixIcon('heroicon-o-users'),

                TextInput::make('tier')
                    ->label('Tier')
                    ->disabled()
                    ->dehydrated()
                    ->helperText('Otomatis: Nano (1K-9K) | Micro (10K-99K) | Macro (100K-999K) | Mega (1M+)')
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

                TextInput::make('engagement_rate')
                    ->label('Engagement Rate')
                    ->suffix('%')
                    ->numeric()
                    ->disabled()
                    ->dehydrated()
                    ->helperText('Otomatis dihitung dari rata-rata 9 post terakhir')
                    ->prefixIcon('heroicon-o-chart-bar'),

                TextInput::make('engagements')
                    ->label('Total Engagements')
                    ->numeric()
                    ->disabled()
                    ->dehydrated()
                    ->helperText('Total likes + comments dari 12 post terakhir')
                    ->prefixIcon('heroicon-o-heart'),

                TextInput::make('impressions')
                    ->label('Avg Impressions')
                    ->numeric()
                    ->disabled()
                    ->dehydrated()
                    ->helperText('Otomatis dari video views atau estimasi 2.5x engagement')
                    ->prefixIcon('heroicon-o-eye'),

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


            ]);
    }
}
