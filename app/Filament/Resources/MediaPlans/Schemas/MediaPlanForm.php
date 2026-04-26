<?php

namespace App\Filament\Resources\MediaPlans\Schemas;

use Filament\Schemas\Schema;
use App\Models\DataKol;
use App\Models\MasterPph;
use App\Enums\VendorTaxType;
use App\Service\InstagramService;
use App\Service\TiktokService;
use App\Service\YoutubeChannelsService;
use App\Service\YoutubeShortsService;
use App\Helpers\QuotationNumberGenerator;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Repeater\TableColumn;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\Checkbox;
use Filament\Forms\Components\TagsInput;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\ToggleButtons;
use App\Filament\Forms\Components\KolDetailsRow;
use Filament\Schemas\Components\Fieldset;
use Filament\Forms\Components\ViewField;
use Filament\Schemas\Components\Wizard;
use Filament\Schemas\Components\Wizard\Step;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Grid;
use Filament\Forms\Components\DatePicker;
use Filament\Notifications\Notification;
use Filament\Actions\Action;
use Filament\Schemas\Components\Actions;
use Filament\Support\RawJs;

class MediaPlanForm
{
    /**
     * Parse formatted number string to float
     * Converts "2.000.000" or "2.000.000,50" to 2000000.50
     */
    private static function parseNumber($value): float
    {
        if (empty($value))
            return 0;
        if (is_numeric($value))
            return (float) $value;

        $value = (string) $value;

        // Remove all non-numeric except . and ,
        $value = preg_replace('/[^\d.,]/', '', $value);

        $dotCount = substr_count($value, '.');
        $commaCount = substr_count($value, ',');

        // Case 1: Only commas (US format from $money mask) - "400,000"
        if ($commaCount > 0 && $dotCount == 0) {
            return (float) str_replace(',', '', $value);
        }

        // Case 2: Only dots (Indonesia format) - "400.000"
        if ($dotCount > 0 && $commaCount == 0) {
            // Check if it's thousand separator (more than 1 dot or position)
            if ($dotCount > 1) {
                return (float) str_replace('.', '', $value);
            }
            // Check position - if 3 digits after single dot, it's thousand separator
            $parts = explode('.', $value);
            if (count($parts) == 2 && strlen($parts[1]) == 3) {
                return (float) str_replace('.', '', $value);
            }
            // Otherwise treat as decimal
            return (float) $value;
        }

        // Case 3: Both (e.g., "1.234,56" Indonesia or "1,234.56" US)
        if ($dotCount > 0 && $commaCount > 0) {
            $lastDot = strrpos($value, '.');
            $lastComma = strrpos($value, ',');

            if ($lastDot > $lastComma) {
                // US format: "1,234.56" - comma thousand, dot decimal
                return (float) str_replace(',', '', $value);
            } else {
                // Indonesia format: "1.234,56" - dot thousand, comma decimal
                $cleaned = str_replace('.', '', $value);
                $cleaned = str_replace(',', '.', $cleaned);
                return (float) $cleaned;
            }
        }

        return (float) $value;
    }

    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Wizard::make([
                    Step::make('Campaign Information')
                        ->icon('heroicon-m-document-text')
                        ->description('Campaign details & client info')
                        ->schema([
                            Section::make('Campaign Information')
                                ->schema([

                                    Placeholder::make('campaign_items_display')
                                        ->label('Campaign Items')
                                        ->content(function ($record) {
                                            if (!$record?->bvSales) {
                                                return new \Illuminate\Support\HtmlString('<span style="color:#9ca3af;">-</span>');
                                            }
                                            $items = $record->bvSales->campaign_items ?? [];
                                            $labels = [
                                                'influencer' => 'Influencer',
                                                'social_media_mgmt' => 'Social Media Management',
                                                'affiliate' => 'Affiliate',
                                                'smm' => 'SMM',
                                            ];
                                            $rendered = collect($items)
                                                ->map(fn($i) => $labels[$i] ?? $i)
                                                ->join(', ');
                                            return $rendered ?: '-';
                                        }),
                                    Select::make('campaign_name')
                                        ->label('Campaign Name')
                                        ->options(fn() => \App\Models\BvSales::pluck('event_name', 'event_name'))
                                        ->searchable()
                                        ->preload()
                                        ->placeholder('Pilih Sales Activity')
                                        ->required(),
                                    DatePicker::make('campaign_period_start')
                                        ->label('Campaign Period Start')
                                        ->native(false)
                                        ->displayFormat('d/m/Y')
                                        ->placeholder('e.g., November 2025')
                                        ->afterStateHydrated(function ($component, $record) {
                                            if ($record?->bvSales?->start_date && !$component->getState()) {
                                                $component->state($record->bvSales->start_date->format('Y-m-d'));
                                            }
                                        })
                                        ->readOnly(fn($record) => (bool) $record?->bvSales),
                                    DatePicker::make('campaign_period_end')
                                        ->label('Campaign Period End')
                                        ->native(false)
                                        ->displayFormat('d/m/Y')
                                        ->placeholder('e.g., December 2025')
                                        ->afterStateHydrated(function ($component, $record) {
                                            if ($record?->bvSales?->end_date && !$component->getState()) {
                                                $component->state($record->bvSales->end_date->format('Y-m-d'));
                                            }
                                        })
                                        ->readOnly(fn($record) => (bool) $record?->bvSales),
                                    TextInput::make('platform')
                                        ->label('Platform')
                                        ->placeholder('e.g., Social Media'),

                                ])->columns(2),

                            Section::make('Detail Brand')
                                ->schema([
                                    Select::make('brand')
                                        ->label('Brand/Client')
                                        ->options(\App\Models\DataClient::pluck('nama_brand', 'nama_brand'))
                                        ->searchable()
                                        ->preload()
                                        ->placeholder('Pilih Brand/Client')
                                        ->required()
                                        ->live()
                                        ->afterStateUpdated(function ($state, callable $set) {
                                            if ($state) {
                                                $client = \App\Models\DataClient::where('nama_brand', $state)->first();
                                                if ($client) {
                                                    $set('pic_client', $client->nama_pic);
                                                }
                                            }
                                        }),
                                    Actions::make([
                                        Action::make('lihat_pic_client')
                                            ->label(function (callable $get) {
                                                $brand = $get('brand');
                                                if (!$brand)
                                                    return 'Lihat PIC Client';
                                                $client = \App\Models\DataClient::where('nama_brand', $brand)->first();
                                                $count = count($client?->pic_clients ?? []);
                                                return "Lihat PIC Client ({$count})";
                                            })
                                            ->icon('heroicon-o-users')
                                            ->color('white')
                                            ->modalHeading('Daftar PIC Client')
                                            ->modalContent(function (callable $get) {
                                                $brand = $get('brand');
                                                if (!$brand) {
                                                    return new \Illuminate\Support\HtmlString('<p style="color:#6b7280;padding:16px;">Brand belum dipilih.</p>');
                                                }
                                                $client = \App\Models\DataClient::where('nama_brand', $brand)->first();
                                                $pics = $client?->pic_clients ?? [];
                                                if (empty($pics)) {
                                                    return new \Illuminate\Support\HtmlString('<p style="color:#6b7280;padding:16px;">Tidak ada PIC Client terdaftar.</p>');
                                                }
                                                $rows = collect($pics)->map(function ($pic, $i) {
                                                    $no = $i + 1;
                                                    $name = e($pic['name'] ?? '-');
                                                    $jabatan = e($pic['role'] ?? '-');
                                                    $email = e($pic['email'] ?? '-');
                                                    $wa = e($pic['wa_number'] ?? '-');
                                                    $leads = e($pic['pic_leads'] ?? '-');
                                                    $bg = $i % 2 === 0 ? '#f9fafb' : '#ffffff';
                                                    return "<tr style='background:{$bg};'>
                                                        <td style='padding:10px 12px;border-bottom:1px solid #e5e7eb;color:#6b7280;font-size:13px;'>{$no}</td>
                                                        <td style='padding:10px 12px;border-bottom:1px solid #e5e7eb;font-weight:600;font-size:13px;'>{$name}</td>
                                                        <td style='padding:10px 12px;border-bottom:1px solid #e5e7eb;font-size:13px;color:#374151;'>{$jabatan}</td>
                                                        <td style='padding:10px 12px;border-bottom:1px solid #e5e7eb;font-size:13px;color:#374151;'>{$email}</td>
                                                        <td style='padding:10px 12px;border-bottom:1px solid #e5e7eb;font-size:13px;color:#374151;'>{$wa}</td>
                                                        <td style='padding:10px 12px;border-bottom:1px solid #e5e7eb;font-size:13px;color:#374151;'>{$leads}</td>
                                                    </tr>";
                                                })->join('');
                                                return new \Illuminate\Support\HtmlString(
                                                    '<div style="overflow-x:auto;">
                                                        <table style="width:100%;border-collapse:collapse;font-family:sans-serif;">
                                                            <thead>
                                                                <tr style="background:#7c3aed;">
                                                                    <th style="padding:10px 12px;text-align:left;color:#fff;font-size:12px;font-weight:600;">#</th>
                                                                    <th style="padding:10px 12px;text-align:left;color:#fff;font-size:12px;font-weight:600;">Nama PIC</th>
                                                                    <th style="padding:10px 12px;text-align:left;color:#fff;font-size:12px;font-weight:600;">Jabatan</th>
                                                                    <th style="padding:10px 12px;text-align:left;color:#fff;font-size:12px;font-weight:600;">Email</th>
                                                                    <th style="padding:10px 12px;text-align:left;color:#fff;font-size:12px;font-weight:600;">No WA</th>
                                                                    <th style="padding:10px 12px;text-align:left;color:#fff;font-size:12px;font-weight:600;">PIC Leads</th>
                                                                </tr>
                                                            </thead>
                                                            <tbody>' . $rows . '</tbody>
                                                        </table>
                                                    </div>'
                                                );
                                            })
                                            ->modalSubmitAction(false)
                                            ->modalCancelActionLabel('Tutup'),
                                    ])->label('PIC Client'),
                                    // TextInput::make('domisili')
                                    //     ->label('Domisili')->required()
                                    //     ->placeholder('e.g., Jakarta'),
                                    Select::make('pic_campaign_id')
                                        ->label('Assign Tugas Brief Ke (PIC Campaign/Sales)')
                                        ->options(\App\Models\BvSalesList::pluck('nama_sales', 'id'))
                                        ->searchable()
                                        ->preload()
                                        ->nullable()
                                        ->helperText('Assign tugas brief media plan ke PIC tim internal'),
                                ])->columns(3),
                        ]),

                    Step::make('Select KOL')
                        ->icon('heroicon-m-user-group')
                        ->description('Choose or search for multiple KOLs')
                        ->schema([
                            Section::make('📊 Summary List KOL')
                                ->description('Ringkasan otomatis dari KOL yang dicentang')
                                ->schema([
                                    Grid::make(4)
                                        ->schema([
                                            Placeholder::make('selected_count_display')
                                                ->label('Selected KOLs')
                                                ->extraAttributes(['class' => 'text-primary-600 dark:text-primary-400'])
                                                ->content(fn(callable $get) => self::getSelectedCount($get('kols') ?? [])),
                                            Placeholder::make('total_rate_display')
                                                ->label('Total Rate')
                                                ->extraAttributes(['class' => 'text-primary-600 dark:text-primary-400'])
                                                ->content(fn(callable $get) => 'Rp ' . number_format(self::getTotalRate($get('kols') ?? []), 0, ',', '.')),
                                            Placeholder::make('total_impression_display')
                                                ->label('Total Est. Views')
                                                ->extraAttributes(['class' => 'text-primary-600 dark:text-primary-400'])
                                                ->content(fn(callable $get) => number_format(self::getTotalImpression($get('kols') ?? []), 0, ',', '.')),
                                            Placeholder::make('total_engagement_display')
                                                ->label('Total Est. Engagement')
                                                ->extraAttributes(['class' => 'text-primary-600 dark:text-primary-400'])
                                                ->content(fn(callable $get) => number_format(self::getTotalEngagement($get('kols') ?? []), 0, ',', '.')),
                                        ]),
                                ])
                                ->collapsible()
                                ->collapsed(),

                            Repeater::make('kols')
                                ->label('KOL List')
                                ->schema([
                                    // ── Overview Slide-Over ───────────────────────────────
                                    Actions::make([
                                        Action::make('kol_overview')
                                            ->label('Lihat Overview KOL')
                                            ->icon('heroicon-o-eye')
                                            ->color('info')
                                            ->size('sm')
                                            ->slideOver()
                                            ->modalWidth('5xl')
                                            ->modalHeading('Overview KOL')
                                            ->visible(fn(callable $get) => !empty($get('name')))
                                            ->modalSubmitAction(false)
                                            ->modalCancelActionLabel('Tutup')
                                            ->modalContent(function (callable $get): \Illuminate\Contracts\View\View {
                                                $schedules = \App\Helpers\PaymentScheduleHelper::getUpcomingSchedules();
                                                $paymentKey = $get('payment_date');

                                                return view('filament.actions.kol-overview-modal', [
                                                    'is_selected' => ($get('is_selected') ?? false) ? 'Ya ✅' : 'Tidak',
                                                    'status' => $get('status') ?? '—',
                                                    'pic' => $get('pic') ?? '—',
                                                    'channel' => $get('channel') ?? '—',
                                                    'name' => $get('name') ?? '—',
                                                    'domisili' => $get('domisili') ?? '—',
                                                    'links' => implode(', ', (array) ($get('links') ?? [])),
                                                    'tipe_pajak_kol' => MasterPph::find($get('tipe_pajak_kol'))?->name ?? '—',
                                                    'followers' => $get('followers') ? number_format((int) $get('followers'), 0, ',', '.') : '—',
                                                    'tier' => $get('tier') ?? '—',
                                                    'er_percent' => $get('er_percent') ? $get('er_percent') . '%' : '—',
                                                    'impression' => $get('impression') ? number_format((int) $get('impression'), 0, ',', '.') : '—',
                                                    'engagement' => $get('engagement') ? number_format((int) $get('engagement'), 0, ',', '.') : '—',
                                                    'cpi_cpv' => self::parseNumber($get('cpi_cpv') ?? 0) > 0
                                                        ? 'Rp ' . number_format(round(self::parseNumber($get('cpi_cpv'))), 0, ',', '.')
                                                        : '—',
                                                    'cpe' => self::parseNumber($get('cpe') ?? 0) > 0
                                                        ? 'Rp ' . number_format(round(self::parseNumber($get('cpe'))), 0, ',', '.')
                                                        : '—',
                                                    'scope_items' => implode(', ', (array) ($get('scope_items') ?? [])),
                                                    'rate' => self::parseNumber($get('rate') ?? 0) > 0
                                                        ? 'Rp ' . number_format(round(self::parseNumber($get('rate'))), 0, ',', '.')
                                                        : '—',
                                                    'after_nego' => self::parseNumber($get('after_nego') ?? 0) > 0
                                                        ? 'Rp ' . number_format(round(self::parseNumber($get('after_nego'))), 0, ',', '.')
                                                        : '—',
                                                    'payment_date' => ($paymentKey && isset($schedules[$paymentKey]))
                                                        ? $schedules[$paymentKey]
                                                        : ($paymentKey ?? '—'),
                                                ]);
                                            }),

                                        Action::make('edit_kol_details')
                                            ->label('Edit Detail KOL')
                                            ->icon('heroicon-o-pencil-square')
                                            ->color('white')
                                            ->size('sm')
                                            ->slideOver()
                                            ->modalWidth('5xl')
                                            ->visible(fn(callable $get) => !empty($get('name')))
                                            ->modalHeading(fn(callable $get) => 'Edit KOL: ' . ($get('name') ?: 'New KOL'))
                                            ->fillForm(function (callable $get): array {
                                                return [
                                                    'channel' => $get('channel'),
                                                    'name' => $get('name'),
                                                    'domisili' => $get('domisili'),
                                                    'links' => $get('links') ?? [],
                                                    'tipe_pajak_kol' => $get('tipe_pajak_kol'),
                                                    'followers' => $get('followers'),
                                                    'tier' => $get('tier'),
                                                    'er_percent' => $get('er_percent'),
                                                    'impression' => $get('impression'),
                                                    'engagement' => $get('engagement'),
                                                    'scope_items' => $get('scope_items') ?? [],
                                                    'after_nego' => $get('after_nego'),
                                                    'payment_date' => $get('payment_date'),
                                                    'is_selected' => $get('is_selected') ?? false,
                                                    'status' => $get('status') ?? 'New List',
                                                    'pic' => $get('pic'),
                                                    'notes' => $get('notes'),
                                                ];
                                            })
                                            ->form([
                                                // ── Detail KOL ──────────────────────────
                                                Section::make('Detail KOL')
                                                    ->schema([
                                                        Select::make('channel')
                                                            ->label('Channel')
                                                            ->options([
                                                                'Instagram' => 'Instagram',
                                                                'Tiktok' => 'TikTok',
                                                                'Threads' => 'Threads',
                                                                'Youtube Channels' => 'YouTube Channels',
                                                                'Youtube Shorts' => 'YouTube Shorts',
                                                                'Facebook' => 'Facebook',
                                                                'Talent' => 'Talent',
                                                                'X' => 'X (Twitter)',
                                                            ])
                                                            ->required(),

                                                        TextInput::make('name')
                                                            ->label('KOL Name')
                                                            ->placeholder('Username / Nama')
                                                            ->required(),

                                                        TextInput::make('domisili')
                                                            ->label('Domisili')
                                                            ->placeholder('Jakarta'),

                                                        TagsInput::make('links')
                                                            ->label('Links')
                                                            ->placeholder('URL'),

                                                        Select::make('tipe_pajak_kol')
                                                            ->label('Golongan Pajak')
                                                            ->options(function () {
                                                                return MasterPph::active()
                                                                    ->ordered()
                                                                    ->get()
                                                                    ->mapWithKeys(function ($pph) {
                                                                        $label = $pph->name;
                                                                        if ($pph->include_ppn) {
                                                                            $label .= " ({$pph->coefficient} + PPN {$pph->ppn_percent}%)";
                                                                        } else {
                                                                            $label .= " ({$pph->coefficient})";
                                                                        }
                                                                        return [$pph->id => $label];
                                                                    })
                                                                    ->toArray();
                                                            })
                                                            ->required(),
                                                    ])
                                                    ->columns(3),

                                                // ── Performance ─────────────────────────
                                                Section::make('Performance')
                                                    ->schema([
                                                        TextInput::make('followers')
                                                            ->label('Followers')
                                                            ->numeric()
                                                            ->live(onBlur: true)
                                                            ->afterStateUpdated(function ($state, callable $set, callable $get) {
                                                                $followers = (int) $state;
                                                                $tier = \App\Models\MediaPlanKol::calculateTier($followers);
                                                                $set('tier', $tier);
                                                                $er = (float) $get('er_percent');
                                                                $set('engagement', intval($followers * ($er / 100)));
                                                            }),

                                                        TextInput::make('tier')
                                                            ->label('Tier')
                                                            ->readOnly()
                                                            ->dehydrated(),

                                                        TextInput::make('er_percent')
                                                            ->label('ER %')
                                                            ->numeric()
                                                            ->suffix('%')
                                                            ->live(onBlur: true)
                                                            ->afterStateUpdated(function ($state, callable $set, callable $get) {
                                                                $followers = (int) $get('followers');
                                                                $set('engagement', intval($followers * ((float) $state / 100)));
                                                            }),

                                                        TextInput::make('impression')
                                                            ->label('Impression')
                                                            ->numeric(),

                                                        TextInput::make('engagement')
                                                            ->label('Engagement')
                                                            ->numeric()
                                                            ->readOnly()
                                                            ->dehydrated(),

                                                        Select::make('scope_items')
                                                            ->label('Scope of Work')
                                                            ->multiple()
                                                            ->options([
                                                                'IG Post' => 'IG Post',
                                                                'IG Reels' => 'IG Reels',
                                                                'IG Story' => 'IG Story',
                                                                'TikTok Post' => 'TikTok Post',
                                                                'TikTok Video' => 'TikTok Video',
                                                                'TikTok Story' => 'TikTok Story',
                                                                'Threads Post' => 'Threads Post',
                                                                'YouTube Video' => 'YouTube Video',
                                                                'YouTube Shorts' => 'YouTube Shorts',
                                                                'Facebook Post' => 'Facebook Post',
                                                                'Facebook Reels' => 'Facebook Reels',
                                                                'Talent Appearance' => 'Talent Appearance',
                                                                'X Post' => 'X Post',
                                                            ])
                                                            ->searchable()
                                                            ->required()
                                                            ->columnSpan(2),
                                                    ])
                                                    ->columns(3),

                                                // ── Jadwal Bayar ────────────────────────
                                                Section::make('Jadwal Bayar')
                                                    ->schema([
                                                        TextInput::make('after_nego')
                                                            ->label('After Nego')
                                                            ->prefix('Rp')
                                                            ->mask(RawJs::make('$money($input)'))
                                                            ->formatStateUsing(fn($state) => $state ? number_format(round($state), 0, '.', ',') : null)
                                                            ->dehydrateStateUsing(fn($state) => $state ? round(self::parseNumber($state)) : null)
                                                            ->placeholder('0')
                                                            ->nullable(),

                                                        Select::make('payment_date')
                                                            ->label('Jadwal Payment')
                                                            ->options(fn() => \App\Helpers\PaymentScheduleHelper::getUpcomingSchedules())
                                                            ->placeholder('Pilih jadwal')
                                                            ->nullable()
                                                            ->searchable(),
                                                    ])
                                                    ->columns(2),

                                                // ── Select Quotation ────────────────────
                                                Section::make('Select Quotation')
                                                    ->schema([
                                                        Checkbox::make('is_selected')
                                                            ->label('Select for Quotation')
                                                            ->default(false),

                                                        Select::make('status')
                                                            ->label('Status')
                                                            ->options([
                                                                'New List' => 'New List',
                                                                'Approaching' => 'Approaching',
                                                                'Locked' => 'Locked',
                                                                'Canceled' => 'Canceled',
                                                            ])
                                                            ->default('New List'),

                                                        Select::make('pic')
                                                            ->label('PIC')
                                                            ->options([
                                                                'ROHMAH' => 'ROHMAH',
                                                                'NABILLA' => 'NABILLA',
                                                            ]),
                                                    ])
                                                    ->columns(3),

                                                // ── Notes ───────────────────────────────
                                                Section::make('Notes')
                                                    ->schema([
                                                        Textarea::make('notes')
                                                            ->label('Notes')
                                                            ->placeholder('Special instructions or notes')
                                                            ->rows(3)
                                                            ->columnSpanFull(),
                                                    ]),
                                            ])
                                            ->action(function (array $data, callable $set): void {
                                                foreach ($data as $key => $value) {
                                                    $set($key, $value);
                                                }
                                            }),
                                    ])->columnSpanFull(),

                                    Section::make('KOL Information')
                                        ->description(fn(callable $get) => !empty($get('name'))
                                            ? '✅ KOL sudah dipilih. Gunakan tombol "Edit Detail KOL" di atas untuk mengubah data.'
                                            : 'Pilih apakah akan menggunakan KOL yang sudah ada di database atau menambahkan KOL baru')
                                        ->schema([
                                            ToggleButtons::make('kol_source')
                                                ->label('Sumber KOL')
                                                ->options([
                                                    'existing' => 'KOL Existing',
                                                    'new' => 'KOL Baru',
                                                ])
                                                ->icons([
                                                    'existing' => 'heroicon-m-user-group',
                                                    'new' => 'heroicon-m-plus-circle',
                                                ])
                                                ->colors([
                                                    'existing' => 'white',
                                                    'new' => 'white',
                                                ])
                                                ->inline()
                                                ->default('existing')
                                                ->live()
                                                ->dehydrated(false)
                                                ->disabled(fn(callable $get) => !empty($get('name')))
                                                ->afterStateUpdated(function (callable $set) {
                                                    $set('data_kol_id', null);
                                                    $set('channel', null);
                                                    $set('categories', null);
                                                })
                                                ->extraAttributes(['class' => 'kol-source-toggle'])
                                                ->columnSpanFull(),

                                            // === EXISTING KOL FIELDS (only visible when 'existing' selected) ===
                                            Select::make('channel')
                                                ->label('Channel')
                                                ->options([
                                                    'Instagram' => 'Instagram',
                                                    'Tiktok' => 'TikTok',
                                                    'Threads' => 'Threads',
                                                    'Youtube Channels' => 'YouTube Channels',
                                                    'Youtube Shorts' => 'YouTube Shorts',
                                                    'Facebook' => 'Facebook',
                                                    'Talent' => 'Talent',
                                                    'X' => 'X (Twitter)',
                                                ])
                                                ->live(onBlur: true)
                                                ->afterStateUpdated(function (callable $set) {
                                                    $set('categories', null);
                                                    $set('data_kol_id', null);
                                                })
                                                ->required()
                                                ->visible(fn(callable $get) => $get('kol_source') === 'existing')
                                                ->disabled(fn(callable $get) => !empty($get('name')))
                                                ->columnSpan(1),

                                            Select::make('categories')
                                                ->label('Categories')
                                                ->options(function (callable $get) {
                                                    $channel = $get('channel');
                                                    if (!$channel)
                                                        return [];

                                                    return DataKol::where('channel', $channel)
                                                        ->whereNotNull('category')
                                                        ->distinct()
                                                        ->pluck('category', 'category')
                                                        ->toArray();
                                                })
                                                ->live(onBlur: true)
                                                ->afterStateUpdated(fn(callable $set) => $set('data_kol_id', null))
                                                ->searchable()
                                                ->visible(fn(callable $get) => $get('kol_source') === 'existing')
                                                ->disabled(fn(callable $get) => !empty($get('name')))
                                                ->columnSpan(1),

                                            Select::make('data_kol_id')
                                                ->label('Pilih KOL dari Database')
                                                ->options(function (callable $get) {
                                                    $channel = $get('channel');
                                                    $category = $get('categories');

                                                    if (!$channel)
                                                        return [];

                                                    $query = DataKol::where('channel', $channel);

                                                    if ($category) {
                                                        $query->where('category', $category);
                                                    }

                                                    return $query->pluck('username', 'id')->toArray();
                                                })
                                                ->live(onBlur: true)
                                                ->afterStateUpdated(function (?string $state, callable $set, callable $get) {
                                                    if (empty($state))
                                                        return;

                                                    $kol = DataKol::find($state);
                                                    if (!$kol) {
                                                        return;
                                                    }

                                                    // Auto-fill KOL data
                                                    $set('name', $kol->username);
                                                    $set('links', [$kol->link_userprofile]);
                                                    $set('followers', (int) $kol->followers);
                                                    $set('tier', $kol->tier);
                                                    $set('er_percent', (float) $kol->engagement_rate);
                                                    $set('impression', (int) $kol->impressions);
                                                    $set('is_selected', true);

                                                    // Calculate engagement
                                                    $followers = (int) $kol->followers;
                                                    $er = (float) $kol->engagement_rate;
                                                    $engagement = intval($followers * ($er / 100));
                                                    $set('engagement', $engagement);

                                                    Notification::make()
                                                        ->title('✅ KOL berhasil dipilih!')
                                                        ->success()
                                                        ->body("Data @{$kol->username} berhasil diambil dari database.")
                                                        ->send();
                                                })
                                                ->searchable()
                                                ->preload()
                                                ->visible(fn(callable $get) => $get('kol_source') === 'existing')
                                                ->disabled(fn(callable $get) => !empty($get('name')))
                                                ->helperText('Pilih KOL yang sudah tersimpan di database')
                                                ->columnSpan(1),

                                            // === NEW KOL - Action Button (only visible when 'new' selected) ===
                                            Actions::make([
                                                Action::make('create_new_kol')
                                                    ->label('Tambah KOL Baru ke Database')
                                                    ->icon('heroicon-o-user-plus')
                                                    ->size('lg')
                                                    ->slideOver()
                                                    ->color('white')
                                                    ->disabled(fn(callable $get) => !empty($get('name')))
                                                    ->modalWidth('4xl')
                                                    ->modalHeading('Tambah KOL Baru ke Database')
                                                    ->modalDescription('Data KOL akan disimpan ke database dan otomatis terhubung ke Media Plan ini.')
                                                    ->modalIcon('heroicon-o-user-plus')
                                                    ->form([
                                                        Section::make('Social Media Data')
                                                            ->description(new \Illuminate\Support\HtmlString(
                                                                '<span wire:loading.delay.shortest class="inline-flex items-center gap-1.5 text-primary-600 dark:text-primary-400 font-medium text-sm">
                                                                    <svg class="animate-spin h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                                                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                                                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                                                                    </svg>
                                                                    Mengambil data dari API...
                                                                </span>'
                                                            ))
                                                            ->columnSpanFull()
                                                            ->schema([
                                                                Select::make('channel')
                                                                    ->label('Channel')
                                                                    ->options([
                                                                        'Instagram' => 'Instagram',
                                                                        'Tiktok' => 'TikTok',
                                                                        'Threads' => 'Threads',
                                                                        'Youtube Channels' => 'YouTube Channels',
                                                                        'Youtube Shorts' => 'YouTube Shorts',
                                                                        'Facebook' => 'Facebook',
                                                                        'Talent' => 'Talent',
                                                                        'X' => 'X (Twitter)',
                                                                    ])
                                                                    ->live(onBlur: true)
                                                                    ->afterStateUpdated(fn(callable $set) => $set('link_userprofile', null))
                                                                    ->required(),

                                                                TextInput::make('link_userprofile')
                                                                    ->label(fn(callable $get) => match ($get('channel')) {
                                                                        'Instagram' => 'Instagram Profile URL',
                                                                        'Tiktok' => 'TikTok Profile URL',
                                                                        'Youtube Channels' => 'YouTube Channel URL',
                                                                        'Youtube Shorts' => 'YouTube Shorts URL',
                                                                        'Threads' => 'Threads Profile URL',
                                                                        'Facebook' => 'Facebook Profile/Page URL',
                                                                        'Talent' => 'Profil Talent / Portfolio URL',
                                                                        'X' => 'X (Twitter) Profile URL',
                                                                        default => 'Profile URL',
                                                                    })
                                                                    ->placeholder(fn(callable $get) => match ($get('channel')) {
                                                                        'Instagram' => 'https://www.instagram.com/username/',
                                                                        'Tiktok' => 'https://www.tiktok.com/@username',
                                                                        'Youtube Channels' => 'https://www.youtube.com/@username',
                                                                        'Youtube Shorts' => 'https://www.youtube.com/@username',
                                                                        'Threads' => 'https://www.threads.net/@username',
                                                                        'Facebook' => 'https://www.facebook.com/pagename',
                                                                        'Talent' => 'Link portfolio atau profil',
                                                                        'X' => 'https://x.com/username',
                                                                        default => 'Profile URL',
                                                                    })
                                                                    ->helperText(fn(callable $get) => in_array($get('channel'), ['Instagram', 'Tiktok', 'Youtube Channels', 'Youtube Shorts'])
                                                                        ? '📋 Masukkan URL/username, tekan Tab/Enter untuk fetch data otomatis'
                                                                        : '📋 Masukkan URL/link profil channel ini')
                                                                    ->required(fn(callable $get) => !empty($get('channel')))
                                                                    ->live(onBlur: true)
                                                                    ->afterStateUpdated(function (?string $state, callable $set, callable $get) {
                                                                        if (empty($state) || empty($get('channel'))) {
                                                                            return;
                                                                        }

                                                                        $channel = $get('channel');
                                                                        $scrapable = ['Instagram', 'Tiktok', 'Youtube Channels', 'Youtube Shorts'];

                                                                        if (!in_array($channel, $scrapable)) {
                                                                            return;
                                                                        }

                                                                        try {
                                                                            $profile = match ($channel) {
                                                                                'Instagram' => (new InstagramService())->getProfile($state),
                                                                                'Tiktok' => (new TiktokService())->getProfile($state),
                                                                                'Youtube Channels' => (new YoutubeChannelsService())->getProfile($state),
                                                                                'Youtube Shorts' => (new YoutubeShortsService())->getProfile($state),
                                                                                default => null,
                                                                            };

                                                                            if (!$profile) {
                                                                                throw new \Exception('Channel tidak didukung untuk auto-fetch');
                                                                            }

                                                                            // Auto-fill fields
                                                                            $set('username', $profile['username']);
                                                                            $set('followers', $profile['followers_count']);
                                                                            $set('tier', $profile['tier']);
                                                                            $set('engagement_rate', $profile['engagement_rate']);
                                                                            $set('engagements', $profile['total_engagements']);
                                                                            $set('impressions', $profile['average_impressions']);

                                                                            if (!empty($profile['category_name'])) {
                                                                                $set('category', $profile['category_name']);
                                                                            }

                                                                            Notification::make()
                                                                                ->title("✅ Data {$channel} berhasil diambil!")
                                                                                ->success()
                                                                                ->body("Profile @{$profile['username']} dengan " . number_format($profile['followers_count']) . " followers.")
                                                                                ->send();

                                                                        } catch (\Exception $e) {
                                                                            Notification::make()
                                                                                ->title("❌ Gagal mengambil data")
                                                                                ->danger()
                                                                                ->body($e->getMessage())
                                                                                ->send();
                                                                        }
                                                                    }),

                                                                TextInput::make('username')
                                                                    ->label('Username')
                                                                    ->readOnly()
                                                                    ->dehydrated()
                                                                    ->prefixIcon('heroicon-o-at-symbol'),

                                                                TextInput::make('followers')
                                                                    ->label('Followers')
                                                                    ->numeric()
                                                                    ->readOnly()
                                                                    ->dehydrated()
                                                                    ->prefixIcon('heroicon-o-users'),

                                                                TextInput::make('tier')
                                                                    ->label('Tier')
                                                                    ->readOnly()
                                                                    ->dehydrated()
                                                                    ->prefixIcon('heroicon-o-star'),

                                                                TextInput::make('engagement_rate')
                                                                    ->label('Engagement Rate')
                                                                    ->suffix('%')
                                                                    ->numeric()
                                                                    ->readOnly()
                                                                    ->dehydrated()
                                                                    ->prefixIcon('heroicon-o-chart-bar'),

                                                                TextInput::make('engagements')
                                                                    ->label('Total Engagements')
                                                                    ->numeric()
                                                                    ->readOnly()
                                                                    ->dehydrated()
                                                                    ->prefixIcon('heroicon-o-heart'),

                                                                TextInput::make('impressions')
                                                                    ->label('Avg Impressions')
                                                                    ->numeric()
                                                                    ->readOnly()
                                                                    ->dehydrated()
                                                                    ->prefixIcon('heroicon-o-eye'),

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
                                                                    ->label('Category')
                                                                    ->searchable(),

                                                                Select::make('status')
                                                                    ->label('Status')
                                                                    ->options([
                                                                        'New List' => 'New List',
                                                                        'Approaching' => 'Approaching',
                                                                        'Waiting Feedback' => 'Waiting Feedback',
                                                                        'Not Available' => 'Not Available',
                                                                    ])
                                                                    ->default('New List'),
                                                            ])->columns(3),

                                                        Section::make('Additional Info')
                                                            ->columnSpanFull()
                                                            ->schema([
                                                                TextInput::make('contact')
                                                                    ->label('Contact')
                                                                    ->email(),

                                                                DatePicker::make('terakhir_update')
                                                                    ->label('Terakhir Update')
                                                                    ->default(now()),

                                                                TextInput::make('rate_card')
                                                                    ->label('Rate Card')
                                                                    ->prefix('Rp')
                                                                    ->numeric()
                                                                    ->mask(RawJs::make('$money($input)'))
                                                                    ->dehydrateStateUsing(fn($state) => $state ? (float) str_replace(['.', ','], ['', '.'], preg_replace('/[^\d,.]/', '', $state)) : null)
                                                                    ->placeholder('0')
                                                                    ->helperText('Published rate card untuk channel ini'),

                                                                Textarea::make('notes')
                                                                    ->label('Notes')
                                                                    ->rows(3)
                                                                    ->columnSpanFull(),
                                                            ])->columns(3),

                                                    ])
                                                    ->action(function (array $data, callable $set) {
                                                        // Validate required fields
                                                        if (empty($data['username']) || empty($data['channel'])) {
                                                            Notification::make()
                                                                ->danger()
                                                                ->title('Data belum lengkap')
                                                                ->body('Pastikan data sudah ter-fetch dari API sebelum menyimpan.')
                                                                ->send();
                                                            return;
                                                        }

                                                        // Create new KOL
                                                        $kol = DataKol::create([
                                                            'channel' => $data['channel'],
                                                            'link_userprofile' => $data['link_userprofile'],
                                                            'username' => $data['username'],
                                                            'followers' => $data['followers'] ?? 0,
                                                            'tier' => $data['tier'] ?? null,
                                                            'engagement_rate' => $data['engagement_rate'] ?? 0,
                                                            'engagements' => $data['engagements'] ?? 0,
                                                            'impressions' => $data['impressions'] ?? 0,
                                                            'category' => $data['category'] ?? null,
                                                            'status' => $data['status'] ?? 'New List',
                                                            'contact' => $data['contact'] ?? null,
                                                            'terakhir_update' => $data['terakhir_update'] ?? now(),
                                                            'notes' => $data['notes'] ?? null,
                                                            'rate_card' => isset($data['rate_card']) ? (float) str_replace(['.', ','], ['', '.'], preg_replace('/[^\d,.]/', '', $data['rate_card'])) : null,
                                                        ]);

                                                        // Auto-fill KOL data in the parent form
                                                        $set('data_kol_id', $kol->id);
                                                        $set('channel', $kol->channel);
                                                        $set('name', $kol->username);
                                                        $set('links', [$kol->link_userprofile]);
                                                        $set('followers', (int) $kol->followers);
                                                        $set('tier', $kol->tier);
                                                        $set('er_percent', (float) $kol->engagement_rate);
                                                        $set('impression', (int) $kol->impressions);
                                                        $set('is_selected', true);
                                                        $engagement = intval($kol->followers * ($kol->engagement_rate / 100));
                                                        $set('engagement', $engagement);

                                                        Notification::make()
                                                            ->success()
                                                            ->title('✅ KOL berhasil ditambahkan!')
                                                            ->body("@{$kol->username} telah disimpan ke database dan data form telah terisi otomatis.")
                                                            ->send();
                                                    }),
                                            ])
                                                ->visible(fn(callable $get) => $get('kol_source') === 'new')
                                                ->extraAttributes([
                                                    'x-init' => '$nextTick(() => { $el.querySelector("button")?.click() })',
                                                ])
                                                ->columnSpanFull(),
                                        ])->columns(3),

                                    // KOL Details — hidden, state dikelola via slide-over action
                                    KolDetailsRow::make([
                                        Hidden::make('row_number'),

                                        // ── Group 1: DETAIL KOL ──────────────────────────────
                                        Fieldset::make('Detail KOL')
                                            ->schema([
                                                Select::make('channel')
                                                    ->label('Channel')
                                                    ->options([
                                                        'Instagram' => 'Instagram',
                                                        'Tiktok' => 'TikTok',
                                                        'Threads' => 'Threads',
                                                        'Youtube Channels' => 'YouTube Channels',
                                                        'Youtube Shorts' => 'YouTube Shorts',
                                                        'Facebook' => 'Facebook',
                                                        'Talent' => 'Talent',
                                                        'X' => 'X (Twitter)',
                                                    ])
                                                    ->default('Instagram'),

                                                TextInput::make('name')
                                                    ->label('KOL Name')
                                                    ->placeholder('Username / Nama'),

                                                TextInput::make('domisili')
                                                    ->label('Domisili')
                                                    ->placeholder('Jakarta'),

                                                TagsInput::make('links')
                                                    ->label('Links')
                                                    ->placeholder('URL'),

                                                Select::make('tipe_pajak_kol')
                                                    ->label('Golongan Pajak')
                                                    ->options(function () {
                                                        return MasterPph::active()
                                                            ->ordered()
                                                            ->get()
                                                            ->mapWithKeys(function ($pph) {
                                                                $label = $pph->name;
                                                                if ($pph->include_ppn) {
                                                                    $label .= " ({$pph->coefficient} + PPN {$pph->ppn_percent}%)";
                                                                } else {
                                                                    $label .= " ({$pph->coefficient})";
                                                                }
                                                                return [$pph->id => $label];
                                                            })
                                                            ->toArray();
                                                    })
                                                    ->default(fn() => MasterPph::active()->ordered()->first()?->id),
                                            ])
                                            ->columns(5),

                                        // ── Group 2: PERFORMANCE ──────────────────────────────
                                        Fieldset::make('Performance')
                                            ->schema([
                                                TextInput::make('followers')
                                                    ->label('Followers')
                                                    ->numeric()
                                                    ->live(onBlur: true)
                                                    ->afterStateUpdated(function ($state, callable $set, callable $get) {
                                                        $followers = (int) $state;
                                                        $tier = \App\Models\MediaPlanKol::calculateTier($followers);
                                                        $set('tier', $tier);

                                                        $er = (float) $get('er_percent');
                                                        $engagement = intval($followers * ($er / 100));
                                                        $set('engagement', $engagement);
                                                    }),

                                                TextInput::make('tier')
                                                    ->label('Tier')
                                                    ->placeholder('—')
                                                    ->readOnly()
                                                    ->dehydrated(),

                                                TextInput::make('er_percent')
                                                    ->label('ER %')
                                                    ->numeric()
                                                    ->suffix('%')
                                                    ->live(onBlur: true)
                                                    ->afterStateUpdated(function ($state, callable $set, callable $get) {
                                                        $followers = (int) $get('followers');
                                                        $er = (float) $state;
                                                        $engagement = intval($followers * ($er / 100));
                                                        $set('engagement', $engagement);
                                                    }),

                                                TextInput::make('impression')
                                                    ->label('Impression')
                                                    ->numeric()
                                                    ->live(onBlur: true),

                                                TextInput::make('engagement')
                                                    ->label('Engagement')
                                                    ->numeric()
                                                    ->readOnly()
                                                    ->dehydrated(),

                                                TextInput::make('cpi_cpv')
                                                    ->label('CPI/CPV')
                                                    ->prefix('Rp')
                                                    ->mask(RawJs::make('$money($input)'))
                                                    ->formatStateUsing(fn($state) => $state ? number_format(round($state), 0, '.', ',') : '0')
                                                    ->dehydrateStateUsing(fn($state) => round(self::parseNumber($state)))
                                                    ->readOnly(),

                                                TextInput::make('cpe')
                                                    ->label('CPE')
                                                    ->prefix('Rp')
                                                    ->mask(RawJs::make('$money($input)'))
                                                    ->formatStateUsing(fn($state) => $state ? number_format(round($state), 0, '.', ',') : '0')
                                                    ->dehydrateStateUsing(fn($state) => round(self::parseNumber($state)))
                                                    ->readOnly(),

                                                Select::make('scope_items')
                                                    ->label('Scope of Work')
                                                    ->multiple()
                                                    ->options([
                                                        'IG Post' => 'IG Post',
                                                        'IG Reels' => 'IG Reels',
                                                        'IG Story' => 'IG Story',
                                                        'TikTok Post' => 'TikTok Post',
                                                        'TikTok Video' => 'TikTok Video',
                                                        'TikTok Story' => 'TikTok Story',
                                                        'Threads Post' => 'Threads Post',
                                                        'YouTube Video' => 'YouTube Video',
                                                        'YouTube Shorts' => 'YouTube Shorts',
                                                        'Facebook Post' => 'Facebook Post',
                                                        'Facebook Reels' => 'Facebook Reels',
                                                        'Talent Appearance' => 'Talent Appearance',
                                                        'X Post' => 'X Post',
                                                    ])
                                                    ->searchable()
                                                    ->live()
                                                    ->default([])
                                                    ->columnSpan(2)
                                                    ->hintAction(
                                                        Action::make('add_custom_scope')
                                                            ->icon('heroicon-m-plus')
                                                            ->tooltip('Tambah opsi custom')
                                                            ->modalWidth('xs')
                                                            ->modalHeading('Tambah Scope of Work lainnya')
                                                            ->form([
                                                                TextInput::make('custom_scope')
                                                                    ->label('Custom Scope Item')
                                                                    ->placeholder('e.g., TT Live, IG Live, etc.')
                                                                    ->required(),
                                                            ])
                                                            ->action(function (array $data, callable $get, callable $set) {
                                                                $customScope = $data['custom_scope'];
                                                                $currentItems = $get('scope_items') ?? [];
                                                                $currentItems[] = $customScope;
                                                                $set('scope_items', $currentItems);

                                                                Notification::make()
                                                                    ->success()
                                                                    ->title('Custom scope ditambahkan!')
                                                                    ->body("'{$customScope}' berhasil ditambahkan.")
                                                                    ->send();
                                                            })
                                                    ),

                                                TextInput::make('rate')
                                                    ->label('Rate (Budget)')
                                                    ->prefix('Rp')
                                                    ->mask(RawJs::make('$money($input)'))
                                                    ->formatStateUsing(fn($state) => $state ? number_format(round($state), 0, '.', ',') : '0')
                                                    ->dehydrateStateUsing(fn($state) => round(self::parseNumber($state)))
                                                    ->readOnly()
                                                    ->default(0),
                                            ])
                                            ->columns(9),

                                        // ── Group 3: JADWAL BAYAR ─────────────────────────────
                                        Fieldset::make('Jadwal Bayar')
                                            ->schema([
                                                TextInput::make('after_nego')
                                                    ->label('After Nego')
                                                    ->prefix('Rp')
                                                    ->mask(RawJs::make('$money($input)'))
                                                    ->formatStateUsing(fn($state) => $state ? number_format(round($state), 0, '.', ',') : null)
                                                    ->dehydrateStateUsing(fn($state) => $state ? round(self::parseNumber($state)) : null)
                                                    ->placeholder('0')
                                                    ->nullable(),

                                                Select::make('payment_date')
                                                    ->label('Jadwal Payment')
                                                    ->options(fn() => \App\Helpers\PaymentScheduleHelper::getUpcomingSchedules())
                                                    ->placeholder('Pilih jadwal')
                                                    ->nullable()
                                                    ->searchable(),
                                            ])
                                            ->columns(2),

                                        // ── Group 4: SELECT QUOTATION ─────────────────────────
                                        Fieldset::make('Select Quotation')
                                            ->schema([
                                                Checkbox::make('is_selected')
                                                    ->label('Select for Quotation')
                                                    ->default(false)
                                                    ->live(),

                                                Select::make('status')
                                                    ->label('Status')
                                                    ->options([
                                                        'New List' => 'New List',
                                                        'Approaching' => 'Approaching',
                                                        'Locked' => 'Locked',
                                                        'Canceled' => 'Canceled',
                                                    ])
                                                    ->default('New List'),

                                                Select::make('pic')
                                                    ->label('PIC')
                                                    ->options([
                                                        'ROHMAH' => 'ROHMAH',
                                                        'NABILLA' => 'NABILLA',
                                                    ]),
                                            ])
                                            ->columns(3),
                                    ])->extraAttributes(['style' => 'display: none;']),

                                    // Notes — disembunyikan via CSS, bukan ->hidden(),
                                    // agar state tetap ikut dehydrate ke form data.
                                    Section::make('Notes')
                                        ->schema([
                                            Textarea::make('notes')
                                                ->label('Notes')
                                                ->placeholder('Special instructions or notes')
                                                ->rows(2)
                                                ->columnSpanFull(),
                                        ])
                                        ->extraAttributes(['style' => 'display: none;']),
                                ])
                                ->columns(1)
                                ->collapsible()
                                ->collapsed()
                                ->itemLabel(function (array $state): ?string {
                                    $name = $state['name'] ?? 'New KOL';
                                    $channel = $state['channel'] ?? '';
                                    $selected = ($state['is_selected'] ?? false) ? '✅ ' : '';
                                    $rateValue = self::parseNumber($state['rate'] ?? 0);
                                    $rate = $rateValue > 0
                                        ? ' - Rp ' . number_format($rateValue, 0, ',', '.')
                                        : '';
                                    $channelLabel = $channel ? " ({$channel})" : '';
                                    return $selected . $name . $channelLabel . $rate;
                                })
                                ->defaultItems(1)
                                ->addActionLabel('Add Another KOL')
                                ->reorderable()
                                ->columnSpanFull()
                                ->live(),
                        ])
                        ->afterStateUpdated(function (callable $get, callable $set) {
                            $kols = $get('kols') ?? [];
                            $margins = $get('kol_margins') ?? [];
                            $useGlobal = $get('use_global_margin') ?? true;

                            // Always sync name, but only re-init structure if counts mismatch or forced
                            // Simple approach: Rebuild margin array preserving values for existing indices
                
                            $newMargins = [];
                            $defaultMargin = $get('margin_percent') ?? 30;

                            foreach ($kols as $index => $kol) {
                                // Try to preserve existing margin for this index
                                $currentMargin = $margins[$index]['margin'] ?? $defaultMargin;

                                $newMargins[] = [
                                    'name' => $kol['name'] ?? 'New KOL',
                                    'margin' => $currentMargin,
                                ];
                            }

                            $set('kol_margins', $newMargins);
                        }),

                    Step::make('Margin Setting')
                        ->icon('heroicon-m-calculator')
                        ->description('Configure margin settings for this campaign')
                        ->schema([
                            Section::make('🎯 Margin Configuration')
                                ->description('Setting margin akan diaplikasikan ke semua KOL dalam campaign ini saat kalkulasi Internal Budget')
                                ->schema([
                                    ToggleButtons::make('margin_type')
                                        ->label('Margin Type')
                                        ->options([
                                            'auto' => 'Auto (Based on Budget Range)',
                                            'custom' => 'Custom Margin',
                                        ])
                                        ->icons([
                                            'auto' => 'heroicon-m-cpu-chip',
                                            'custom' => 'heroicon-m-pencil-square',
                                        ])
                                        ->colors([
                                            'auto' => 'info',
                                            'custom' => 'warning',
                                        ])
                                        ->inline()
                                        ->default('auto')
                                        ->live()
                                        ->columnSpanFull()
                                        ->helperText('Auto: Margin dihitung otomatis berdasarkan Master Margin. Custom: Anda tentukan sendiri.'),

                                    TextInput::make('margin_percent')
                                        ->label('Custom Margin %')
                                        ->suffix('%')
                                        ->numeric()
                                        ->step('0.01')
                                        ->minValue(0)
                                        ->maxValue(100)
                                        ->default(30)
                                        ->visible(fn(callable $get) => $get('margin_type') === 'custom')
                                        ->required(fn(callable $get) => $get('margin_type') === 'custom')
                                        ->helperText('Contoh: 30 untuk 30%, 40 untuk 40%, dll'),

                                    Toggle::make('use_global_margin')
                                        ->label('Apply to All KOLs')
                                        ->helperText('Jika aktif, margin ini akan diterapkan ke semua KOL. Jika tidak, setiap KOL bisa memiliki margin berbeda di Internal Budget.')
                                        ->inline()
                                        ->default(true)
                                        ->live()
                                        ->afterStateUpdated(function ($state, callable $get, callable $set) {
                                            if (!$state) {
                                                // Sync kols to margins when toggled OFF
                                                $kols = $get('kols') ?? [];
                                                $margins = [];
                                                $default = $get('margin_percent') ?? 30;

                                                foreach ($kols as $kol) {
                                                    $margins[] = [
                                                        'name' => $kol['name'] ?? 'New KOL',
                                                        'margin' => $default,
                                                    ];
                                                }
                                                $set('kol_margins', $margins);
                                            }
                                        })
                                        ->columnSpanFull(),

                                    Repeater::make('kol_margins')
                                        ->label('Custom Margin per KOL')
                                        ->hidden(fn(callable $get) => $get('use_global_margin') === true)
                                        ->schema([
                                            TextInput::make('name')
                                                ->disabled()
                                                ->dehydrated(false)
                                                ->columnSpan(2),
                                            TextInput::make('margin')
                                                ->label('Margin %')
                                                ->numeric()
                                                ->suffix('%')
                                                ->required()
                                                ->maxValue(100)
                                                ->minValue(0)
                                                ->columnSpan(1),
                                        ])
                                        ->addable(false)
                                        ->deletable(false)
                                        ->reorderable(false)
                                        ->columns(3)
                                        ->columnSpanFull(),

                                    // Master Margin Reference removed
                                ])
                                ->columns(2),
                        ]),
                ])
                    ->columnSpanFull()
                    ->skippable()
            ]);
    }

    /**
     * Helper: Get count of selected KOLs
     */
    private static function getSelectedCount(array $kols): string
    {
        $count = collect($kols)->filter(fn($kol) => $kol['is_selected'] ?? false)->count();
        return "{$count} KOL(s) selected";
    }

    /**
     * Helper: Get total rate of selected KOLs
     */
    private static function getTotalRate(array $kols): float
    {
        return collect($kols)
            ->filter(fn($kol) => $kol['is_selected'] ?? false)
            ->sum(fn($kol) => self::parseNumber($kol['rate'] ?? 0));
    }

    /**
     * Helper: Get total impression of selected KOLs
     */
    private static function getTotalImpression(array $kols): int
    {
        return collect($kols)
            ->filter(fn($kol) => $kol['is_selected'] ?? false)
            ->sum(fn($kol) => (int) ($kol['impression'] ?? 0));
    }

    /**
     * Helper: Get total engagement of selected KOLs
     */
    private static function getTotalEngagement(array $kols): int
    {
        return collect($kols)
            ->filter(fn($kol) => $kol['is_selected'] ?? false)
            ->sum(fn($kol) => (int) ($kol['engagement'] ?? 0));
    }
}
