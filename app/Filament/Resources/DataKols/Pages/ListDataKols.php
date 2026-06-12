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
use Filament\Forms\Components\Repeater;
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
                ->modalHeading('Create Database KOL')
                ->form([
                    Repeater::make('channels')
                        ->label('Platform / Channel')
                        ->helperText('Satu KOL bisa punya banyak platform. Tambahkan tiap channel — masing-masing di-fetch & disimpan terpisah.')
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
                                ->default('Instagram'),

                            TextInput::make('link_userprofile')
                                ->label('Profile URL / Username')
                                ->required()
                                ->placeholder(fn($get) => match ($get('channel')) {
                                    'Tiktok' => 'Contoh: @stoolpresidente atau https://tiktok.com/@stoolpresidente',
                                    'Youtube Channels', 'Youtube Shorts' => 'Contoh: @ThePatMcAfeeShow atau URL channel',
                                    default => 'Contoh: adrianhorning atau https://instagram.com/adrianhorning',
                                })
                                ->suffixIcon('heroicon-m-magnifying-glass'),
                        ])
                        ->columns(2)
                        ->minItems(1)
                        ->defaultItems(1)
                        ->addActionLabel('+ Tambah Channel')
                        ->reorderable(false),
                ])
                ->using(function (array $data, string $model): Model {
                    $rows = array_filter(
                        $data['channels'] ?? [],
                        fn($row) => !empty($row['channel']) && !empty($row['link_userprofile'])
                    );

                    if (empty($rows)) {
                        throw new \Exception('Minimal isi 1 channel beserta URL/username profil.');
                    }

                    $created = 0;
                    $updated = 0;
                    $failed = [];
                    $firstRecord = null;

                    foreach ($rows as $row) {
                        $result = $this->fetchAndSaveKol($row, $model);

                        if ($result['status'] === 'failed') {
                            $failed[] = "{$row['channel']}: {$result['message']}";
                            continue;
                        }

                        $result['status'] === 'updated' ? $updated++ : $created++;
                        $firstRecord ??= $result['record'];
                    }

                    if (!$firstRecord) {
                        throw new \Exception('Semua channel gagal di-fetch. ' . implode(' | ', $failed));
                    }

                    $summary = [];
                    if ($created) {
                        $summary[] = "{$created} dibuat";
                    }
                    if ($updated) {
                        $summary[] = "{$updated} diperbarui";
                    }
                    if ($failed) {
                        $summary[] = count($failed) . ' gagal';
                    }

                    Notification::make()
                        ->title('Data KOL tersimpan')
                        ->body(implode(', ', $summary) . ($failed ? ' — ' . implode(' | ', $failed) : ''))
                        ->success()
                        ->send();

                    return $firstRecord;
                })
                ->successNotification(null)
                ->createAnother(false)
                ->modalWidth('lg'),
        ];
    }

    /**
     * Fetch profil 1 channel via service yang ada lalu simpan/update sebagai 1 record DataKol.
     *
     * @param  array{channel: string, link_userprofile: string}  $row
     * @return array{status: string, record?: Model, message?: string}
     */
    private function fetchAndSaveKol(array $row, string $model): array
    {
        try {
            $profile = match ($row['channel']) {
                'Instagram' => (new InstagramService())->getProfile($row['link_userprofile']),
                'Tiktok' => (new TiktokService())->getProfile($row['link_userprofile']),
                'Youtube Channels' => (new YoutubeChannelsService())->getProfile($row['link_userprofile']),
                'Youtube Shorts' => (new YoutubeShortsService())->getProfile($row['link_userprofile']),
                default => null,
            };

            if (!$profile) {
                return ['status' => 'failed', 'message' => 'channel belum didukung auto-fetch'];
            }

            $finalData = [
                'link_userprofile' => $row['link_userprofile'],
                'channel' => $row['channel'],
                'username' => $profile['username'],
                'followers' => $profile['followers_count'],
                'tier' => $profile['tier'],
                'engagement_rate' => $profile['engagement_rate'],
                'engagements' => $profile['total_engagements'],
                'impressions' => $profile['average_impressions'],
                'status' => 'New List',
                'terakhir_update' => now()->format('Y-m-d'),
            ];

            $categoryName = $profile['category_name']
                ?: (($profile['business_category_name'] ?? null) !== 'None' ? ($profile['business_category_name'] ?? null) : null);
            if (!empty($categoryName)) {
                $finalData['category'] = [$categoryName];
            }

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

            $finalData['notes'] = $this->buildProfileNotes($profile, $row['channel']);

            // Cegah duplikasi: 1 username pada channel yang sama hanya 1 baris.
            $existing = $model::where('username', $profile['username'])
                ->where('channel', $row['channel'])
                ->first();

            if ($existing) {
                $existing->update($finalData);

                return ['status' => 'updated', 'record' => $existing];
            }

            return ['status' => 'created', 'record' => $model::create($finalData)];
        } catch (\Throwable $e) {
            return ['status' => 'failed', 'message' => $e->getMessage()];
        }
    }

    /**
     * @param  array<string, mixed>  $profile
     */
    private function buildProfileNotes(array $profile, string $channel): string
    {
        $notes = [];
        if (!empty($profile['biography'])) {
            $notes[] = "Bio: {$profile['biography']}";
        }
        if (!empty($profile['is_verified'])) {
            $notes[] = '✓ Verified Account';
        }
        if (!empty($profile['is_business_account'])) {
            $notes[] = 'Business Account';
        }
        $notes[] = "Tier: {$profile['tier']}";
        $notes[] = "Engagement Rate: {$profile['engagement_rate']}%";
        $notes[] = 'Avg Impressions: ' . number_format($profile['average_impressions']);
        $notes[] = 'Avg Likes: ' . number_format($profile['average_likes']);
        $notes[] = 'Avg Comments: ' . number_format($profile['average_comments']);
        $notes[] = 'Following: ' . number_format($profile['following_count']);

        $mediaLabel = match ($channel) {
            'Tiktok', 'Youtube Channels' => 'Videos',
            'Youtube Shorts' => 'Shorts',
            default => 'Posts',
        };
        $notes[] = "{$mediaLabel}: " . number_format($profile['media_count']);

        if (!empty($profile['external_url'])) {
            $notes[] = "Website: {$profile['external_url']}";
        }

        return implode("\n", $notes);
    }
}
