<?php

namespace App\Filament\Resources\DataKols\Pages;

use App\Filament\Resources\DataKols\DataKolResource;
use Filament\Resources\Pages\CreateRecord;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;
use App\Service\InstagramService;
use Filament\Notifications\Notification;

class CreateDataKol extends CreateRecord
{
    protected static string $resource = DataKolResource::class;

    public function form(Schema $schema): Schema
    {
        return $schema
            ->schema([
                Select::make('channel')
                    ->label('Channel')
                    ->options([
                        'Instagram' => 'Instagram',
                        'Tiktok' => 'Tiktok',
                        'Youtube' => 'Youtube',
                    ])
                    ->required()
                    ->default('Instagram')
                    ->live()
                    ->helperText('Pilih platform social media'),

                TextInput::make('link_userprofile')
                    ->label('Profile URL / Username')
                    ->required()
                    ->placeholder('Contoh: adrianhorning atau https://instagram.com/adrianhorning')
                    ->helperText('Masukkan username atau URL lengkap profil')
                    ->live(onBlur: true)
                    ->afterStateUpdated(function ($state, callable $set, callable $get) {
                        if (empty($state)) {
                            return;
                        }

                        $channel = $get('channel');

                        // Hanya support Instagram untuk saat ini
                        if ($channel !== 'Instagram') {
                            Notification::make()
                                ->warning()
                                ->title('Channel belum didukung')
                                ->body('Saat ini hanya Instagram yang support auto-fetch data.')
                                ->send();
                            return;
                        }

                        try {
                            $service = new InstagramService();
                            $profile = $service->getProfile($state);

                            // Auto-fill semua fields (hidden)
                            $set('username', $profile['username']);
                            $set('followers', $profile['followers_count']);
                            $set('tier', $profile['tier']);
                            $set('engagement_rate', $profile['engagement_rate']);
                            $set('engagements', $profile['total_engagements']);
                            $set('impressions', $profile['average_impressions']);

                            // Set category
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

                            // Set status default
                            $set('status', 'Active');

                            // Set notes
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
                            $notes[] = "Posts: " . number_format($profile['media_count']);

                            if (!empty($profile['external_url'])) {
                                $notes[] = "Website: {$profile['external_url']}";
                            }

                            $set('notes', implode("\n", $notes));
                            $set('terakhir_update', now()->format('Y-m-d'));

                            Notification::make()
                                ->success()
                                ->title('Data berhasil diambil!')
                                ->body("Profile @{$profile['username']} berhasil di-fetch dari Instagram.")
                                ->send();

                        } catch (\Exception $e) {
                            Notification::make()
                                ->danger()
                                ->title('Gagal mengambil data')
                                ->body($e->getMessage())
                                ->send();
                        }
                    }),
            ]);
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // Ensure all required fields have default values if not set
        $data['status'] = $data['status'] ?? 'Active';
        $data['terakhir_update'] = $data['terakhir_update'] ?? now()->format('Y-m-d');

        return $data;
    }
}
