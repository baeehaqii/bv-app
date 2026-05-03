<?php

namespace App\Filament\Resources\DataKols\Pages;

use App\Filament\Resources\DataKols\DataKolResource;
use App\Filament\Resources\DataKols\Widgets\KolStatsWidget;
use App\Filament\Widgets\EngagementRateDistributionChart;
use App\Filament\Widgets\KolByCategoryChart;
use App\Filament\Widgets\KolByChannelChart;
use App\Filament\Widgets\KolByTierChart;
use App\Filament\Widgets\TopKolByFollowersChart;
use App\Models\DataKol;
use Filament\Actions\CreateAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use App\Service\InstagramService;
use App\Service\TiktokService;
use App\Service\YoutubeChannelsService;
use App\Service\YoutubeShortsService;
use Illuminate\Database\Eloquent\Model;
use Filament\Actions\Action;

class ListDataKols extends ListRecords
{
    protected static string $resource = DataKolResource::class;

    public $dateFilter = 'all';

    public function getHeaderWidgetsColumns(): int|array
    {
        return 2;
    }

    protected function getHeaderWidgets(): array
    {
        return [
            KolStatsWidget::class,
        ];
    }

    protected function getWidgetsData(): array
    {
        return [
            'dateFilter' => $this->dateFilter,
        ];
    }

    public function updatedDateFilter()
    {
        // This will trigger widget refresh when filter changes
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('dateFilter')
                ->label(fn() => match ($this->dateFilter) {
                    'today' => 'Filter: Today',
                    '7days' => 'Filter: 7 Days',
                    '14days' => 'Filter: 14 Days',
                    '30days' => 'Filter: 30 Days',
                    '60days' => 'Filter: 60 Days',
                    '90days' => 'Filter: 90 Days',
                    default => 'Filter: All Time',
                })
                ->icon('heroicon-o-funnel')
                ->color('primary')
                ->form([
                    Select::make('filter')
                        ->label('Select Date Range')
                        ->options([
                            'today' => 'Today',
                            '7days' => '7 Days',
                            '14days' => '14 Days',
                            '30days' => '30 Days',
                            '60days' => '60 Days',
                            '90days' => '90 Days',
                            'all' => 'All Time',
                        ])
                        ->default($this->dateFilter)
                        ->required()
                        ->native(false),
                ])
                ->action(function (array $data) {
                    $this->dateFilter = $data['filter'];
                })
                ->modalHeading('Filter by Date Range')
                ->modalSubmitActionLabel('Apply Filter')
                ->modalWidth('sm'),

            CreateAction::make()
                ->label('New Data KOL')
                ->icon('heroicon-o-plus')
                ->form([
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
                        ->helperText('Pilih platform social media'),

                    TextInput::make('link_userprofile')
                        ->label(fn($get) => match ($get('channel')) {
                            'Instagram' => 'Instagram Profile URL / Username',
                            'Tiktok' => 'TikTok Profile URL / Username',
                            'Youtube Channels' => 'YouTube Channel URL / Username',
                            'Youtube Shorts' => 'YouTube Channel URL / Username',
                            default => 'Profile URL / Username',
                        })
                        ->required()
                        ->placeholder(fn($get) => match ($get('channel')) {
                            'Instagram' => 'Contoh: adrianhorning atau https://instagram.com/adrianhorning',
                            'Tiktok' => 'Contoh: @stoolpresidente atau https://tiktok.com/@stoolpresidente',
                            'Youtube Channels' => 'Contoh: @ThePatMcAfeeShow atau https://www.youtube.com/@ThePatMcAfeeShow',
                            'Youtube Shorts' => 'Contoh: @ThePatMcAfeeShow atau https://www.youtube.com/@ThePatMcAfeeShow',
                            default => 'Contoh: username atau URL',
                        })
                        ->helperText('Masukkan username atau URL lengkap profil.')
                        ->suffixIcon('heroicon-m-magnifying-glass'),
                ])
                ->using(function (array $data, string $model): Model {
                    try {
                        $profile = match ($data['channel']) {
                            'Instagram' => (new InstagramService())->getProfile($data['link_userprofile']),
                            'Tiktok' => (new TiktokService())->getProfile($data['link_userprofile']),
                            'Youtube Channels' => (new YoutubeChannelsService())->getProfile($data['link_userprofile']),
                            'Youtube Shorts' => (new YoutubeShortsService())->getProfile($data['link_userprofile']),
                            default => null,
                        };

                        if (!$profile) {
                            Notification::make()
                                ->warning()
                                ->title('Channel belum didukung')
                                ->body("Channel {$data['channel']} belum support auto-fetch data.")
                                ->send();

                            // Create basic data
                            return $model::create($data);
                        }

                        // Prepare data lengkap
                        $finalData = [
                            'link_userprofile' => $data['link_userprofile'],
                            'channel' => $data['channel'],
                            'username' => $profile['username'],
                            'followers' => $profile['followers_count'],
                            'tier' => $profile['tier'],
                            'engagement_rate' => $profile['engagement_rate'],
                            'engagements' => $profile['total_engagements'],
                            'impressions' => $profile['average_impressions'],
                            'status' => 'New List',
                            'terakhir_update' => now()->format('Y-m-d'),
                        ];

                        // Set category (as array for JSON column)
                        if (!empty($profile['business_category_name']) && $profile['business_category_name'] !== 'None') {
                            $finalData['category'] = [$profile['category_name'] ?? $profile['business_category_name']];
                        } elseif (!empty($profile['category_name'])) {
                            $finalData['category'] = [$profile['category_name']];
                        }

                        // Set contact fields
                        if (!empty($profile['full_name'])) {
                            $finalData['full_name'] = $profile['full_name'];
                        }
                        if (!empty($profile['business_email'])) {
                            $finalData['email'] = $profile['business_email'];
                            $finalData['contact'] = $profile['business_email'];
                        }
                        if (!empty($profile['business_phone_number'])) {
                            $finalData['wa_number'] = $profile['business_phone_number'];
                            if (empty($profile['business_email'])) {
                                $finalData['contact'] = $profile['business_phone_number'];
                            }
                        }

                        // Set notes
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

                        $mediaLabel = match ($data['channel']) {
                            'Tiktok' => 'Videos',
                            'Youtube Channels' => 'Videos',
                            'Youtube Shorts' => 'Shorts',
                            default => 'Posts',
                        };
                        $notes[] = "{$mediaLabel}: " . number_format($profile['media_count']);

                        if (!empty($profile['external_url'])) {
                            $notes[] = "Website: {$profile['external_url']}";
                        }

                        $finalData['notes'] = implode("\n", $notes);

                        return $model::create($finalData);

                    } catch (\Exception $e) {
                        // Jika gagal fetch, throw exception agar modal tidak tertutup dan error muncul
                        $channelLabel = match ($data['channel']) {
                            'Instagram' => 'Instagram',
                            'Tiktok' => 'TikTok',
                            'Youtube Channels' => 'YouTube Channels',
                            'Youtube Shorts' => 'YouTube Shorts',
                            default => $data['channel'],
                        };
                        throw new \Exception("Gagal mengambil data {$channelLabel}: " . $e->getMessage());
                    }
                })
                ->successNotification(
                    Notification::make()
                        ->success()
                        ->title('Data berhasil diambil dan disimpan!')
                        ->body('Redirecting ke halaman edit...')
                )
                ->after(function ($record) {
                    // Redirect to edit page after create
                    return redirect()->to(
                        DataKolResource::getUrl('edit', ['record' => $record])
                    );
                })
                ->createAnother(false)
                ->modalWidth('md'),
        ];
    }
}
