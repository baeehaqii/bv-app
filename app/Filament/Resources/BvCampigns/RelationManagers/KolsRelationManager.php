<?php

namespace App\Filament\Resources\BvCampigns\RelationManagers;

use App\Models\BvCampaignKol;
use App\Service\PostPerformanceService;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Schema;
use Filament\Support\Enums\FontWeight;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Exception;

class KolsRelationManager extends RelationManager
{
    protected static string $relationship = 'kols';

    protected static ?string $title = 'KOL Performance';

    protected static ?string $recordTitleAttribute = 'creator_name';

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Grid::make(2)
                    ->schema([
                        TextInput::make('creator_name')
                            ->label('Creator Name')
                            ->required()
                            ->maxLength(255),

                        TextInput::make('username')
                            ->label('Username')
                            ->placeholder('@username')
                            ->maxLength(255),

                        Select::make('platform')
                            ->label('Platform')
                            ->options(BvCampaignKol::PLATFORMS)
                            ->required()
                            ->live(),

                        Select::make('content_type')
                            ->label('Content Type')
                            ->options(fn($get) => BvCampaignKol::CONTENT_TYPES[$get('platform')] ?? [])
                            ->required(),

                        TextInput::make('post_url')
                            ->label('Post URL')
                            ->placeholder('https://...')
                            ->url()
                            ->columnSpanFull(),

                        TextInput::make('price')
                            ->label('Price')
                            ->prefix('Rp')
                            ->numeric()
                            ->default(0),

                        Select::make('status')
                            ->label('Status')
                            ->options(BvCampaignKol::STATUSES)
                            ->default('pending'),
                    ]),

                // Performance Metrics (readonly, filled by API)
                Grid::make(4)
                    ->schema([
                        TextInput::make('views')
                            ->label('Views')
                            ->numeric()
                            ->default(0),

                        TextInput::make('likes')
                            ->label('Likes')
                            ->numeric()
                            ->default(0),

                        TextInput::make('comments')
                            ->label('Comments')
                            ->numeric()
                            ->default(0),

                        TextInput::make('shares')
                            ->label('Shares')
                            ->numeric()
                            ->default(0),

                        TextInput::make('saves')
                            ->label('Saves')
                            ->numeric()
                            ->default(0),

                        TextInput::make('reach')
                            ->label('Reach')
                            ->numeric()
                            ->default(0),

                        TextInput::make('impressions')
                            ->label('Impressions')
                            ->numeric()
                            ->default(0),

                        TextInput::make('engagement_rate')
                            ->label('ER (%)')
                            ->numeric()
                            ->suffix('%')
                            ->default(0),
                    ])
                    ->visible(fn(string $operation) => $operation === 'edit'),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn($query) => $query->where('brief_status', 'approved'))
            ->columns([
                TextColumn::make('creator_name')
                    ->label('Creator')
                    ->weight(FontWeight::SemiBold)
                    ->searchable()
                    ->sortable(),

                TextColumn::make('platform')
                    ->label('Platform')
                    ->badge()
                    ->color(fn($state) => match ($state) {
                        'instagram' => 'pink',
                        'tiktok' => 'gray',
                        'youtube' => 'danger',
                        default => 'primary',
                    })
                    ->formatStateUsing(fn($state) => ucfirst($state)),

                TextColumn::make('content_type')
                    ->label('Type')
                    ->badge()
                    ->color('info')
                    ->formatStateUsing(fn($state) => ucfirst($state)),

                TextColumn::make('views')
                    ->label('Views')
                    ->numeric()
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
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('saves')
                    ->label('Saves')
                    ->numeric()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('total_engagement')
                    ->label('Engagement')
                    ->numeric()
                    ->color('primary')
                    ->sortable(),

                TextColumn::make('engagement_rate')
                    ->label('ER')
                    ->formatStateUsing(fn($state, $record) => $record->views > 0 ? number_format($state, 2) . '%' : 'N/A')
                    ->color(fn($state, $record) => $record->views > 0 ? 'success' : 'gray')
                    ->sortable(),

                TextColumn::make('price')
                    ->label('Price')
                    ->money('IDR')
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

                TextColumn::make('last_fetched_at')
                    ->label('Last Fetched')
                    ->since()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('platform')
                    ->label('Platform')
                    ->options(BvCampaignKol::PLATFORMS),

                SelectFilter::make('status')
                    ->label('Status')
                    ->options(BvCampaignKol::STATUSES),
            ])
            ->headerActions([
                CreateAction::make()
                    ->label('Add KOL'),
                Action::make('fetch_all')
                    ->label('Fetch All Performance')
                    ->icon('heroicon-o-arrow-path')
                    ->color('info')
                    ->requiresConfirmation()
                    ->modalHeading('Fetch All KOL Performance')
                    ->modalDescription('This will fetch performance data from all KOL post URLs. This may take a while depending on the number of KOLs.')
                    ->action(function () {
                        try {
                            $kols = $this->getOwnerRecord()->kols()
                                ->whereNotNull('post_url')
                                ->where('post_url', '!=', '')
                                ->get();

                            if ($kols->isEmpty()) {
                                Notification::make()
                                    ->title('No KOLs with Post URLs')
                                    ->body('Please add post URLs to KOLs first.')
                                    ->warning()
                                    ->send();
                                return;
                            }

                            $service = new PostPerformanceService();
                            $results = $service->bulkFetchAndUpdate($kols);

                            if ($results['success'] > 0) {
                                Notification::make()
                                    ->title('Performance Fetched')
                                    ->body("Successfully updated {$results['success']} of {$results['total']} KOLs.")
                                    ->success()
                                    ->send();
                            }

                            if ($results['failed'] > 0) {
                                Notification::make()
                                    ->title('Some Fetches Failed')
                                    ->body(implode("\n", array_slice($results['errors'], 0, 5)))
                                    ->warning()
                                    ->send();
                            }

                        } catch (Exception $e) {
                            Notification::make()
                                ->title('Fetch Failed')
                                ->body($e->getMessage())
                                ->danger()
                                ->send();
                        }
                    }),
            ])
            ->actions([
                Action::make('fetch')
                    ->label('Fetch')
                    ->icon('heroicon-o-arrow-path')
                    ->color('info')
                    ->tooltip('Fetch performance from post URL')
                    ->visible(fn($record) => !empty($record->post_url))
                    ->action(function ($record) {
                        try {
                            if (empty($record->post_url)) {
                                Notification::make()
                                    ->title('No Post URL')
                                    ->body('Please add a post URL first.')
                                    ->warning()
                                    ->send();
                                return;
                            }

                            $service = new PostPerformanceService();
                            $service->fetchAndUpdateKol($record);

                            Notification::make()
                                ->title('Performance Fetched')
                                ->body("Views: " . number_format($record->views) .
                                    " | Likes: " . number_format($record->likes) .
                                    " | Comments: " . number_format($record->comments))
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
                    ->visible(fn($record) => !empty($record->post_url))
                    ->tooltip('Open post in new tab'),
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
    }
}
