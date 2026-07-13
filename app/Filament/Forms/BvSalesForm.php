<?php

namespace App\Filament\Forms;

use App\Enums\SalesStatus;
use App\Filament\Resources\DataClients\Schemas\DataClientForm;
use App\Models\BvSalesList;
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
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Support\RawJs;
use Illuminate\Support\HtmlString;
use Illuminate\Support\Str;

class BvSalesForm
{
    public static function getFormComponents(): array
    {
        return [
            \Filament\Schemas\Components\Grid::make(1)
                ->hidden(fn (string $operation): bool => $operation === 'create')
                ->schema([
                    Placeholder::make('campaign_progress_summary')
                        ->label('')
                        ->columnSpanFull()
                        ->content(function ($record) {
                            if (! $record) {
                                return '';
                            }

                            // Tahapan happy-path (Close Lost ditangani terpisah di bawah)
                            $steps = [
                                SalesStatus::NOT_STARTED,
                                SalesStatus::PITCHING,
                                SalesStatus::BRIEFING,
                                SalesStatus::PROPOSAL_BUILDING,
                                SalesStatus::NEGOTIATION,
                                SalesStatus::CAMPAIGN_LIVE,
                                SalesStatus::REPORTING,
                                SalesStatus::INVOICING,
                                SalesStatus::PAID,
                            ];

                            $current = $record->status instanceof SalesStatus
                                ? $record->status
                                : SalesStatus::tryFrom((string) $record->status);

                            $isLost = $current === SalesStatus::CLOSE_LOSE;
                            $currentIndex = $isLost ? -1 : array_search($current, $steps, true);
                            if ($currentIndex === false) {
                                $currentIndex = -1;
                            }

                            $done = '#22c55e';      // hijau: selesai
                            $now  = '#a855f7';      // ungu (brand): tahap sekarang
                            $todo = '#4b5563';      // abu: belum

                            $html = '<style>'
    . '@keyframes bv-pulse{0%{box-shadow:0 0 0 0 rgba(168,85,247,0.55);}70%{box-shadow:0 0 0 9px rgba(168,85,247,0);}100%{box-shadow:0 0 0 0 rgba(168,85,247,0);}}'
    . '.bv-sl-done{color:#15803d}.bv-sl-now{color:#7e22ce;font-weight:700}.bv-sl-todo{color:#6b7280}'
    . '.dark .bv-sl-done{color:#4ade80}.dark .bv-sl-now{color:#d8b4fe;font-weight:700}.dark .bv-sl-todo{color:#9ca3af}'
    . '</style>';
                            $html .= '<div style="display:flex;align-items:flex-start;padding:20px 2px 6px;">';
                            foreach ($steps as $i => $step) {
                                $state = $i < $currentIndex ? 'done' : ($i === $currentIndex ? 'now' : 'todo');
                                $dotColor = $state === 'done' ? $done : ($state === 'now' ? $now : 'transparent');
                                $border   = $state === 'todo' ? $todo : $dotColor;
                                $ring     = $state === 'now' ? 'animation:bv-pulse 1.6s ease-out infinite;' : '';
                                $inner    = $state === 'done'
                                    ? '<svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="3"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>'
                                    : '';
                                $labelClass = 'bv-sl-' . $state;

                                if ($i > 0) {
                                    $lineColor = $i <= $currentIndex ? $done : $todo;
                                    $html .= '<div style="flex:1;height:2px;background:'.$lineColor.';margin-top:10px;"></div>';
                                }

                                $html .= '
                                    <div style="display:flex;flex-direction:column;align-items:center;width:46px;flex:0 0 auto;">
                                        <div style="width:22px;height:22px;border-radius:999px;background:'.$dotColor.';border:2px solid '.$border.';display:flex;align-items:center;justify-content:center;'.$ring.'">'.$inner.'</div>
                                        <div class="'.$labelClass.'" style="font-size:9px;line-height:1.1;text-align:center;margin-top:5px;">'.e($step->getLabel()).'</div>
                                    </div>';
                            }
                            $html .= '</div>';

                            if ($isLost) {
                                $html .= '<div style="margin-top:10px;padding:6px 10px;border-radius:6px;background:rgba(239,68,68,0.15);color:#f87171;font-size:12px;font-weight:600;display:inline-block;">&#10005; Close Lost</div>';
                            }

                            return new HtmlString($html);
                        }),
                ]),

            Section::make('Campaign Information')
                ->description('Campaign information details')
                ->schema([
                    Grid::make(2)
                        ->schema([
                            TextInput::make('event_name')
                                ->label('Event/Campaign Name')
                                ->placeholder('e.g. Campaign Ramadan 2026')
                                ->required()
                                ->maxLength(255),

                            Select::make('bv_sales_list_id')
                                ->label('Sales Name')
                                ->options(fn () => BvSalesList::orderBy('nama_sales')->pluck('nama_sales', 'id'))
                                ->searchable()
                                ->preload()
                                ->live()
                                ->required(),

                            Placeholder::make('company_display')
                                ->label('Tipe Client')
                                ->content(fn ($record) => $record?->company_name ?? '-')
                                ->hintAction(
                                    Action::make('viewClient')
                                        ->label('Detail')
                                        ->icon('heroicon-o-eye')
                                        ->color('primary')
                                        ->modalHeading(fn ($record) => $record?->company_name ?? 'Client Detail')
                                        ->modalWidth('2xl')
                                        ->modalSubmitAction(false)
                                        ->modalCancelActionLabel('Tutup')
                                        ->infolist(function ($record) {
                                            $client = DataClient::where('nama_brand', $record?->company_name)->first();
                                            if (! $client) {
                                                return [
                                                    TextEntry::make('not_found')
                                                        ->label('')
                                                        ->getStateUsing(fn () => 'Data client tidak ditemukan')
                                                        ->color('danger'),
                                                ];
                                            }

                                            return [
                                                Section::make('Client Information')
                                                    ->schema([
                                                        Grid::make(2)->schema([
                                                            TextEntry::make('type')
                                                                ->label('Client Type')
                                                                ->getStateUsing(fn () => ucfirst($client->type ?? '-'))
                                                                ->badge()
                                                                ->color(fn () => $client->type === 'agency' ? 'warning' : 'info'),
                                                            TextEntry::make('nama_brand')
                                                                ->label('Brand Name')
                                                                ->getStateUsing(fn () => $client->nama_brand ?? '-'),
                                                            TextEntry::make('produk')
                                                                ->label('Product')
                                                                ->getStateUsing(fn () => $client->produk ?? '-'),
                                                            TextEntry::make('category')
                                                                ->label('Category')
                                                                ->getStateUsing(fn () => $client->category ?? '-'),
                                                            TextEntry::make('priority')
                                                                ->label('Priority')
                                                                ->getStateUsing(fn () => $client->priority ?? '-'),
                                                            TextEntry::make('website')
                                                                ->label('Website')
                                                                ->getStateUsing(fn () => $client->website ?? '-')
                                                                ->url(fn () => $client->website),
                                                        ]),
                                                    ]),
                                                Section::make('PIC Client')
                                                    ->schema([
                                                        Grid::make(2)->schema(
                                                            ! empty($client->pic_clients)
                                                            ? collect($client->pic_clients)->flatMap(fn ($pic, $i) => [
                                                                TextEntry::make("pc_{$i}_name")
                                                                    ->label('Nama PIC Client')
                                                                    ->getStateUsing(fn () => $pic['name'] ?? '-'),
                                                                TextEntry::make("pc_{$i}_role")
                                                                    ->label('Jabatan')
                                                                    ->getStateUsing(fn () => $pic['role'] ?? '-'),
                                                                TextEntry::make("pc_{$i}_email")
                                                                    ->label('Email')
                                                                    ->getStateUsing(fn () => $pic['email'] ?? '-'),
                                                                TextEntry::make("pc_{$i}_wa")
                                                                    ->label('WhatsApp')
                                                                    ->getStateUsing(fn () => $pic['wa_number'] ?? '-'),
                                                                TextEntry::make("pc_{$i}_leads")
                                                                    ->label('PIC Leads')
                                                                    ->getStateUsing(fn () => $pic['pic_leads'] ?? '-')
                                                                    ->columnSpanFull(),
                                                            ])->toArray()
                                                            : [
                                                                TextEntry::make('no_pic_client')
                                                                    ->label('')
                                                                    ->getStateUsing(fn () => 'Belum ada PIC Client')
                                                                    ->columnSpanFull(),
                                                            ]
                                                        ),
                                                    ]),
                                                Section::make('Tracking')
                                                    ->schema([
                                                        Grid::make(2)->schema([
                                                            TextEntry::make('status')
                                                                ->label('Status')
                                                                ->getStateUsing(fn () => $client->status ?? '-')
                                                                ->badge(),
                                                            TextEntry::make('date_outreach')
                                                                ->label('Date Outreach')
                                                                ->getStateUsing(fn () => $client->date_outreach ?? '-'),
                                                            TextEntry::make('date_follow_up')
                                                                ->label('Date Follow Up')
                                                                ->getStateUsing(fn () => $client->date_follow_up ?? '-'),
                                                            TextEntry::make('notes')
                                                                ->label('Notes')
                                                                ->getStateUsing(fn () => $client->notes ?? '-')
                                                                ->columnSpanFull(),
                                                        ]),
                                                    ]),
                                            ];
                                        })
                                )
                                ->hidden(fn (string $operation): bool => $operation === 'create'),

                            Select::make('company_name')
                                ->label('Tipe Client')
                                ->searchable()
                                ->live()
                                ->afterStateUpdated(fn (Set $set) => $set('related_client_name', null))
                                ->getSearchResultsUsing(fn (string $search): array => DataClient::where('nama_brand', 'like', "%{$search}%")->limit(50)->pluck('nama_brand', 'nama_brand')->toArray())
                                ->options(DataClient::limit(50)->pluck('nama_brand', 'nama_brand'))
                                ->createOptionForm(DataClientForm::getFormSchema())
                                ->createOptionUsing(function (array $data): string {
                                    $client = DataClient::create($data);

                                    return $client->nama_brand;
                                })
                                ->hint(function ($state): ?HtmlString {
                                    if (! $state) {
                                        return null;
                                    }
                                    $client = DataClient::where('nama_brand', $state)->first();
                                    if (! $client) {
                                        return null;
                                    }
                                    [$label, $bg, $color] = match ($client->type) {
                                        'agency' => ['Agency', '#fef3c7', '#92400e'],
                                        'direct' => ['Direct Brand', '#dbeafe', '#1e40af'],
                                        default => [null, null, null],
                                    };
                                    if (! $label) {
                                        return null;
                                    }
                                    $html = '<span style="display:inline-flex;align-items:center;padding:2px 10px;border-radius:999px;font-size:11px;font-weight:600;background:'.$bg.';color:'.$color.';">'.$label.'</span>';
                                    if ($client->type === 'agency' && ! empty($client->agency_name)) {
                                        $agencyNames = is_array($client->agency_name) ? implode(', ', $client->agency_name) : $client->agency_name;
                                        $html .= ' <span style="font-size:11px;color:#6b7280;margin-left:4px;">('.e($agencyNames).')</span>';
                                    }

                                    // Brand yang dihandel agency sudah tampil di field "Brand yang Dihandel Agency" di sampingnya.

                                    if ($client->type === 'direct') {
                                        $agencies = collect($client->pics ?? [])
                                            ->pluck('agency')
                                            ->filter()
                                            ->unique()
                                            ->values();

                                        if ($agencies->isEmpty() && $client->agency_client_id) {
                                            $agencies = collect([$client->agency?->nama_brand])->filter();
                                        }

                                        if ($agencies->isNotEmpty()) {
                                            $html .= ' <span style="font-size:11px;color:#6b7280;margin-left:4px;">via Agency: '.e($agencies->implode(', ')).'</span>';
                                        }
                                    }

                                    return new HtmlString($html);
                                })
                                ->placeholder('Select or create')
                                ->required()
                                ->hidden(fn (string $operation): bool => $operation === 'edit'),

                            // Field dinamis: pasangan client (brand <-> agency)
                            Select::make('related_client_name')
                                ->label(fn (Get $get): string => match (self::resolveClientType($get('company_name'))) {
                                    'agency' => 'Brand yang Dihandel Agency',
                                    'direct' => 'Agency yang Menghandle Brand',
                                    default => 'Brand / Agency Terkait',
                                })
                                ->placeholder(fn (Get $get): string => match (self::resolveClientType($get('company_name'))) {
                                    'agency' => 'Pilih brand yang dihandel agency...',
                                    'direct' => 'Pilih agency yang menghandle brand...',
                                    default => 'Pilih client terlebih dahulu',
                                })
                                ->helperText(fn (Get $get): ?string => match (self::resolveClientType($get('company_name'))) {
                                    'agency' => 'Brand-brand yang berada di bawah agency ini.',
                                    'direct' => 'Agency yang menangani brand ini.',
                                    default => null,
                                })
                                ->options(fn (Get $get): array => self::relatedClientOptions($get('company_name')))
                                ->searchable()
                                ->native(false)
                                // Sembunyikan bila tak ada pasangan: brand tanpa agency (atau agency tanpa brand).
                                ->visible(fn (Get $get): bool => filled($get('company_name'))
                                    && self::resolveClientType($get('company_name')) !== null
                                    && ! empty(self::relatedClientOptions($get('company_name')))),

                            Select::make('campaign_items')
                                ->label('Campaign Items')
                                ->multiple()
                                ->options([
                                    'Active Services' => [
                                        'influencer' => 'Influencer',
                                        'social_media_mgmt' => 'Social Media Management',
                                    ],
                                    'Coming Soon' => [
                                        'affiliate' => 'Affiliate (Soon)',
                                        'smm' => 'SMM (Soon)',
                                        'tiktok_clippers' => 'TikTok Clipper (Soon)',
                                        'digital_video' => 'Digital Video (Soon)',
                                    ],
                                ])
                                ->searchable(),

                            TextInput::make('budget_propose')
                                ->label('Budget Propose')
                                ->hintIcon('heroicon-m-information-circle', tooltip: 'Budget awal yang diajukan ke klien')
                                ->prefix('Rp')
                                ->mask(RawJs::make(<<<'JS'
                            $money($input, ',', '.', 0)
                        JS))
                                ->formatStateUsing(fn ($state) => $state ? (int) $state : 0)
                                ->dehydrateStateUsing(fn ($state) => (int) str_replace('.', '', $state))
                                ->default(0),

                            TextInput::make('deal_value')
                                ->label('Deal Value')
                                ->hintIcon('heroicon-m-information-circle', tooltip: 'Nilai akhir yang disepakati')
                                ->prefix('Rp')
                                ->mask(RawJs::make(<<<'JS'
                            $money($input, ',', '.', 0)
                        JS))
                                ->formatStateUsing(fn ($state) => $state ? (int) $state : 0)
                                ->dehydrateStateUsing(fn ($state) => (int) str_replace('.', '', $state))
                                ->default(0),

                        ]),
                ]),

            Section::make('Campaign Schedule')
                ->description('Campaign timeline information')
                ->schema([
                    Grid::make(2)
                        ->schema([
                            Select::make('campaign_year')
                                ->label('Campaign Year')
                                ->options(function () {
                                    $currentYear = now()->year;
                                    $years = [];
                                    for ($i = $currentYear - 2; $i <= $currentYear + 2; $i++) {
                                        $years[$i] = (string) $i;
                                    }

                                    return $years;
                                })
                                ->default(now()->year),

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

                            DatePicker::make('start_date')
                                ->label('Start Date')
                                ->placeholder('Select Start Date')
                                ->native(false)
                                ->displayFormat('d M Y'),

                            DatePicker::make('end_date')
                                ->label('End Date')
                                ->placeholder('Select End Date')
                                ->native(false)
                                ->displayFormat('d M Y')
                                ->afterOrEqual('start_date'),

                            DatePicker::make('close_date')
                                ->native(false)
                                ->placeholder('Select Close Date')
                                ->displayFormat('d M Y')
                                ->label('Close Date'),

                            DatePicker::make('brief_submit_date')
                                ->label('Date of Brief')
                                ->placeholder('Select Date of Brief')
                                ->native(false)
                                ->displayFormat('d M Y'),
                        ]),

                    Select::make('form_brief_id')
                        ->label('Select from Brief')
                        ->placeholder('Select brief from client...')
                        ->options(function () {
                            return FormBrief::where('status', 'submitted')
                                ->orWhere('status', 'reviewed')
                                ->orderBy('created_at', 'desc')
                                ->get()
                                ->mapWithKeys(fn (FormBrief $brief) => [
                                    $brief->id => $brief->title.' — '.($brief->submitted_by_name ?? 'Unknown'),
                                ]);
                        })
                        ->searchable()->columnSpanFull()
                        ->preload()
                        ->hidden(fn (string $operation): bool => $operation === 'create'),

                    FileUpload::make('brief_files')
                        ->label('Upload Brief (PDF) — Legacy / Archive')
                        ->helperText('Field lama. Upload brief baru via "Brief History" di bawah supaya tercatat dengan tanggal & catatan.')
                        ->multiple()
                        ->directory('sales-briefs')
                        ->acceptedFileTypes(['application/pdf'])
                        ->maxSize(10240)
                        ->downloadable()
                        ->openable()
                        ->reorderable()
                        ->columnSpanFull()
                        ->hidden(fn (string $operation): bool => $operation === 'create'),

                    Repeater::make('briefHistories')
                        ->relationship()
                        ->label('Brief History')
                        ->hidden(fn (string $operation): bool => $operation === 'create')
                        ->helperText('Setiap revisi brief dari client (file atau link) — append, tidak overwrite.')
                        ->addActionLabel('+ Tambah Brief Baru')
                        ->orderColumn(false)
                        ->defaultItems(0)
                        ->collapsible()
                        ->collapsed()
                        ->itemLabel(function (array $state): ?string {
                            $type = $state['type'] ?? 'file';
                            $hint = $type === 'link'
                                ? ($state['link_url'] ?? 'Link brief')
                                : (isset($state['file_path']) ? basename(is_array($state['file_path']) ? reset($state['file_path']) : $state['file_path']) : 'File brief');

                            return ucfirst($type).' — '.Str::limit($hint, 60);
                        })
                        ->schema([
                            Select::make('type')
                                ->label('Tipe')
                                ->options([
                                    'file' => 'Upload File',
                                    'link' => 'Link Brief',
                                ])
                                ->default('file')
                                ->required()
                                ->live(),

                            FileUpload::make('file_path')
                                ->label('File Brief')
                                ->directory('sales-briefs')
                                ->acceptedFileTypes(['application/pdf', 'image/png', 'image/jpeg', 'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'])
                                ->maxSize(10240)
                                ->downloadable()
                                ->openable()
                                ->visible(fn (callable $get) => $get('type') === 'file')
                                ->required(fn (callable $get) => $get('type') === 'file')
                                ->columnSpanFull(),

                            TextInput::make('link_url')
                                ->label('Link Brief')
                                ->url()
                                ->placeholder('https://...')
                                ->visible(fn (callable $get) => $get('type') === 'link')
                                ->required(fn (callable $get) => $get('type') === 'link')
                                ->columnSpanFull(),

                            Textarea::make('notes')
                                ->label('Catatan')
                                ->placeholder('Misal: revisi pertama, sudah include budget breakdown...')
                                ->rows(2)
                                ->columnSpanFull(),

                            Placeholder::make('created_at_display')
                                ->label('Tanggal Upload')
                                ->content(fn ($record) => $record?->created_at?->format('d M Y H:i') ?? 'Akan otomatis terisi saat disimpan')
                                ->visible(fn ($record) => $record !== null),
                        ])
                        ->mutateRelationshipDataBeforeCreateUsing(function (array $data): array {
                            $data['uploaded_by'] = auth()->id();

                            return $data;
                        })
                        ->columnSpanFull(),
                ]),

            Section::make('Status & Detail Campaign')
                ->description('Campaign status and detail information')
                ->schema([
                    Grid::make(2)
                        ->schema([
                            Select::make('status')
                                ->label('Status')
                                ->options(SalesStatus::toArray())->columnSpanFull()
                                ->default(SalesStatus::NOT_STARTED->value)
                                ->required(),

                            Textarea::make('detail')
                                ->label('Detail')
                                ->placeholder('Enter details if any...')
                                ->rows(3)
                                ->columnSpan(2),
                        ]),
                ]),

            Section::make('Meeting Progress')
                ->description('Notes from client meetings')
                ->icon('heroicon-o-users')
                ->collapsible()
                ->hidden(fn (string $operation): bool => $operation === 'create')
                ->schema([
                    Textarea::make('meeting_notes')
                        ->label('Meeting Notes')
                        ->placeholder('Write meeting progress / results here...')
                        ->rows(4)
                        ->columnSpanFull(),
                ]),

            Section::make('Quotation Sign')
                ->description('Upload dokumen Quotation Sign setelah campaign live')
                ->icon('heroicon-o-document-check')
                ->collapsible()
                ->hidden(
                    fn (string $operation, $record): bool => $operation === 'create' ||
                    ($record?->status !== SalesStatus::CAMPAIGN_LIVE && $record?->status?->value !== SalesStatus::CAMPAIGN_LIVE->value)
                )
                ->schema([
                    FileUpload::make('quotation_sign')
                        ->label('Upload Quotation Sign')
                        ->multiple()
                        ->directory('quotation-signs')
                        ->acceptedFileTypes(['application/pdf', 'image/png', 'image/jpeg', 'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'])
                        ->maxSize(10240)
                        ->downloadable()
                        ->openable()
                        ->reorderable()
                        ->columnSpanFull(),
                ]),

            Section::make('Brief & History')
                ->description('Brief preview and upload history')
                ->icon('heroicon-o-document-arrow-up')
                ->collapsible()
                ->collapsed()
                ->hidden(fn (string $operation): bool => $operation === 'create')
                ->schema([
                    // Brief dari client — tampil ketika FormBrief sudah ada
                    Placeholder::make('client_brief_preview')
                        ->label('')
                        ->hidden(fn ($record) => ! $record?->formBrief)
                        ->content(function ($record) {
                            $brief = $record?->formBrief;
                            if (! $brief) {
                                return '';
                            }

                            $statusColors = [
                                'draft' => ['bg' => '#f3f4f6', 'text' => '#374151'],
                                'submitted' => ['bg' => '#dbeafe', 'text' => '#1e40af'],
                                'reviewed' => ['bg' => '#fef3c7', 'text' => '#92400e'],
                                'approved' => ['bg' => '#dcfce7', 'text' => '#14532d'],
                                'revision' => ['bg' => '#fee2e2', 'text' => '#991b1b'],
                            ];
                            $sc = $statusColors[$brief->status] ?? $statusColors['draft'];

                            $rows = [];

                            if ($brief->submitted_by_name) {
                                $rows[] = ['label' => 'Submitted by', 'value' => e($brief->submitted_by_name).($brief->submitted_at ? ' · '.Carbon::parse($brief->submitted_at)->format('d M Y') : '')];
                            }
                            if ($brief->timeline) {
                                $rows[] = ['label' => 'Timeline', 'value' => e($brief->timeline)];
                            }
                            if ($brief->campaign_objective) {
                                $rows[] = ['label' => 'Campaign Objective', 'value' => nl2br(e($brief->campaign_objective))];
                            }
                            if ($brief->criteria_of_kol) {
                                $rows[] = ['label' => 'Criteria of KOL', 'value' => nl2br(e($brief->criteria_of_kol))];
                            }
                            if ($brief->sow) {
                                $rows[] = ['label' => 'Scope of Work', 'value' => nl2br(e($brief->sow))];
                            }
                            if ($brief->budget) {
                                $rows[] = ['label' => 'Budget Campaign', 'value' => 'Rp '.number_format((float) $brief->budget, 0, ',', '.')];
                            }
                            if ($brief->deadline) {
                                $rows[] = ['label' => 'Deadline', 'value' => e($brief->deadline)];
                            }
                            if ($brief->additional_notes) {
                                $rows[] = ['label' => 'Catatan', 'value' => nl2br(e($brief->additional_notes))];
                            }

                            $rowsHtml = '';
                            foreach ($rows as $row) {
                                $rowsHtml .= '<tr>
                                    <td style="padding:6px 8px;font-size:12px;color:#6b7280;white-space:nowrap;vertical-align:top;width:140px;">'.$row['label'].'</td>
                                    <td style="padding:6px 8px;font-size:13px;color:#111827;">'.$row['value'].'</td>
                                </tr>';
                            }

                            $linksHtml = '';
                            if ($brief->sheet_link_external) {
                                $linksHtml .= '<a href="'.e($brief->sheet_link_external).'" target="_blank" rel="noopener noreferrer"
                                    style="display:inline-flex;align-items:center;gap:4px;font-size:12px;color:#3b82f6;text-decoration:none;margin-right:12px;">
                                    <svg width="12" height="12" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M13.19 8.688a4.5 4.5 0 0 1 1.242 7.244l-4.5 4.5a4.5 4.5 0 0 1-6.364-6.364l1.757-1.757m13.35-.622 1.757-1.757a4.5 4.5 0 0 0-6.364-6.364l-4.5 4.5a4.5 4.5 0 0 0 1.242 7.244"/></svg>
                                    Brief Client</a>';
                            }
                            if ($brief->sheet_link_internal) {
                                $linksHtml .= '<a href="'.e($brief->sheet_link_internal).'" target="_blank" rel="noopener noreferrer"
                                    style="display:inline-flex;align-items:center;gap:4px;font-size:12px;color:#8b5cf6;text-decoration:none;">
                                    <svg width="12" height="12" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M13.19 8.688a4.5 4.5 0 0 1 1.242 7.244l-4.5 4.5a4.5 4.5 0 0 1-6.364-6.364l1.757-1.757m13.35-.622 1.757-1.757a4.5 4.5 0 0 0-6.364-6.364l-4.5 4.5a4.5 4.5 0 0 0 1.242 7.244"/></svg>
                                    Internal Sheet</a>';
                            }

                            return new HtmlString('
                                <div style="border:1px solid #e5e7eb;border-radius:8px;overflow:hidden;font-family:inherit;">
                                    <div style="padding:10px 14px;background:#f9fafb;border-bottom:1px solid #e5e7eb;display:flex;align-items:center;justify-content:space-between;">
                                        <div style="display:flex;align-items:center;gap:8px;">
                                            <span style="font-size:13px;font-weight:600;color:#111827;">'.e($brief->title).'</span>
                                            <span style="padding:2px 8px;border-radius:999px;font-size:11px;font-weight:600;background:'.$sc['bg'].';color:'.$sc['text'].';">'.ucfirst($brief->status).'</span>
                                        </div>
                                        '.($linksHtml ? '<div style="display:flex;gap:4px;">'.$linksHtml.'</div>' : '').'
                                    </div>
                                    '.($rowsHtml ? '<table style="width:100%;border-collapse:collapse;">'.$rowsHtml.'</table>' : '').'
                                </div>
                            ');
                        })
                        ->columnSpanFull(),
                ]),
        ];
    }

    /** Tipe client (agency|direct|null) berdasarkan nama brand yang dipilih di company_name. */
    protected static function resolveClientType(?string $companyName): ?string
    {
        if (blank($companyName)) {
            return null;
        }

        return DataClient::where('nama_brand', $companyName)->value('type');
    }

    /**
     * Opsi field pasangan client:
     * - agency  → daftar brand yang dihandel agency tsb (dari agency_brands).
     * - direct  → daftar agency yang menghandle brand tsb (dari pics brand + agency yang mencantumkan brand ini).
     */
    protected static function relatedClientOptions(?string $companyName): array
    {
        if (blank($companyName)) {
            return [];
        }

        $client = DataClient::where('nama_brand', $companyName)->first();
        if (! $client) {
            return [];
        }

        if ($client->type === 'agency') {
            return collect($client->agency_brands ?? [])
                ->pluck('nama_brand')
                ->filter()
                ->unique()
                ->values()
                ->mapWithKeys(fn ($name) => [$name => $name])
                ->all();
        }

        if ($client->type === 'direct') {
            // 1) Agency yang tercatat langsung di PIC brand
            $fromPics = collect($client->pics ?? [])->pluck('agency')->filter();

            // 2) Agency yang mencantumkan brand ini di daftar agency_brands miliknya
            $fromAgencies = DataClient::where('type', 'agency')
                ->get()
                ->filter(fn ($agency) => collect($agency->agency_brands ?? [])
                    ->pluck('nama_brand')
                    ->contains($client->nama_brand))
                ->pluck('nama_brand');

            return $fromPics->merge($fromAgencies)
                ->filter()
                ->unique()
                ->values()
                ->mapWithKeys(fn ($name) => [$name => $name])
                ->all();
        }

        return [];
    }
}
