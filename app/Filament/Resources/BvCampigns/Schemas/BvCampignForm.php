<?php

namespace App\Filament\Resources\BvCampigns\Schemas;

use App\Models\BvCampaignKol;
use App\Models\BvSales;
use App\Models\DataClient;
use App\Models\FormBrief;
use Carbon\Carbon;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Wizard;
use Filament\Schemas\Components\Wizard\Step;
use Filament\Schemas\Schema;
use Filament\Support\RawJs;
use Illuminate\Support\Facades\Storage;

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
                            // ─── Summary Campaign (hanya tampil saat edit) ───────────────
                            Section::make('Summary Campaign')
                                ->description('Ringkasan status dan informasi campaign')
                                ->icon('heroicon-o-chart-bar')
                                ->hidden(fn(string $operation): bool => $operation === 'create')
                                ->schema([
                                    Placeholder::make('campaign_summary_display')
                                        ->label('')
                                        ->columnSpanFull()
                                        ->content(function ($record) {
                                            if (!$record) return '';

                                            $statusColors = [
                                                'draft'     => ['#f3f4f6', '#374151'],
                                                'upcoming'  => ['#dbeafe', '#1e40af'],
                                                'ongoing'   => ['#dcfce7', '#14532d'],
                                                'completed' => ['#d1fae5', '#065f46'],
                                                'cancelled' => ['#fee2e2', '#991b1b'],
                                            ];
                                            [$bg, $text] = $statusColors[$record->status] ?? ['#f3f4f6', '#374151'];

                                            $kolCount    = $record->kols()->count();
                                            $kolPosted   = $record->kols()->where('status', 'posted')->count();
                                            $totalCost   = 'Rp ' . number_format((float)$record->total_cost, 0, ',', '.');
                                            $dealValue   = 'Rp ' . number_format((float)$record->deal_value, 0, ',', '.');
                                            $platforms   = collect($record->media_platforms ?? [])->implode(', ') ?: '-';

                                            $progress    = $record->progress;
                                            $progressBar = $record->start_date && $record->end_date
                                                ? '<div style="background:#e5e7eb;border-radius:999px;height:6px;overflow:hidden;margin-top:4px;">
                                                       <div style="background:#22c55e;height:100%;width:' . $progress . '%;"></div>
                                                   </div>
                                                   <div style="font-size:11px;color:#6b7280;margin-top:2px;">' . $progress . '% selesai</div>'
                                                : '<span style="font-size:12px;color:#9ca3af;">Tanggal belum diset</span>';

                                            $rows = [
                                                ['label' => 'Status',         'value' => '<span style="padding:2px 8px;border-radius:999px;font-size:11px;font-weight:600;background:' . $bg . ';color:' . $text . ';">' . ucfirst($record->status) . '</span>'],
                                                ['label' => 'Client',         'value' => e($record->client?->nama_brand ?? $record->campaign_name)],
                                                ['label' => 'Media Platform', 'value' => e($platforms)],
                                                ['label' => 'KOL',            'value' => $kolCount . ' KOL (' . $kolPosted . ' posted)'],
                                                ['label' => 'Total Cost',     'value' => e($totalCost)],
                                                ['label' => 'Deal Value',     'value' => e($dealValue)],
                                                ['label' => 'Progres Waktu',  'value' => $progressBar],
                                            ];

                                            $rowsHtml = '';
                                            foreach ($rows as $row) {
                                                $rowsHtml .= '<tr>
                                                    <td style="padding:6px 8px;font-size:12px;color:#6b7280;white-space:nowrap;vertical-align:top;width:130px;">' . $row['label'] . '</td>
                                                    <td style="padding:6px 8px;font-size:13px;color:#111827;">' . $row['value'] . '</td>
                                                </tr>';
                                            }

                                            return new \Illuminate\Support\HtmlString('
                                                <div style="border:1px solid #e5e7eb;border-radius:8px;overflow:hidden;">
                                                    <div style="padding:10px 14px;background:#f9fafb;border-bottom:1px solid #e5e7eb;">
                                                        <span style="font-size:14px;font-weight:600;color:#111827;">' . e($record->campaign_name) . '</span>
                                                    </div>
                                                    <table style="width:100%;border-collapse:collapse;">' . $rowsHtml . '</table>
                                                </div>
                                            ');
                                        }),
                                ]),

                            // ─── Brief Section (hanya tampil saat edit) ──────────────────
                            Section::make('Brief & Dokumen Client')
                                ->description('Brief summary terbaru dan upload brief dari client')
                                ->icon('heroicon-o-document-text')
                                ->collapsible()
                                ->hidden(fn(string $operation): bool => $operation === 'create')
                                ->schema([
                                    Textarea::make('brief_summary')
                                        ->label('Brief Summary Terbaru')
                                        ->placeholder('Tuliskan ringkasan brief terbaru dari client...')
                                        ->rows(4)
                                        ->columnSpanFull(),

                                    FileUpload::make('client_brief_files')
                                        ->label('Upload Brief dari Client')
                                        ->multiple()
                                        ->directory('campaign-briefs')
                                        ->acceptedFileTypes([
                                            'application/pdf',
                                            'image/png',
                                            'image/jpeg',
                                            'application/msword',
                                            'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                                            'application/vnd.ms-excel',
                                            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                                        ])
                                        ->maxSize(10240)
                                        ->downloadable()
                                        ->openable()
                                        ->reorderable()
                                        ->helperText('Format yang diterima: PDF, Word, Excel, JPG, PNG (maks. 10 MB)')
                                        ->columnSpanFull(),

                                    Placeholder::make('brief_pdf_viewer')
                                        ->label('View Brief PDF')
                                        ->hidden(fn($record) => empty($record?->client_brief_files))
                                        ->columnSpanFull()
                                        ->content(function ($record) {
                                            $files = $record?->client_brief_files ?? [];
                                            if (empty($files)) return '';

                                            $links = '';
                                            foreach ($files as $file) {
                                                $url  = Storage::url($file);
                                                $name = basename($file);
                                                $ext  = strtolower(pathinfo($name, PATHINFO_EXTENSION));
                                                $isPdf = $ext === 'pdf';

                                                $links .= '
                                                    <div style="display:flex;align-items:center;gap:8px;padding:8px 12px;border:1px solid #e5e7eb;border-radius:6px;background:#f9fafb;margin-bottom:6px;">
                                                        <svg width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5" style="color:' . ($isPdf ? '#ef4444' : '#3b82f6') . ';flex-shrink:0;">
                                                            <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 0 0-3.375-3.375h-1.5A1.125 1.125 0 0 1 13.5 7.125v-1.5a3.375 3.375 0 0 0-3.375-3.375H8.25m2.25 0H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 0 0-9-9Z" />
                                                        </svg>
                                                        <span style="font-size:13px;color:#374151;flex:1;truncate;">' . e($name) . '</span>
                                                        <a href="' . e($url) . '" target="_blank" rel="noopener noreferrer"
                                                            style="font-size:12px;color:#3b82f6;text-decoration:none;">
                                                            ' . ($isPdf ? 'View PDF' : 'Download') . '
                                                        </a>
                                                    </div>';
                                            }

                                            return new \Illuminate\Support\HtmlString($links);
                                        }),
                                ]),

                            Section::make('Campaign Detail')
                                ->schema([
                                    // Link ke Sales Activity → auto-fill fields
                                    Select::make('bv_sales_id')
                                        ->label('Pilih Campaign')
                                        ->placeholder('Pilih dari Sales Activity yang berjalan (opsional)...')
                                        ->options(function () {
                                            return BvSales::whereNotIn('status', ['close_lose', 'paid'])
                                                ->orderBy('created_at', 'desc')
                                                ->get()
                                                ->mapWithKeys(fn($s) => [
                                                    $s->id => $s->event_name . ($s->company_name ? ' — ' . $s->company_name : ''),
                                                ]);
                                        })
                                        ->searchable()
                                        ->live()
                                        ->afterStateUpdated(function ($state, $set) {
                                            if ($state) {
                                                $sales = BvSales::find($state);
                                                if ($sales) {
                                                    $set('campaign_name', $sales->event_name);
                                                    $set('deal_value', $sales->deal_value);
                                                    $set('close_date', $sales->close_date?->format('Y-m-d'));
                                                    $set('brief_received_date', $sales->brief_submit_date?->format('Y-m-d'));

                                                    // Auto-fill bulan dari campaign_month
                                                    if ($sales->campaign_month) {
                                                        $set('campaign_month', $sales->campaign_month);
                                                    }

                                                    // Auto-fill client dari company_name
                                                    if ($sales->company_name) {
                                                        $client = DataClient::where('nama_brand', $sales->company_name)->first();
                                                        if ($client) {
                                                            $set('client_id', $client->id);
                                                            $set('client_type', $client->type ?? 'direct');
                                                            $set('agency_name', $client->agency_name);
                                                        }
                                                    }
                                                }
                                            }
                                        })
                                        ->helperText('Field di bawah akan otomatis terisi dari data Sales Activity yang dipilih')
                                        ->columnSpanFull(),

                                    TextInput::make('campaign_name')
                                        ->label('Campaign Name')
                                        ->placeholder('e.g. Ramadan Campaign 2026')
                                        ->required()
                                        ->maxLength(255)
                                        ->columnSpanFull(),

                                    // CP-03: Bulan & Tanggal Campaign
                                    Grid::make(2)->schema([
                                        Select::make('campaign_month')
                                            ->label('Bulan Campaign')
                                            ->placeholder('Pilih Bulan Campaign')
                                            ->options(function () {
                                                $months = [];
                                                for ($i = 1; $i <= 12; $i++) {
                                                    $months[$i] = Carbon::createFromDate(null, $i, 1)->translatedFormat('F');
                                                }
                                                return $months;
                                            })
                                            ->native(false),

                                        DatePicker::make('campaign_date')
                                            ->label('Tanggal Campaign')
                                            ->placeholder('Pilih Tanggal Campaign')
                                            ->native(false)
                                            ->displayFormat('d M Y'),
                                    ]),

                                    Grid::make(2)->schema([
                                        DatePicker::make('start_date')
                                            ->label('Start Date')
                                            ->placeholder('Pilih Start Date')
                                            ->native(false)
                                            ->displayFormat('d M Y')
                                            ->required(),

                                        DatePicker::make('end_date')
                                            ->label('End Date')
                                            ->placeholder('Pilih End Date')
                                            ->native(false)
                                            ->displayFormat('d M Y')
                                            ->required()
                                            ->afterOrEqual('start_date'),
                                    ]),

                                    // CP-04: Deal Value & Close Date berdekatan
                                    Grid::make(2)->schema([
                                        TextInput::make('deal_value')
                                            ->label('Deal Value')
                                            ->placeholder('0')
                                            ->prefix('Rp')
                                            ->numeric()
                                            ->default(0)
                                            ->mask(RawJs::make('$money($input)'))
                                            ->stripCharacters(','),

                                        DatePicker::make('close_date')
                                            ->label('Close Date')
                                            ->placeholder('Pilih Close Date')
                                            ->native(false)
                                            ->displayFormat('d M Y'),
                                    ]),

                                    // CP-05: Tanggal Dapat Brief & PIC Media Plan Internal
                                    Grid::make(2)->schema([
                                        DatePicker::make('brief_received_date')
                                            ->label('Tanggal Dapat Brief')
                                            ->placeholder('Pilih Tanggal Dapat Brief')
                                            ->native(false)
                                            ->displayFormat('d M Y')
                                            ->helperText('Tanggal menerima brief dari client'),

                                        TextInput::make('pic_media_plan')
                                            ->label('PIC Media Plan / Internal')
                                            ->placeholder('Masukkan PIC Media Plan...'),
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
                                        ->rows(4)
                                        ->columnSpanFull(),
                                ]),

                            // FB-04: Pilih Form Brief yang sudah disubmit client
                            Section::make('Form Brief')
                                ->description('Pilih brief yang sudah disubmit oleh client (opsional)')
                                ->schema([
                                    Select::make('form_brief_id')
                                        ->label('Form Brief')
                                        ->placeholder('Pilih brief dari client...')
                                        ->options(function () {
                                            return FormBrief::where('status', 'submitted')
                                                ->orWhere('status', 'reviewed')
                                                ->orderBy('created_at', 'desc')
                                                ->get()
                                                ->mapWithKeys(fn(FormBrief $brief) => [
                                                    $brief->id => $brief->title . ' — ' . ($brief->submitted_by_name ?? 'Unknown'),
                                                ]);
                                        })
                                        ->searchable()
                                        ->preload()
                                        ->live()
                                        ->afterStateUpdated(function ($state, $set) {
                                            if ($state) {
                                                $brief = FormBrief::find($state);
                                                if ($brief) {
                                                    // Gunakan campaign_name dari brief, fallback ke title
                                                    $set('campaign_name', $brief->campaign_name ?? $brief->title);
                                                    $set('campaign_description', $brief->campaign_objective);
                                                }
                                            }
                                        })
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

                                    Toggle::make('instagram_story_enabled')
                                        ->label('Story')
                                        ->live()
                                        ->afterStateUpdated(fn($state, $set) => !$state && $set('instagram_story_creators', [])),

                                    Repeater::make('instagram_story_creators')
                                        ->label('')
                                        ->schema(self::getCreatorFields())
                                        ->visible(fn($get) => $get('instagram_story_enabled'))
                                        ->addActionLabel('Add more creator')
                                        ->defaultItems(0)
                                        ->collapsible()
                                        ->itemLabel(fn(array $state): ?string => $state['creator_name'] ?? 'New Creator'),
                                ]),

                            // TikTok Section
                            Section::make('TikTok')
                                ->description('Video, Photos & Story')
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

                                    Toggle::make('tiktok_story_enabled')
                                        ->label('Story')
                                        ->live()
                                        ->afterStateUpdated(fn($state, $set) => !$state && $set('tiktok_story_creators', [])),

                                    Repeater::make('tiktok_story_creators')
                                        ->label('')
                                        ->schema(self::getCreatorFields())
                                        ->visible(fn($get) => $get('tiktok_story_enabled'))
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

                            // Threads Section
                            Section::make('Threads')
                                ->description('Post & Thread')
                                ->collapsible()
                                ->schema([
                                    Toggle::make('threads_post_enabled')
                                        ->label('Post')
                                        ->live()
                                        ->afterStateUpdated(fn($state, $set) => !$state && $set('threads_post_creators', [])),

                                    Repeater::make('threads_post_creators')
                                        ->label('')
                                        ->schema(self::getCreatorFields())
                                        ->visible(fn($get) => $get('threads_post_enabled'))
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
                                        ->inputMode('decimal')
                                        ->default(0)
                                        ->mask(RawJs::make(<<<'JS'
                                            $money($input, ',', '.', 0)
                                        JS))
                                        ->dehydrateStateUsing(fn($state) => (float) str_replace(['.', ','], '', $state ?? '0')),

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
                ->placeholder('Insert creator name'),

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
