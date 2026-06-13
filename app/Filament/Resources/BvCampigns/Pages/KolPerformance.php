<?php

namespace App\Filament\Resources\BvCampigns\Pages;

use App\Filament\Resources\BvCampigns\BvCampignResource;
use App\Models\BvCampaignKol;
use App\Service\PostPerformanceService;
use Filament\Actions;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\BulkActionGroup;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\Concerns\InteractsWithRecord;
use Filament\Resources\Pages\Page;
use Filament\Schemas\Components\Grid;
use Filament\Support\Enums\FontWeight;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Illuminate\Contracts\Support\Htmlable;
use Exception;

class KolPerformance extends Page implements HasTable
{
    use InteractsWithRecord;
    use InteractsWithTable;

    protected static string $resource = BvCampignResource::class;

    public function getView(): string
    {
        return 'filament.pages.kol-performance';
    }

    public function mount(int|string $record): void
    {
        $this->record = $this->resolveRecord($record);
    }

    public function getTitle(): string|Htmlable
    {
        return "KOL Performance - {$this->record->campaign_name}";
    }

    public function getBreadcrumbs(): array
    {
        return [
            BvCampignResource::getUrl() => 'Campaign Ongoing Internal',
            BvCampignResource::getUrl('edit', ['record' => $this->record]) => $this->record->campaign_name,
            '' => 'KOL Performance',
        ];
    }

    public function table(Table $table): Table
    {
        return $table
            ->queryStringIdentifier('kol')
            ->query(BvCampaignKol::query()->where('campaign_id', $this->record->id)->where('brief_status', 'approved'))
            ->columns([
                TextColumn::make('creator_name')
                    ->label('Creator')
                    ->weight(FontWeight::SemiBold)
                    ->searchable()
                    ->sortable(),

                TextColumn::make('platform')
                    ->label('Platform')
                    ->formatStateUsing(function ($state) {
                        $icon = match ($state) {
                            'instagram' => 'fi-brands-instagram',
                            'tiktok' => 'fi-brands-tiktok',
                            'youtube' => 'fi-brands-youtube',
                            default => 'fi-rr-globe',
                        };
                        return new \Illuminate\Support\HtmlString(
                            "<i class=\"fi {$icon}\" style=\"font-size: 1.25rem;\"></i>"
                        );
                    })
                    ->html()
                    ->tooltip(fn($state) => ucfirst($state)),

                TextColumn::make('content_type')
                    ->label('Type')
                    ->badge()
                    ->color(fn($state) => match ($state) {
                        'reels' => 'success',
                        'video' => 'success',
                        'short' => 'success',
                        'feed' => 'warning',
                        'photos' => 'warning',
                        default => 'info',
                    })
                    ->icon(fn($state) => match ($state) {
                        'reels', 'video', 'short' => 'heroicon-o-play-circle',
                        'feed', 'photos' => 'heroicon-o-photo',
                        default => null,
                    })
                    ->formatStateUsing(fn($state) => match ($state) {
                        'reels' => 'Reels',
                        'video' => 'Video',
                        'short' => 'Short',
                        'feed' => 'Foto',
                        'photos' => 'Foto',
                        default => ucfirst($state),
                    }),

                TextColumn::make('views')
                    ->label('Views')
                    ->formatStateUsing(function ($state, $record) {
                        // For video content (reels, video, short), show views
                        $videoTypes = ['reels', 'video', 'short'];
                        if (in_array($record->content_type, $videoTypes)) {
                            return number_format($state);
                        }
                        // For photo content, show dash
                        return '-';
                    })
                    ->color(fn($record) => in_array($record->content_type, ['reels', 'video', 'short']) ? null : 'gray')
                    ->sortable(),

                // TextColumn::make('followers_count')
                //     ->label('Followers')
                //     ->numeric()
                //     ->sortable(),

                TextColumn::make('total_engagement')
                    ->label('Engagement')
                    ->numeric()
                    ->color('primary')
                    ->sortable(),

                TextColumn::make('likes')
                    ->label('Likes')
                    ->numeric()
                    ->sortable(),

                TextColumn::make('comments')
                    ->label('Comments')
                    ->numeric()
                    ->sortable(),

                TextColumn::make('shares')
                    ->label('Shares')
                    ->numeric()
                    ->sortable(),

                TextColumn::make('saves')
                    ->label('Saves')
                    ->numeric()
                    ->sortable(),

                TextColumn::make('engagement_rate')
                    ->label('ER')
                    ->formatStateUsing(function ($state, $record) {
                        // If ER is calculated and > 0, show it with type indicator
                        if ($state > 0) {
                            $suffix = $record->er_type === 'followers' ? ' (F)' : '';
                            return number_format((float) $state, 2) . '%' . $suffix;
                        }

                        // If views or followers available but ER is 0, show 0%
                        if ($record->views > 0 || $record->followers_count > 0) {
                            return '0%';
                        }

                        // No data available
                        return 'N/A';
                    })
                    ->color(fn($state, $record) => $state > 0 ? 'success' : 'gray')
                    ->tooltip(function ($record) {
                        if ($record->er_type === 'followers') {
                            return 'ER by Followers: (Likes + Comments) / Followers × 100';
                        }
                        return 'ER by Views: (Likes + Comments) / Views × 100';
                    })
                    ->sortable(),

                TextColumn::make('price')
                    ->label('Price')
                    ->formatStateUsing(fn($state) => 'IDR ' . number_format((float) $state, 0, ',', '.'))
                    ->sortable(),

                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->color(fn($state) => match ($state) {
                        'pending' => 'warning',
                        'posted' => 'info',
                        'completed' => 'success',
                        default => 'gray',
                    }),
            ])
            ->actions([
                Action::make('fetch')
                    ->label('Fetch')
                    ->icon('heroicon-o-arrow-path')
                    ->color('white')
                    ->visible(fn($record) => !empty($record->post_url))
                    ->action(function ($record) {
                        try {
                            $service = new PostPerformanceService();
                            $updated = $service->fetchAndUpdateKol($record);

                            Notification::make()
                                ->title('Performance Fetched')
                                ->body("Views: " . number_format($updated->views) .
                                    " | Likes: " . number_format($updated->likes) .
                                    " | Comments: " . number_format($updated->comments))
                                ->success()
                                ->send();

                        } catch (Exception $e) {
                            Notification::make()
                                ->title('Fetch Failed')
                                ->body($e->getMessage())
                                ->danger()
                                ->send();
                        }
                    }),
                Action::make('open_url')
                    ->label('Open')
                    ->icon('heroicon-o-arrow-top-right-on-square')
                    ->color('gray')
                    ->url(fn($record) => $record->post_url)
                    ->openUrlInNewTab()
                    ->visible(fn($record) => !empty($record->post_url)),
                EditAction::make()
                    ->form([
                        Grid::make(2)->schema([
                            TextInput::make('creator_name')->label('Creator Name')->required(),
                            TextInput::make('username')->label('Username'),
                        ]),
                        Grid::make(2)->schema([
                            Select::make('platform')->options(BvCampaignKol::PLATFORMS)->required(),
                            Select::make('content_type')->options([
                                'reels' => 'Reels',
                                'feed' => 'Feed',
                                'video' => 'Video',
                                'photos' => 'Photos',
                                'short' => 'Short',
                            ])->required(),
                        ]),
                        TextInput::make('post_url')->label('Post URL')->url()->columnSpanFull(),
                        Grid::make(2)->schema([
                            TextInput::make('price')
                                ->prefix('Rp')
                                ->mask(\Filament\Support\RawJs::make(<<<'JS'
                                    $money($input, ',', '.', 0)
                                JS))
                                ->dehydrateStateUsing(fn($state) => (double) str_replace(['.', ','], '', $state ?? '0')),
                            Select::make('status')->options(\App\Models\BvCampaignKol::STATUSES)->default('pending'),
                        ]),
                    ]),
                DeleteAction::make(),
            ])
            ->headerActions([
                // Add KOL button removed - KOLs should be added from Campaign edit page
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('fetch_all')
                ->label('Fetch All Performance')
                ->icon('heroicon-o-arrow-path')
                ->color('white')
                ->requiresConfirmation()
                ->action(function () {
                    try {
                        $kols = $this->record->kols()
                            ->whereNotNull('post_url')
                            ->where('post_url', '!=', '')
                            ->get();

                        if ($kols->isEmpty()) {
                            Notification::make()
                                ->title('No KOLs with Post URLs')
                                ->warning()
                                ->send();
                            return;
                        }

                        $service = new PostPerformanceService();
                        $results = $service->bulkFetchAndUpdate($kols);

                        Notification::make()
                            ->title('Performance Fetched')
                            ->body("Successfully updated {$results['success']} of {$results['total']} KOLs.")
                            ->success()
                            ->send();

                    } catch (Exception $e) {
                        Notification::make()
                            ->title('Fetch Failed')
                            ->body($e->getMessage())
                            ->danger()
                            ->send();
                    }
                }),
            Actions\Action::make('back')
                ->label('Back to Campaign')
                ->icon('heroicon-o-arrow-left')
                ->color('gray')
                ->url(BvCampignResource::getUrl('edit', ['record' => $this->record])),
        ];
    }
}
