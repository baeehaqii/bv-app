<?php

namespace App\Filament\Resources\BvCampigns\Schemas;

use App\Models\BvCampaignKol;
use App\Models\DataClient;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Fieldset;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Wizard;
use Filament\Schemas\Components\Wizard\Step;
use Filament\Schemas\Schema;

class BvCampignForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Wizard::make([
                    // Step 1: Information
                    Step::make('Information')
                        ->icon('heroicon-o-information-circle')
                        ->description('Basic campaign information')
                        ->schema([
                            Section::make('Campaign Information')
                                ->schema([
                                    Select::make('client_id')
                                        ->label('Brand Name')
                                        ->relationship(
                                            name: 'client',
                                            titleAttribute: 'nama_brand',
                                            modifyQueryUsing: fn($query) => $query
                                                ->selectRaw('MIN(id) as id, nama_brand')
                                                ->groupBy('nama_brand')
                                        )
                                        ->searchable()
                                        ->preload()
                                        ->required()
                                        ->createOptionForm([
                                            TextInput::make('nama_brand')
                                                ->label('Brand Name')
                                                ->required()
                                                ->maxLength(255),
                                            TextInput::make('email')
                                                ->label('Email')
                                                ->email()
                                                ->maxLength(255),
                                            TextInput::make('phone')
                                                ->label('Phone')
                                                ->tel()
                                                ->maxLength(20),
                                        ])
                                        ->columnSpanFull(),

                                    TextInput::make('campaign_name')
                                        ->label('Campaign Name')
                                        ->placeholder('e.g. Ramadan Campaign 2026')
                                        ->required()
                                        ->maxLength(255)
                                        ->columnSpanFull(),

                                    Grid::make(2)
                                        ->schema([
                                            DatePicker::make('start_date')
                                                ->label('Start Date')
                                                ->native(false)
                                                ->displayFormat('d M Y')
                                                ->required(),

                                            DatePicker::make('end_date')
                                                ->label('End Date')
                                                ->native(false)
                                                ->displayFormat('d M Y')
                                                ->required()
                                                ->afterOrEqual('start_date'),
                                        ]),

                                    FileUpload::make('campaign_image')
                                        ->label('Insert Image/Banner Campaign')
                                        ->image()
                                        ->disk('public')
                                        ->directory('campaigns')
                                        ->imageEditor()
                                        ->maxSize(5120)
                                        ->columnSpanFull(),

                                    Textarea::make('campaign_description')
                                        ->label('Campaign Description')
                                        ->placeholder('Describe your campaign...')
                                        ->required()
                                        ->rows(4)
                                        ->columnSpanFull(),
                                ]),
                        ]),

                    // Step 2: Media Platform
                    Step::make('Media Platform')
                        ->icon('heroicon-o-device-phone-mobile')
                        ->description('Select platforms and add creators')
                        ->schema([
                            // Instagram Section
                            Section::make('Instagram')
                                ->description('Reels & Feed')
                                ->collapsible()
                                ->schema([
                                    Toggle::make('instagram_reels_enabled')
                                        ->label('Reels')
                                        ->live()
                                        ->afterStateUpdated(fn($state, $set) => !$state && $set('instagram_reels_creators', [])),

                                    Repeater::make('instagram_reels_creators')
                                        ->label('')
                                        ->schema(self::getCreatorFields())
                                        ->visible(fn($get) => $get('instagram_reels_enabled'))
                                        ->addActionLabel('Add more creator')
                                        ->defaultItems(0)
                                        ->collapsible()
                                        ->itemLabel(fn(array $state): ?string => $state['creator_name'] ?? 'New Creator'),

                                    Toggle::make('instagram_feed_enabled')
                                        ->label('Feed')
                                        ->live()
                                        ->afterStateUpdated(fn($state, $set) => !$state && $set('instagram_feed_creators', [])),

                                    Repeater::make('instagram_feed_creators')
                                        ->label('')
                                        ->schema(self::getCreatorFields())
                                        ->visible(fn($get) => $get('instagram_feed_enabled'))
                                        ->addActionLabel('Add more creator')
                                        ->defaultItems(0)
                                        ->collapsible()
                                        ->itemLabel(fn(array $state): ?string => $state['creator_name'] ?? 'New Creator'),
                                ]),

                            // TikTok Section
                            Section::make('TikTok')
                                ->description('Video & Photos')
                                ->collapsible()
                                ->schema([
                                    Toggle::make('tiktok_video_enabled')
                                        ->label('Video')
                                        ->live()
                                        ->afterStateUpdated(fn($state, $set) => !$state && $set('tiktok_video_creators', [])),

                                    Repeater::make('tiktok_video_creators')
                                        ->label('')
                                        ->schema(self::getCreatorFields())
                                        ->visible(fn($get) => $get('tiktok_video_enabled'))
                                        ->addActionLabel('Add more creator')
                                        ->defaultItems(0)
                                        ->collapsible()
                                        ->itemLabel(fn(array $state): ?string => $state['creator_name'] ?? 'New Creator'),

                                    Toggle::make('tiktok_photos_enabled')
                                        ->label('Photos')
                                        ->live()
                                        ->afterStateUpdated(fn($state, $set) => !$state && $set('tiktok_photos_creators', [])),

                                    Repeater::make('tiktok_photos_creators')
                                        ->label('')
                                        ->schema(self::getCreatorFields())
                                        ->visible(fn($get) => $get('tiktok_photos_enabled'))
                                        ->addActionLabel('Add more creator')
                                        ->defaultItems(0)
                                        ->collapsible()
                                        ->itemLabel(fn(array $state): ?string => $state['creator_name'] ?? 'New Creator'),
                                ]),

                            // YouTube Section
                            Section::make('YouTube')
                                ->description('Short & Video')
                                ->collapsible()
                                ->schema([
                                    Toggle::make('youtube_short_enabled')
                                        ->label('Short')
                                        ->live()
                                        ->afterStateUpdated(fn($state, $set) => !$state && $set('youtube_short_creators', [])),

                                    Repeater::make('youtube_short_creators')
                                        ->label('')
                                        ->schema(self::getCreatorFields())
                                        ->visible(fn($get) => $get('youtube_short_enabled'))
                                        ->addActionLabel('Add more creator')
                                        ->defaultItems(0)
                                        ->collapsible()
                                        ->itemLabel(fn(array $state): ?string => $state['creator_name'] ?? 'New Creator'),

                                    Toggle::make('youtube_video_enabled')
                                        ->label('Video')
                                        ->live()
                                        ->afterStateUpdated(fn($state, $set) => !$state && $set('youtube_video_creators', [])),

                                    Repeater::make('youtube_video_creators')
                                        ->label('')
                                        ->schema(self::getCreatorFields())
                                        ->visible(fn($get) => $get('youtube_video_enabled'))
                                        ->addActionLabel('Add more creator')
                                        ->defaultItems(0)
                                        ->collapsible()
                                        ->itemLabel(fn(array $state): ?string => $state['creator_name'] ?? 'New Creator'),
                                ]),
                        ]),

                    // Step 3: Confirmation
                    Step::make('Confirmation')
                        ->icon('heroicon-o-check-circle')
                        ->description('Review and confirm')
                        ->schema([
                            Section::make('Campaign Type')
                                ->schema([
                                    Select::make('campaign_type')
                                        ->label('')
                                        ->options([
                                            'regular' => 'Regular',
                                            'advance' => 'Advance',
                                        ])
                                        ->default('regular')
                                        ->native(false)
                                        ->helperText('Scheduled retrieve can\'t be canceled. You can schedule more retrieve on detail page.'),
                                ]),

                            Section::make('Retrieve Option')
                                ->schema([
                                    Select::make('retrieve_option')
                                        ->label('')
                                        ->options([
                                            'template' => 'Template',
                                            'custom' => 'Custom',
                                            'daily' => 'Daily',
                                        ])
                                        ->default('template')
                                        ->native(false),

                                    Select::make('retrieve_template')
                                        ->label('Choose Template')
                                        ->options([
                                            'one_time' => 'One time only',
                                            'weekly' => 'Weekly',
                                            'monthly' => 'Monthly',
                                        ])
                                        ->default('one_time')
                                        ->native(false)
                                        ->visible(fn($get) => $get('retrieve_option') === 'template'),
                                ]),

                            Section::make('Campaign Settings')
                                ->schema([
                                    Select::make('status')
                                        ->label('Status')
                                        ->options([
                                            'draft' => 'Draft',
                                            'upcoming' => 'Upcoming',
                                            'ongoing' => 'Ongoing',
                                            'completed' => 'Completed',
                                            'cancelled' => 'Cancelled',
                                        ])
                                        ->default('draft')
                                        ->required(),

                                    TextInput::make('total_cost')
                                        ->label('Total Campaign Cost')
                                        ->prefix('Rp')
                                        ->numeric()
                                        ->default(0)
                                        ->mask(\Filament\Support\RawJs::make(<<<'JS'
                                            $money($input, ',', '.', 0)
                                        JS))
                                        ->dehydrateStateUsing(fn($state) => (double) str_replace(['.', ','], '', $state ?? '0')),

                                    TextInput::make('pic_internal')
                                        ->label('PIC Internal')
                                        ->placeholder('Person in charge'),

                                    TextInput::make('report_link')
                                        ->label('Report Link')
                                        ->placeholder('Link to report document')
                                        ->url(),
                                ])->columns(2),
                        ]),
                ])
                    ->columnSpanFull()
                    ->skippable(),
            ]);
    }

    /**
     * Get creator fields for repeater
     */
    private static function getCreatorFields(): array
    {
        return [
            TextInput::make('creator_name')
                ->label('Creator Name')
                ->placeholder('Insert creator name')
                ->required(),

            Grid::make(2)
                ->schema([
                    TextInput::make('url')
                        ->label('URL')
                        ->placeholder('Insert content/post link')
                        ->url(),

                    TextInput::make('price')
                        ->label('Price')
                        ->prefix('Rp')
                        ->default(0)
                        ->mask(\Filament\Support\RawJs::make(<<<'JS'
                            $money($input, ',', '.', 0)
                        JS))
                        ->dehydrateStateUsing(fn($state) => str_replace(['.', ','], '', $state ?? '0')),
                ]),
        ];
    }
}
