<?php

namespace App\Filament\Resources\BvCampigns\Schemas;

use App\Models\BvSales;
use App\Models\DataClient;
use App\Models\FormBrief;
use Carbon\Carbon;
use Filament\Actions\Action;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\Actions;
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
                                            if (!$record)
                                                return '';

                                            $statusColors = [
                                                'draft' => ['#f3f4f6', '#374151'],
                                                'upcoming' => ['#dbeafe', '#1e40af'],
                                                'ongoing' => ['#dcfce7', '#14532d'],
                                                'completed' => ['#d1fae5', '#065f46'],
                                                'cancelled' => ['#fee2e2', '#991b1b'],
                                            ];
                                            [$bg, $text] = $statusColors[$record->status] ?? ['#f3f4f6', '#374151'];

                                            $kolCount = $record->kols()->count();
                                            $kolPosted = $record->kols()->where('status', 'posted')->count();
                                            $totalCost = 'Rp ' . number_format((float) $record->total_cost, 0, ',', '.');
                                            $dealValue = 'Rp ' . number_format((float) $record->deal_value, 0, ',', '.');
                                            $platforms = collect($record->media_platforms ?? [])->implode(', ') ?: '-';

                                            $progress = $record->progress;
                                            $progressBar = $record->start_date && $record->end_date
                                                ? '<div style="background:#e5e7eb;border-radius:999px;height:6px;overflow:hidden;margin-top:4px;">
                                                       <div style="background:#22c55e;height:100%;width:' . $progress . '%;"></div>
                                                   </div>
                                                   <div style="font-size:11px;color:#6b7280;margin-top:2px;">' . $progress . '% selesai</div>'
                                                : '<span style="font-size:12px;color:#9ca3af;">Tanggal belum diset</span>';

                                            $rows = [
                                                ['label' => 'Status', 'value' => '<span style="padding:2px 8px;border-radius:999px;font-size:11px;font-weight:600;background:' . $bg . ';color:' . $text . ';">' . ucfirst($record->status) . '</span>'],
                                                ['label' => 'Client', 'value' => e($record->client?->nama_brand ?? $record->campaign_name)],
                                                ['label' => 'Media Platform', 'value' => e($platforms)],
                                                ['label' => 'KOL', 'value' => $kolCount . ' KOL (' . $kolPosted . ' posted)'],
                                                ['label' => 'Total Cost', 'value' => e($totalCost)],
                                                ['label' => 'Deal Value', 'value' => e($dealValue)],
                                                ['label' => 'Progres Waktu', 'value' => $progressBar],
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
                                            if (empty($files))
                                                return '';

                                            $links = '';
                                            foreach ($files as $file) {
                                                $url = Storage::url($file);
                                                $name = basename($file);
                                                $ext = strtolower(pathinfo($name, PATHINFO_EXTENSION));
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
                                        ->label('Select Campaign')
                                        ->placeholder('Select from ongoing Sales Activity (optional)...')
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
                                        ->helperText('Fields below will be auto-filled from the selected Sales Activity')
                                        ->columnSpanFull(),

                                    TextInput::make('campaign_name')
                                        ->label('Campaign Name')
                                        ->placeholder('e.g. Ramadan Campaign 2026')
                                        ->required()
                                        ->maxLength(255)
                                        ->columnSpanFull(),

                                    // CP-03: Campaign Month
                                    Select::make('campaign_month')
                                        ->label('Campaign Month')
                                        ->placeholder('Select Campaign Month')
                                        ->options(function () {
                                            $months = [];
                                            for ($i = 1; $i <= 12; $i++) {
                                                $months[$i] = Carbon::createFromDate(null, $i, 1)->format('F');
                                            }
                                            return $months;
                                        })
                                        ->native(false),

                                    // Tipe client badge (ditampilkan saat client sudah dipilih)
                                    Placeholder::make('client_type_badge')
                                        ->label('Tipe Client')
                                        ->content(fn($get) => match ($get('client_type')) {
                                            'agency' => new \Illuminate\Support\HtmlString('<span style="display:inline-block;padding:3px 10px;border-radius:999px;font-size:12px;font-weight:600;background:#dbeafe;color:#1e40af;">Agency</span>'),
                                            'direct' => new \Illuminate\Support\HtmlString('<span style="display:inline-block;padding:3px 10px;border-radius:999px;font-size:12px;font-weight:600;background:#dcfce7;color:#14532d;">Direct Brand</span>'),
                                            default => new \Illuminate\Support\HtmlString('<span style="color:#9ca3af;font-size:12px;">—</span>'),
                                        })
                                        ->hidden(fn($get) => empty($get('client_type'))),


                                    Grid::make(2)->schema([
                                        DatePicker::make('start_date')
                                            ->label('Start Date')
                                            ->placeholder('Select Start Date')
                                            ->native(false)
                                            ->displayFormat('d M Y')
                                            ->required(),

                                        DatePicker::make('end_date')
                                            ->label('End Date')
                                            ->placeholder('Select End Date')
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

                                    // CP-05: Date of Brief
                                    DatePicker::make('brief_received_date')
                                        ->label('Date of Brief')
                                        ->placeholder('Pilih Date of Brief')
                                        ->native(false)
                                        ->displayFormat('d M Y')
                                        ->helperText('Tanggal menerima brief dari client'),


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
                        ->description('Tambahkan creator dan pilih channel')
                        ->schema([
                            Repeater::make('kol_entries')
                                ->label('Creator / KOL List')
                                ->addActionLabel('+ Add Creator')
                                ->defaultItems(0)
                                ->reorderable()
                                ->columnSpanFull()
                                ->columns(4)
                                ->schema([
                                    TextInput::make('creator_name')
                                        ->label('Creator Name')
                                        ->placeholder('@username or name')
                                        ->required(),

                                    Select::make('channel')
                                        ->label('Channel')
                                        ->options([
                                            'Instagram' => [
                                                'instagram_reels' => 'Instagram • Reels',
                                                'instagram_feed' => 'Instagram • Feed',
                                                'instagram_story' => 'Instagram • Story',
                                            ],
                                            'TikTok' => [
                                                'tiktok_video' => 'TikTok • Video',
                                                'tiktok_story' => 'TikTok • Story',
                                                'tiktok_photos' => 'TikTok • Photos',
                                            ],
                                            'YouTube' => [
                                                'youtube_short' => 'YouTube • Short',
                                                'youtube_video' => 'YouTube • Video',
                                            ],
                                            'Threads' => [
                                                'threads_post' => 'Threads • Post',
                                            ],
                                        ])
                                        ->native(false)
                                        ->required(),

                                    TextInput::make('url')
                                        ->label('Post URL')
                                        ->placeholder('https://instagram.com/...')
                                        ->url(),

                                    TextInput::make('price')
                                        ->label('Price (Rp)')
                                        ->prefix('Rp')
                                        ->default(0)
                                        ->mask(RawJs::make(<<<'JS'
                                            $money($input, ',', '.', 0)
                                        JS))
                                        ->dehydrateStateUsing(fn($state) => str_replace(['.', ','], '', $state ?? '0')),
                                ]),

                            Actions::make([
                                Action::make('bulkImportKols')
                                    ->label('Bulk Import CSV')
                                    ->icon('heroicon-o-arrow-up-tray')
                                    ->color('gray')
                                    ->modalHeading('Bulk Import KOL dari CSV')
                                    ->modalDescription(new \Illuminate\Support\HtmlString(
                                        '<p style="font-size:13px;color:#374151;margin-bottom:8px;">Upload file CSV dengan kolom: <strong>creator_name, channel, url, price</strong>.</p>'
                                        . '<table style="width:100%;border-collapse:collapse;font-size:12px;">'
                                        . '<thead><tr style="background:#6d28d9;color:#fff;">'
                                        . '<th style="padding:4px 8px;text-align:left;">nilai_channel</th>'
                                        . '<th style="padding:4px 8px;text-align:left;">Platform</th>'
                                        . '<th style="padding:4px 8px;text-align:left;">Tipe</th>'
                                        . '</tr></thead><tbody>'
                                        . '<tr style="background:#fce7f3;"><td style="padding:3px 8px;">instagram_reels</td><td style="padding:3px 8px;">Instagram</td><td style="padding:3px 8px;">Reels</td></tr>'
                                        . '<tr style="background:#fce7f3;"><td style="padding:3px 8px;">instagram_feed</td><td style="padding:3px 8px;">Instagram</td><td style="padding:3px 8px;">Feed</td></tr>'
                                        . '<tr style="background:#fce7f3;"><td style="padding:3px 8px;">instagram_story</td><td style="padding:3px 8px;">Instagram</td><td style="padding:3px 8px;">Story</td></tr>'
                                        . '<tr style="background:#f0fdf4;"><td style="padding:3px 8px;">tiktok_video</td><td style="padding:3px 8px;">TikTok</td><td style="padding:3px 8px;">Video</td></tr>'
                                        . '<tr style="background:#f0fdf4;"><td style="padding:3px 8px;">tiktok_story</td><td style="padding:3px 8px;">TikTok</td><td style="padding:3px 8px;">Story</td></tr>'
                                        . '<tr style="background:#f0fdf4;"><td style="padding:3px 8px;">tiktok_photos</td><td style="padding:3px 8px;">TikTok</td><td style="padding:3px 8px;">Photo Slide</td></tr>'
                                        . '<tr style="background:#fef3c7;"><td style="padding:3px 8px;">youtube_short</td><td style="padding:3px 8px;">YouTube</td><td style="padding:3px 8px;">Shorts</td></tr>'
                                        . '<tr style="background:#fef3c7;"><td style="padding:3px 8px;">youtube_video</td><td style="padding:3px 8px;">YouTube</td><td style="padding:3px 8px;">Video</td></tr>'
                                        . '<tr style="background:#eff6ff;"><td style="padding:3px 8px;">threads_post</td><td style="padding:3px 8px;">Threads</td><td style="padding:3px 8px;">Post</td></tr>'
                                        . '</tbody></table>'
                                    ))
                                    ->form([
                                        FileUpload::make('csv_file')
                                            ->label('Upload File CSV')
                                            ->disk('local')
                                            ->directory('temp-kol-imports')
                                            ->visibility('private')
                                            ->acceptedFileTypes(['text/csv', 'text/plain', 'application/csv', 'application/octet-stream'])
                                            ->maxSize(2048)
                                            ->helperText(new \Illuminate\Support\HtmlString(
                                                'Baris pertama dianggap sebagai header dan akan dilewati. '
                                                . '<a href="' . route('kol-import.template') . '" '
                                                . 'style="color:#7c3aed;font-weight:500;text-decoration:underline;">'
                                                . '↓ Download Template CSV</a>'
                                            ))
                                            ->required(),
                                    ])
                                    ->action(function (array $data, $get, $set): void {
                                        $filePath = $data['csv_file'] ?? null;

                                        if (!$filePath) {
                                            return;
                                        }

                                        $contents = Storage::disk('local')->get($filePath);
                                        Storage::disk('local')->delete($filePath);

                                        if (blank($contents)) {
                                            Notification::make()
                                                ->title('File CSV kosong')
                                                ->danger()
                                                ->send();
                                            return;
                                        }

                                        $validChannels = [
                                            'instagram_reels',
                                            'instagram_feed',
                                            'instagram_story',
                                            'tiktok_video',
                                            'tiktok_story',
                                            'tiktok_photos',
                                            'youtube_short',
                                            'youtube_video',
                                            'threads_post',
                                        ];

                                        $newEntries = [];
                                        $skipped = 0;

                                        $lines = array_filter(
                                            explode("\n", str_replace("\r\n", "\n", trim($contents))),
                                            fn($line) => trim($line) !== ''
                                        );

                                        // Lewati baris header
                                        array_shift($lines);

                                        foreach ($lines as $line) {
                                            $row = str_getcsv($line);

                                            $creatorName = trim($row[0] ?? '');
                                            $channel = strtolower(trim($row[1] ?? ''));

                                            if (blank($creatorName) || !in_array($channel, $validChannels, true)) {
                                                $skipped++;
                                                continue;
                                            }

                                            $newEntries[] = [
                                                'creator_name' => $creatorName,
                                                'channel' => $channel,
                                                'url' => trim($row[2] ?? ''),
                                                'price' => preg_replace('/[^0-9]/', '', $row[3] ?? '0') ?: '0',
                                            ];
                                        }

                                        $existing = $get('kol_entries') ?? [];
                                        $set('kol_entries', array_merge($existing, $newEntries));

                                        $total = count($newEntries);

                                        if ($total === 0) {
                                            Notification::make()
                                                ->title('Tidak ada data yang valid ditemukan')
                                                ->warning()
                                                ->send();
                                            return;
                                        }

                                        Notification::make()
                                            ->title("{$total} creator berhasil diimport" . ($skipped > 0 ? ", {$skipped} baris dilewati" : ''))
                                            ->success()
                                            ->send();
                                    }),
                            ])->columnSpanFull()->alignment(\Filament\Support\Enums\Alignment::Center),
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
                                        ->default(0)
                                        ->mask(RawJs::make('$money($input)'))
                                        ->stripCharacters(','),

                                    TextInput::make('report_link')
                                        ->label('Report Link')
                                        ->placeholder('Link to report document')
                                        ->url(),
                                ])->columns(2),
                        ]),
                ])
                    ->columnSpanFull()
                    ->skippable()
                    ->nextAction(fn(Action $action) => $action->extraAttributes(['style' => 'display:none!important;']))
                    ->previousAction(fn(Action $action) => $action->extraAttributes(['style' => 'display:none!important;'])),
            ]);
    }

}
