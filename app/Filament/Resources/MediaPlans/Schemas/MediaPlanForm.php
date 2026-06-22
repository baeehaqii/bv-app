<?php

namespace App\Filament\Resources\MediaPlans\Schemas;

use Filament\Schemas\Schema;
use App\Enums\MediaPlanKolStatus;
use App\Models\DataKol;
use App\Models\MasterPph;
use App\Service\InstagramService;
use App\Service\TiktokService;
use App\Service\YoutubeChannelsService;
use App\Service\YoutubeShortsService;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Repeater\TableColumn;
use Filament\Forms\Components\Checkbox;
use Filament\Forms\Components\TagsInput;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Placeholder;
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

    /**
     * Tampilkan PIC KOL. Data baru menyimpan nama PIC KOL (string) langsung.
     * Data legacy bisa menyimpan ID BvSalesList/User — di-resolve ke nama agar
     * tidak tampil sebagai angka.
     */
    private static function resolveKolPicDisplay($state): ?string
    {
        if (blank($state)) {
            return null;
        }

        if (is_numeric($state)) {
            return \App\Models\BvSalesList::find($state)?->nama_sales
                ?? \App\Models\User::find($state)?->name
                ?? (string) $state;
        }

        return (string) $state;
    }

    /**
     * Daftar label SOW dari rate card milik KOL (Database KOL).
     * Dipakai untuk auto-fill & opsi kolom "SOW (Request)" agar selalu valid/tampil.
     *
     * @return array<int, string>
     */
    private static function kolSowLabels(?int $dataKolId): array
    {
        if (! $dataKolId) {
            return [];
        }

        return DataKol::find($dataKolId)
            ?->rateCards()->with('masterSow')->get()
            ->pluck('sow_label')
            ->filter()
            ->unique()
            ->values()
            ->all() ?? [];
    }

    /**
     * Rincian rate per SOW (read-only), tampil rapi seperti spreadsheet:
     * tiap SOW = 1 baris (Item | Rate), ditutup baris Total.
     * Rate card KOL di-load SEKALI lalu dipakai untuk hitung rate + cek masa berlaku (hindari N+1).
     */
    private static function renderSowRateBreakdown(callable $get): \Illuminate\Support\HtmlString
    {
        $scopes = array_values(array_filter((array) $get('scope_items'), fn($s) => filled($s)));

        if (empty($scopes)) {
            return new \Illuminate\Support\HtmlString(
                '<span style="color:#9ca3af;font-size:12px;font-style:italic;">Belum ada SOW dipilih</span>'
            );
        }

        // Ambil seluruh rate card KOL satu kali untuk lookup rate + cek expiry.
        $rateCards = self::resolveKolRateCards($get('data_kol_id'), $get('name'), $get('channel'));

        $total = 0;
        $rows = '';
        $hasExpired = false;
        foreach ($scopes as $scope) {
            $card = $rateCards->first(fn($c) => strcasecmp($c->sow_label, (string) $scope) === 0);
            $rate = (float) ($card?->rate ?? 0);
            $total += $rate;

            $badge = '';
            if ($card && $card->isExpired()) {
                $hasExpired = true;
                $tooltip = self::expiryTooltip($card);
                $badge = ' <span title="' . e($tooltip) . '" style="display:inline-block;margin-left:6px;font-size:11px;font-weight:600;color:#dc2626;background:#fef2f2;border:1px solid #fecaca;border-radius:4px;padding:1px 6px;white-space:nowrap;">&#9888; Perlu update</span>';
            }

            $rows .= '<tr>
                <td style="padding:5px 8px;border:1px solid #e5e7eb;font-size:12px;color:#374151;">' . e($scope) . $badge . '</td>
                <td style="padding:5px 8px;border:1px solid #e5e7eb;font-size:12px;color:#111827;text-align:right;white-space:nowrap;">Rp ' . number_format(round($rate), 0, ',', '.') . '</td>
            </tr>';
        }

        $count = count($scopes);
        $totalFmt = 'Rp ' . number_format(round($total), 0, ',', '.');

        // Banner ringkas bila ada SOW yang sudah lewat masa berlaku.
        $banner = $hasExpired
            ? '<div style="font-size:11px;color:#dc2626;background:#fef2f2;border:1px solid #fecaca;border-radius:4px;padding:6px 8px;margin-bottom:6px;">&#9888; Sebagian rate card sudah lewat masa berlaku &mdash; wajib perbarui data SOW dengan yang terbaru.</div>'
            : '';

        // <details> = collapse native (tanpa JS), default tertutup agar baris tabel tetap ringkas.
        // Ringkasan tetap menampilkan jumlah item + Total walau tertutup.
        return new \Illuminate\Support\HtmlString('
            <details style="font-family:inherit;">
                <summary style="cursor:pointer;font-size:12px;font-weight:600;color:#4f46e5;padding:4px 0;white-space:nowrap;user-select:none;">
                    Rincian Rate (' . $count . ' item) · ' . $totalFmt . ($hasExpired ? ' <span style="color:#dc2626;">&#9888;</span>' : '') . '
                </summary>
                ' . $banner . '
                <table style="border-collapse:collapse;width:100%;background:#fff;margin-top:6px;">
                    <thead>
                        <tr>
                            <th style="padding:5px 8px;border:1px solid #e5e7eb;background:#f9fafb;font-size:10px;text-transform:uppercase;letter-spacing:.04em;color:#6b7280;text-align:left;">Item</th>
                            <th style="padding:5px 8px;border:1px solid #e5e7eb;background:#f9fafb;font-size:10px;text-transform:uppercase;letter-spacing:.04em;color:#6b7280;text-align:right;">Rate</th>
                        </tr>
                    </thead>
                    <tbody>' . $rows . '</tbody>
                    <tfoot>
                        <tr>
                            <td style="padding:5px 8px;border:1px solid #e5e7eb;background:#f9fafb;font-size:12px;font-weight:700;color:#111827;">Total</td>
                            <td style="padding:5px 8px;border:1px solid #e5e7eb;background:#f9fafb;font-size:12px;font-weight:700;color:#111827;text-align:right;white-space:nowrap;">' . $totalFmt . '</td>
                        </tr>
                    </tfoot>
                </table>
            </details>
        ');
    }

    /**
     * Ambil koleksi rate card KOL (eager-load masterSow) untuk lookup rate + expiry.
     * Aman bila data_kol_id null (fallback by username + channel, pola sama computeRateFromSow).
     *
     * @return \Illuminate\Support\Collection<int, \App\Models\KolRateCard>
     */
    private static function resolveKolRateCards($dataKolId, ?string $name, ?string $channel): \Illuminate\Support\Collection
    {
        $dataKol = $dataKolId
            ? DataKol::find($dataKolId)
            : DataKol::where('username', $name)
                ->when($channel, fn($q) => $q->where('channel', $channel))
                ->first();

        return $dataKol
            ? $dataKol->rateCards()->with('masterSow')->get()
            : collect();
    }

    /**
     * Teks tooltip rentang masa berlaku rate card yang sudah lewat.
     */
    private static function expiryTooltip(\App\Models\KolRateCard $card): string
    {
        $from = $card->valid_from?->format('d M Y') ?? '-';
        $until = $card->effective_valid_until?->format('d M Y') ?? '-';
        $days = abs((int) $card->daysUntilExpiry());

        return "Diisi {$from} → berlaku s/d {$until} (lewat {$days} hari)";
    }

    /**
     * Label read-only SOW dari brief client, ditampilkan di tab Select KOL
     * agar tim tidak perlu bolak-balik ke tab Brief.
     * Konten adalah rich-text trusted (pola existing project), dibungkus HtmlString.
     */
    private static function renderBriefSowLabel(?object $record): \Illuminate\Support\HtmlString
    {
        $sow = $record?->bvSales?->formBrief?->sow;

        if (blank($sow)) {
            return new \Illuminate\Support\HtmlString(
                '<span style="color:#9ca3af;font-size:12px;font-style:italic;">Belum ada SOW pada brief</span>'
            );
        }

        return new \Illuminate\Support\HtmlString(
            '<div style="font-size:13px;line-height:1.6;color:#374151;">' . $sow . '</div>'
        );
    }

    /**
     * Hitung total rate otomatis dari rate card per SOW milik KOL.
     * Rate card terbaru (valid_from terakhir) per SOW yang dipakai.
     */
    public static function computeRateFromSow($dataKolId, ?string $name, ?string $channel, array $scopeItems): float
    {
        if (empty($scopeItems)) {
            return 0;
        }

        $dataKol = $dataKolId
            ? DataKol::find($dataKolId)
            : DataKol::where('username', $name)
                ->when($channel, fn($q) => $q->where('channel', $channel))
                ->first();

        if (!$dataKol) {
            return 0;
        }

        $rateCards = $dataKol->rateCards()->with('masterSow')->get();

        return collect($scopeItems)->sum(
            fn($scope) => (float) ($rateCards->first(
                fn($card) => strcasecmp($card->sow_label, (string) $scope) === 0
            )?->rate ?? 0)
        );
    }

    /**
     * Kalkulasi budget 1 baris (Cost / Client Price / Margin) dari rate_base + koef PPh.
     * Dipakai BERSAMA oleh tampilan KOL List & EditMediaPlan::afterSave agar nilai konsisten.
     * Margin: pakai $marginOverride bila diisi, jika null fallback ke MasterMargin (by subtotal).
     *
     * @return array{subtotal:float, mu_pph:float, mu_target:float, rounded:float, actual_margin:float}
     */
    public static function computeBudgetFigures(float $subtotal, float $pphCoeff, ?float $marginOverride): array
    {
        if ($subtotal <= 0 || $pphCoeff <= 0) {
            return ['subtotal' => round($subtotal), 'mu_pph' => 0, 'mu_target' => 0, 'rounded' => 0, 'actual_margin' => 0];
        }

        $muPph = $subtotal / $pphCoeff;

        $margin = $marginOverride !== null
            ? $marginOverride
            : \App\Models\MasterMargin::getMarginForAmount($subtotal);

        $marginDecimal = min(max($margin, 0), 99) / 100;
        $muTarget = $marginDecimal >= 1 ? $muPph : $muPph / (1 - $marginDecimal);
        $rounded = ceil($muTarget / 100000) * 100000;
        $actualMargin = $rounded > 0 ? (($rounded - $muPph) / $rounded) * 100 : 0;

        return [
            'subtotal' => round($subtotal),
            'mu_pph' => round($muPph),
            'mu_target' => round($muTarget),
            'rounded' => round($rounded),
            'actual_margin' => round($actualMargin, 2),
        ];
    }

    /**
     * Total Cost (MU PPh) & Client Price (rounded) untuk 1 KOL (jumlah seluruh SOW-nya),
     * dihitung per-SOW lalu dijumlah — konsisten dengan generate InternalBudgetItem.
     *
     * @return array{cost:float, client:float, margin:?float}
     */
    private static function computeKolTotals(callable $get): array
    {
        $scopes = array_values(array_filter((array) $get('scope_items'), fn($s) => filled($s)));
        if (empty($scopes)) {
            return ['cost' => 0, 'client' => 0, 'margin' => null];
        }

        $rateCards = self::resolveKolRateCards($get('data_kol_id'), $get('name'), $get('channel'));
        $pph = filled($get('tipe_pajak_kol')) ? \App\Models\MasterPph::find($get('tipe_pajak_kol')) : null;
        $coeff = $pph?->getCalculatedCoefficient() ?? 0.975;
        $margin = filled($get('margin_percent')) ? (float) $get('margin_percent') : null;

        $cost = 0;
        $client = 0;
        foreach ($scopes as $scope) {
            $card = $rateCards->first(fn($c) => strcasecmp($c->sow_label, (string) $scope) === 0);
            $rate = (float) ($card?->rate ?? 0);
            $figs = self::computeBudgetFigures($rate, $coeff, $margin);
            $cost += $figs['mu_pph'];
            $client += $figs['rounded'];
        }

        return ['cost' => $cost, 'client' => $client, 'margin' => $margin];
    }

    /**
     * Channel yang didukung — hanya channel dengan service fetch data di sistem.
     */
    private static function kolChannelOptions(): array
    {
        return [
            'Instagram' => 'Instagram',
            'Tiktok' => 'TikTok',
            'Threads' => 'Threads',
            'Youtube Channels' => 'YouTube Channels',
            'Youtube Shorts' => 'YouTube Shorts',
        ];
    }

    private static function kolCategoryOptions(): array
    {
        return [
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
        ];
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
                                        ->required()
                                        ->disabled(fn($record) => (bool) $record?->bvSales)
                                        ->dehydrated(),
                                    DatePicker::make('campaign_period_start')
                                        ->label('Campaign Period Start')
                                        ->native(false)
                                        ->displayFormat('d/m/Y')
                                        ->placeholder('e.g., November 2025')
                                        ->afterStateHydrated(function ($component, $record) {
                                            if ($record?->bvSales?->start_date) {
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
                                            if ($record?->bvSales?->end_date) {
                                                $component->state($record->bvSales->end_date->format('Y-m-d'));
                                            }
                                        })
                                        ->readOnly(fn($record) => (bool) $record?->bvSales),
                                    // TextInput::make('platform')
                                    //     ->label('Platform')
                                    //     ->placeholder('e.g., Social Media'),

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
                                            ->label(function (callable $get, \Livewire\Component $livewire) {
                                                $record = $livewire->record ?? null;
                                                $client = $record?->bvSales?->client
                                                    ?? \App\Models\DataClient::where('nama_brand', $get('brand'))->first();
                                                if (!$client)
                                                    return 'Lihat PIC Client';
                                                $count = count($client->pic_clients ?? []);
                                                return "Lihat PIC Client ({$count})";
                                            })
                                            ->icon('heroicon-o-users')
                                            ->color('white')
                                            ->modalWidth('5xl')
                                            ->modalHeading('Daftar PIC Client')
                                            ->modalContent(function (callable $get, \Livewire\Component $livewire) {
                                                $record = $livewire->record ?? null;
                                                $client = $record?->bvSales?->client
                                                    ?? \App\Models\DataClient::where('nama_brand', $get('brand'))->first();
                                                if (!$client) {
                                                    return new \Illuminate\Support\HtmlString('<p style="color:#6b7280;padding:16px;">Brand belum dipilih atau tidak ditemukan.</p>');
                                                }
                                                $pics = $client->pic_clients ?? [];
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
                                                    '<div style="overflow-x:auto;-webkit-overflow-scrolling:touch;">
                                                        <table style="width:100%;min-width:640px;border-collapse:collapse;font-family:sans-serif;">
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
                                ])->columns(2),

                            Section::make('PIC Campaign')
                                ->description('Penanggung jawab campaign per role')
                                ->icon('heroicon-o-user-group')
                                ->schema([
                                    Select::make('pic_sales_bd_id')
                                        ->label('Sales / BD')
                                        ->options(\App\Models\BvSalesList::pluck('nama_sales', 'id'))
                                        ->searchable()
                                        ->preload()
                                        ->nullable()
                                        ->helperText('Sales atau BD yang menangani campaign ini'),

                                    Select::make('pic_leads_project_id')
                                        ->label('Lead Project (Manager)')
                                        ->options(\App\Models\BvSalesList::pluck('nama_sales', 'id'))
                                        ->searchable()
                                        ->preload()
                                        ->nullable()
                                        ->helperText('Manager yang memimpin project'),

                                    Select::make('pic_project_internal_ids')
                                        ->label('Project Internal (KOL Specialist)')
                                        ->options(
                                            fn() => \App\Models\BvEmploye::whereHas(
                                                'position.department.division',
                                                fn($q) => $q->where('slug', 'creative')
                                            )
                                                ->orderBy('nama_lengkap')
                                                ->pluck('nama_lengkap', 'id')
                                        )
                                        ->multiple()
                                        ->searchable()
                                        ->preload()
                                        ->nullable()
                                        ->helperText('Karyawan divisi Creative/KOL — bisa lebih dari 1'),

                                    Select::make('pic_am_id')
                                        ->label('Account Management (AM)')
                                        ->options(\App\Models\BvSalesList::pluck('nama_sales', 'id'))
                                        ->searchable()
                                        ->preload()
                                        ->nullable()
                                        ->helperText('AM yang berkoordinasi dengan client'),
                                ])->columns(2),

                            Section::make('Quotation Bertanda Tangan')
                                ->description('Upload quotation yang sudah ditandatangani client sebelum campaign bisa live')
                                ->icon('heroicon-o-document-check')
                                ->schema([
                                    FileUpload::make('quotation_signed_path')
                                        ->label('Upload Quotation Signed')
                                        ->directory('quotation-signed')
                                        ->disk('public')
                                        ->acceptedFileTypes([
                                            'application/pdf',
                                            'image/png',
                                            'image/jpeg',
                                        ])
                                        ->maxSize(10240)
                                        ->downloadable()
                                        ->openable()
                                        ->helperText('Format: PDF, JPG, PNG (maks. 10 MB). Wajib diisi sebelum status bisa berubah ke Ongoing.')
                                        ->afterStateUpdated(function ($state, callable $set) {
                                            if ($state) {
                                                $set('quotation_signed_at', now()->toDateTimeString());
                                            } else {
                                                $set('quotation_signed_at', null);
                                            }
                                        })
                                        ->live()
                                        ->columnSpanFull(),

                                    \Filament\Forms\Components\Hidden::make('quotation_signed_at'),

                                    \Filament\Schemas\Components\Grid::make(2)->schema([
                                        \Filament\Forms\Components\Placeholder::make('quotation_signed_status')
                                            ->label('Status Quotation Signed')
                                            ->content(function ($record) {
                                                if (!$record?->quotation_signed_path) {
                                                    return new \Illuminate\Support\HtmlString(
                                                        '<span style="color:#dc2626;font-size:13px;">⚠ Belum diupload — campaign tidak bisa Ongoing</span>'
                                                    );
                                                }
                                                $date = $record->quotation_signed_at?->format('d M Y H:i') ?? '-';
                                                return new \Illuminate\Support\HtmlString(
                                                    '<span style="color:#16a34a;font-size:13px;">✓ Sudah diupload pada ' . e($date) . '</span>'
                                                );
                                            })
                                            ->hidden(fn(string $operation) => $operation === 'create'),
                                    ]),
                                ]),
                        ]),

                    Step::make('Brief')
                        ->icon('heroicon-m-document-text')
                        ->description('Lihat brief & lampiran dari client')
                        ->schema([
                            ViewField::make('brief_view')
                                ->view('filament.forms.components.media-plan-brief')
                                ->dehydrated(false)
                                ->columnSpanFull(),
                        ]),

                    Step::make('Select KOL')
                        ->icon('heroicon-m-user-group')
                        ->description('Choose or search for multiple KOLs')
                        ->schema([
                            // Summary List KOL — dinonaktifkan sementara atas permintaan client
                            // Section::make('📊 Summary List KOL')
                            //     ->description('Ringkasan otomatis dari KOL yang dicentang')
                            //     ->schema([
                            //         Grid::make(4)
                            //             ->schema([
                            //                 Placeholder::make('selected_count_display')
                            //                     ->label('Selected KOLs')
                            //                     ->extraAttributes(['class' => 'text-primary-600 dark:text-primary-400'])
                            //                     ->content(fn(callable $get) => self::getSelectedCount($get('kols') ?? [])),
                            //                 Placeholder::make('total_rate_display')
                            //                     ->label('Total Rate')
                            //                     ->extraAttributes(['class' => 'text-primary-600 dark:text-primary-400'])
                            //                     ->content(fn(callable $get) => 'Rp ' . number_format(self::getTotalRate($get('kols') ?? []), 0, ',', '.')),
                            //                 Placeholder::make('total_impression_display')
                            //                     ->label('Total Est. Views')
                            //                     ->extraAttributes(['class' => 'text-primary-600 dark:text-primary-400'])
                            //                     ->content(fn(callable $get) => number_format(self::getTotalImpression($get('kols') ?? []), 0, ',', '.')),
                            //                 Placeholder::make('total_engagement_display')
                            //                     ->label('Total Est. Engagement')
                            //                     ->extraAttributes(['class' => 'text-primary-600 dark:text-primary-400'])
                            //                     ->content(fn(callable $get) => number_format(self::getTotalEngagement($get('kols') ?? []), 0, ',', '.')),
                            //             ]),
                            //     ])
                            //     ->collapsible()
                            //     ->collapsed(),

                            Placeholder::make('kol_table_css')
                                ->hiddenLabel()
                                ->content(new \Illuminate\Support\HtmlString('<style>
                                    #kol-list-repeater { overflow-x: auto; padding-bottom: 340px; margin-bottom: -340px; }
                                    #kol-list-repeater table { min-width: 2920px; }
                                </style>'))
                                ->columnSpanFull(),

                            Section::make('Scope of Work (dari Brief)')
                                ->icon('heroicon-m-clipboard-document-list')
                                ->description('Referensi SOW dari brief client, tanpa perlu pindah ke tab Brief')
                                ->collapsible()
                                ->collapsed()
                                ->schema([
                                    Placeholder::make('brief_sow_label')
                                        ->hiddenLabel()
                                        ->content(fn(?\App\Models\MediaPlan $record) => self::renderBriefSowLabel($record)),
                                ])
                                ->columnSpanFull(),

                            Repeater::make('kols')
                                ->label('KOL List')
                                ->extraItemActions([
                                    Action::make('kol_overview')
                                        ->label('Lihat Overview KOL')
                                        ->tooltip('Lihat Overview KOL')
                                        ->icon('heroicon-o-eye')
                                        ->color('info')
                                        ->slideOver()
                                        ->modalWidth('5xl')
                                        ->modalHeading('Overview KOL')
                                        ->modalSubmitAction(false)
                                        ->modalCancelActionLabel('Tutup')
                                        ->visible(fn(array $arguments, Repeater $component): bool => !empty(($component->getRawItemState($arguments['item'])['name'] ?? null)))
                                        ->modalContent(function (array $arguments, Repeater $component): \Illuminate\Contracts\View\View {
                                            $item = $component->getRawItemState($arguments['item']);

                                            // Query langsung ke DB agar status approval dari Media Plan External akurat
                                            // (tidak terpengaruh default 'pending' dari Hidden field di form state)
                                            $livewire = $component->getLivewire();
                                            $mediaPlan = $livewire->record;
                                            $kolName = $item['name'] ?? null;
                                            $mediaPlanKolId = $item['id'] ?? null;

                                            $kolBudgetItems = collect();
                                            if ($mediaPlan?->internalBudget) {
                                                $query = $mediaPlan->internalBudget
                                                    ->items()
                                                    ->with('mediaPlanKol')
                                                    ->orderBy('sort_order');

                                                // Filter by media_plan_kol_id jika tersedia, fallback ke kol_name
                                                if ($mediaPlanKolId) {
                                                    $query->where('media_plan_kol_id', $mediaPlanKolId);
                                                } elseif ($kolName) {
                                                    $query->whereHas('mediaPlanKol', fn($q) => $q->where('name', $kolName));
                                                }

                                                $kolBudgetItems = $query->get()->map(fn($bi) => [
                                                    'scope_item' => $bi->scope_item,
                                                    'qty' => $bi->qty,
                                                    'rate_base' => $bi->rate_base,
                                                    'mu_pph' => $bi->mu_pph ? number_format(round($bi->mu_pph), 0, '.', ',') : null,
                                                    'rounded' => $bi->rounded ? number_format(round($bi->rounded), 0, '.', ',') : null,
                                                    'actual_margin_percent' => $bi->actual_margin_percent,
                                                    'status' => $bi->status ?? 'pending',
                                                    'rejection_notes' => $bi->rejection_notes,
                                                ]);
                                            }

                                            return view('filament.actions.kol-overview-modal', array_merge(
                                                self::buildKolOverviewData($item),
                                                ['budget_items' => $kolBudgetItems],
                                            ));
                                        }),

                                    Action::make('edit_kol_details')
                                        ->label('Edit Detail KOL')
                                        ->tooltip('Edit Detail KOL')
                                        ->icon('heroicon-o-pencil-square')
                                        ->color('warning')
                                        ->slideOver()
                                        ->modalWidth('5xl')
                                        ->visible(fn(array $arguments, Repeater $component): bool => !empty(($component->getRawItemState($arguments['item'])['name'] ?? null)))
                                        ->modalHeading(function (array $arguments, Repeater $component): string {
                                            $item = $component->getRawItemState($arguments['item']);
                                            return 'Edit KOL: ' . ($item['name'] ?? 'New KOL');
                                        })
                                        ->fillForm(function (array $arguments, Repeater $component): array {
                                            $item = $component->getRawItemState($arguments['item']);
                                            return [
                                                'channel' => $item['channel'] ?? null,
                                                'name' => $item['name'] ?? null,
                                                'domisili' => $item['domisili'] ?? null,
                                                'links' => $item['links'] ?? null,
                                                'tipe_pajak_kol' => $item['tipe_pajak_kol'] ?? null,
                                                'followers' => filled($item['followers'] ?? null) ? (int) self::parseNumber($item['followers']) : null,
                                                'tier' => $item['tier'] ?? null,
                                                'er_percent' => $item['er_percent'] ?? null,
                                                'impression' => filled($item['impression'] ?? null) ? (int) self::parseNumber($item['impression']) : null,
                                                'engagement' => filled($item['engagement'] ?? null) ? (int) self::parseNumber($item['engagement']) : null,
                                                'scope_items' => $item['scope_items'] ?? [],
                                                'after_nego' => $item['after_nego'] ?? null,
                                                'payment_date' => $item['payment_date'] ?? null,
                                                'is_selected' => $item['is_selected'] ?? false,
                                                'status' => $item['status'] ?? 'New List',
                                                'pic' => $item['pic'] ?? null,
                                                'notes' => $item['notes'] ?? null,
                                            ];
                                        })
                                        ->form([
                                            // ── Detail KOL ──────────────────────────
                                            Section::make('Detail KOL')
                                                ->schema([
                                                    Select::make('channel')
                                                        ->label('Channel')
                                                        ->options(self::kolChannelOptions())
                                                        ->required(),

                                                    TextInput::make('name')
                                                        ->label('KOL Name')
                                                        ->placeholder('Username / Nama')
                                                        ->required(),

                                                    TextInput::make('domisili')
                                                        ->label('Domisili')
                                                        ->placeholder('Jakarta'),

                                                    TextInput::make('links')
                                                        ->label('Links')
                                                        ->placeholder('URL')
                                                        ->formatStateUsing(fn($state) => is_array($state) ? implode(', ', array_filter($state)) : $state)
                                                        ->dehydrateStateUsing(fn($state) => is_array($state)
                                                            ? array_values(array_filter($state))
                                                            : array_values(array_filter(array_map('trim', explode(',', (string) $state))))),

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
                                                        ->options(MediaPlanKolStatus::toArray())
                                                        ->searchable()
                                                        ->native(false)
                                                        ->default('New List'),

                                                    TextInput::make('pic')
                                                        ->label('PIC KOL')
                                                        ->placeholder('Otomatis dari PIC KOL')
                                                        ->formatStateUsing(fn($state) => self::resolveKolPicDisplay($state))
                                                        ->nullable(),
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
                                        ->action(function (array $data, array $arguments, Repeater $component): void {
                                            $item = $component->getRawItemState($arguments['item']);
                                            $data['rate'] = round(self::computeRateFromSow(
                                                $item['data_kol_id'] ?? null,
                                                $data['name'] ?? null,
                                                $data['channel'] ?? null,
                                                (array) ($data['scope_items'] ?? [])
                                            ));
                                            $component->getChildSchema($arguments['item'])->fill($data);
                                        }),
                                ])
                                ->table([
                                    TableColumn::make('PIC')->width('220px'),
                                    TableColumn::make('Status')->width('150px'),
                                    TableColumn::make('Username')->width('380px'),
                                    TableColumn::make('Link')->width('260px'),
                                    TableColumn::make('Channel')->width('150px'),
                                    TableColumn::make('Followers')->width('110px'),
                                    TableColumn::make('Tier')->width('90px'),
                                    TableColumn::make('ER %')->width('150px'),
                                    TableColumn::make('Avg Views')->width('110px'),
                                    TableColumn::make('Engagement')->width('110px'),
                                    TableColumn::make('SOW (Request)')->width('320px'),
                                    TableColumn::make('Rate per SOW')->width('300px'),
                                    TableColumn::make('Margin %')->width('120px'),
                                    TableColumn::make('Cost (MU PPh)')->width('150px'),
                                    TableColumn::make('Client Price')->width('150px'),
                                    TableColumn::make('Quotation')->width('90px'),
                                ])
                                ->schema([
                                    Hidden::make('row_number'),
                                    Hidden::make('data_kol_id'),
                                    Hidden::make('domisili'),
                                    Hidden::make('tipe_pajak_kol')
                                        ->default(fn() => MasterPph::active()->ordered()->first()?->id),
                                    Hidden::make('cpi_cpv'),
                                    Hidden::make('cpe'),
                                    Hidden::make('after_nego'),
                                    Hidden::make('payment_date'),
                                    Hidden::make('notes'),

                                    TextInput::make('pic')
                                        ->label('PIC KOL')
                                        ->placeholder('Otomatis dari PIC KOL')
                                        ->formatStateUsing(fn($state) => self::resolveKolPicDisplay($state))
                                        ->nullable(),

                                    Select::make('status')
                                        ->label('Status')
                                        ->options(MediaPlanKolStatus::toArray())
                                        ->searchable()
                                        ->native(false)
                                        ->default('New List'),

                                    Select::make('name')
                                        ->label('Username')
                                        ->placeholder('Cari KOL...')
                                        ->searchable()
                                        ->getSearchResultsUsing(
                                            fn(string $search) => DataKol::where('username', 'like', "%{$search}%")
                                                ->limit(50)
                                                ->get()
                                                ->mapWithKeys(fn($kol) => [$kol->username => "{$kol->username} ({$kol->channel})"])
                                                ->toArray()
                                        )
                                        ->getOptionLabelUsing(fn($value) => $value)
                                        ->live()
                                        ->afterStateUpdated(function (?string $state, callable $set, callable $get) {
                                            if (empty($state)) {
                                                return;
                                            }

                                            $kol = DataKol::where('username', $state)->first();
                                            if (!$kol) {
                                                return;
                                            }

                                            $set('data_kol_id', $kol->id);
                                            // PIC = PIC KOL (Nama Lengkap PIC dari Database KOL), bukan PIC BD/sales
                                            $set('pic', $kol->full_name ?: null);
                                            $set('channel', $kol->channel);
                                            $set('links', $kol->link_userprofile);
                                            $set('followers', number_format((int) $kol->followers, 0, '.', ','));
                                            $set('tier', $kol->tier);
                                            $set('er_percent', (float) $kol->engagement_rate);
                                            $set('impression', number_format((int) $kol->impressions, 0, '.', ','));
                                            $set('engagement', number_format(intval($kol->followers * ($kol->engagement_rate / 100)), 0, '.', ','));
                                            // Tax: ambil golongan pajak dari detail KOL agar otomatis di budget items
                                            if ($kol->tipe_pajak_kol) {
                                                $set('tipe_pajak_kol', $kol->tipe_pajak_kol);
                                            }
                                            // SOW (Request) otomatis dari rate card KOL (Database KOL)
                                            $kolSowLabels = self::kolSowLabels($kol->id);
                                            if (! empty($kolSowLabels)) {
                                                $set('scope_items', $kolSowLabels);
                                            }
                                            $set('rate', number_format(round(self::computeRateFromSow(
                                                $kol->id,
                                                $kol->username,
                                                $kol->channel,
                                                ! empty($kolSowLabels) ? $kolSowLabels : (array) $get('scope_items')
                                            )), 0, '.', ','));
                                        })
                                        ->suffixAction(
                                            Action::make('tambah_kol')
                                                ->icon('heroicon-m-plus')
                                                ->tooltip('Tambah KOL')
                                                ->modalHeading('Tambah KOL')
                                                ->modalDescription('Pilih sumber KOL untuk baris ini.')
                                                ->modalWidth('md')
                                                ->modalSubmitAction(false)
                                                ->modalCancelActionLabel('Batal')
                                                ->extraModalFooterActions([
                                                    Action::make('dari_database')
                                                        ->label('KOL dari Database')
                                                        ->icon('heroicon-o-user-group')
                                                        ->color('white')
                                                        ->cancelParentActions()
                                                        ->action(function () {
                                                            Notification::make()
                                                                ->info()
                                                                ->title('Cari KOL di kolom Username')
                                                                ->body('Ketik di kolom Username untuk mencari KOL dari database — data akan terisi otomatis.')
                                                                ->send();
                                                        }),

                                                Action::make('create_new_kol')
                                                    ->label('KOL Baru')
                                                    ->icon('heroicon-o-user-plus')
                                                    ->size('lg')
                                                    ->slideOver()
                                                    ->color('white')
                                                    ->modalWidth('4xl')
                                                    ->modalHeading('Tambah KOL Baru ke Database')
                                                    ->modalDescription('Data KOL akan disimpan ke database dan otomatis terhubung ke Media Plan ini.')
                                                    ->modalIcon('heroicon-o-user-plus')
                                                    ->cancelParentActions()
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
                                                                    ->options(self::kolChannelOptions())
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
                                                                                $set('category', [$profile['category_name']]);
                                                                            }

                                                                            // Auto-fill contact fields
                                                                            if (!empty($profile['business_email'])) {
                                                                                $set('email', $profile['business_email']);
                                                                                $set('contact', $profile['business_email']);
                                                                            }
                                                                            if (!empty($profile['business_phone_number'])) {
                                                                                $set('wa_number', $profile['business_phone_number']);
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
                                                                    ->options(function (callable $get) {
                                                                        $options = self::kolCategoryOptions();

                                                                        // Sertakan kategori hasil fetch API agar lolos validasi & tersimpan
                                                                        foreach (array_filter((array) $get('category')) as $cat) {
                                                                            $options[$cat] = $cat;
                                                                        }

                                                                        return $options;
                                                                    })
                                                                    ->multiple()
                                                                    ->label('Category')
                                                                    ->searchable(),
                                                            ])->columns(3),

                                                        Section::make('Additional Info')
                                                            ->columnSpanFull()
                                                            ->schema([
                                                                TextInput::make('full_name')
                                                                    ->label('Nama PIC KOL')
                                                                    ->placeholder('Nama PIC / penanggung jawab KOL')
                                                                    ->prefixIcon('heroicon-o-user'),

                                                                TextInput::make('email')
                                                                    ->label('Email')
                                                                    ->email()
                                                                    ->placeholder('email@example.com')
                                                                    ->prefixIcon('heroicon-o-envelope'),

                                                                TextInput::make('wa_number')
                                                                    ->label('No WhatsApp')
                                                                    ->tel()
                                                                    ->placeholder('08xxxxxxxxxx')
                                                                    ->prefixIcon('heroicon-o-phone'),

                                                                TextInput::make('contact')
                                                                    ->label('Contact (Legacy)')
                                                                    ->helperText('Otomatis terisi dari API')
                                                                    ->disabled()
                                                                    ->dehydrated()
                                                                    ->visible(fn($state) => !empty($state)),

                                                                DatePicker::make('terakhir_update')
                                                                    ->label('Terakhir Update')
                                                                    ->default(now()),

                                                                Textarea::make('notes')
                                                                    ->label('Notes')
                                                                    ->rows(3)
                                                                    ->columnSpanFull(),
                                                            ])->columns(3),

                                                        Section::make('Rate Card KOL')
                                                            ->description('Input rate card untuk setiap SOW.')
                                                            ->columnSpanFull()
                                                            ->schema([
                                                                Repeater::make('rate_cards')
                                                                    ->label('Rate cards')
                                                                    ->schema([
                                                                        Select::make('channel')
                                                                            ->label('Channel')
                                                                            ->options(self::kolChannelOptions())
                                                                            ->live()
                                                                            ->afterStateUpdated(fn(callable $set) => $set('master_sow_id', null))
                                                                            ->required(),

                                                                        Select::make('master_sow_id')
                                                                            ->label('SOW')
                                                                            ->options(function (callable $get) {
                                                                                $channel = $get('channel');
                                                                                $query = \App\Models\MasterSow::active()->ordered();
                                                                                if ($channel) {
                                                                                    $query->byChannel($channel);
                                                                                }
                                                                                return $query->get()
                                                                                    ->mapWithKeys(fn($sow) => [
                                                                                        $sow->id => $sow->channel
                                                                                            ? "{$sow->name} ({$sow->channel})"
                                                                                            : $sow->name,
                                                                                    ]);
                                                                            })
                                                                            ->searchable()
                                                                            ->placeholder('Pilih SOW')
                                                                            ->live()
                                                                            ->afterStateUpdated(function ($state, callable $set) {
                                                                                $sow = \App\Models\MasterSow::find($state);
                                                                                if ($sow && !$sow->is_custom) {
                                                                                    $set('custom_sow_name', null);
                                                                                }
                                                                            })
                                                                            ->nullable(),

                                                                        TextInput::make('custom_sow_name')
                                                                            ->label('SOW Custom (Tulis Manual)')
                                                                            ->placeholder('e.g. IG Collab Post + Story')
                                                                            ->visible(function (callable $get) {
                                                                                $sowId = $get('master_sow_id');
                                                                                if (!$sowId) {
                                                                                    return false;
                                                                                }
                                                                                $sow = \App\Models\MasterSow::find($sowId);
                                                                                return $sow?->is_custom === true;
                                                                            })
                                                                            ->nullable(),

                                                                        TextInput::make('rate')
                                                                            ->label('Rate Card')
                                                                            ->prefix('Rp')
                                                                            ->mask(RawJs::make('$money($input)'))
                                                                            ->dehydrateStateUsing(fn($state) => $state !== null && $state !== '' ? (int) preg_replace('/[^\d]/', '', $state) : null)
                                                                            ->formatStateUsing(fn($state) => $state ? number_format((float) $state, 0, '.', ',') : null)
                                                                            ->placeholder('0'),

                                                                        DatePicker::make('valid_from')
                                                                            ->label('Berlaku Dari')
                                                                            ->default(now()),

                                                                        DatePicker::make('valid_until')
                                                                            ->label('Berlaku Sampai (opsional)')
                                                                            ->helperText('Kosongkan = otomatis berlaku 90 hari sejak Berlaku Dari')
                                                                            ->minDate(fn(callable $get) => $get('valid_from'))
                                                                            ->rule('after_or_equal:valid_from')
                                                                            ->nullable(),

                                                                        Textarea::make('notes')
                                                                            ->label('Catatan')
                                                                            ->rows(2)
                                                                            ->placeholder('Catatan tambahan...'),
                                                                    ])
                                                                    ->table([
                                                                        TableColumn::make('Channel'),
                                                                        TableColumn::make('SOW'),
                                                                        TableColumn::make('SOW Custom'),
                                                                        TableColumn::make('Rate Card'),
                                                                        TableColumn::make('Berlaku Dari'),
                                                                        TableColumn::make('Berlaku Sampai'),
                                                                        TableColumn::make('Catatan'),
                                                                    ])
                                                                    ->extraItemActions([
                                                                        Action::make('upload_file')
                                                                            ->icon('heroicon-o-paper-clip')
                                                                            ->label('')
                                                                            ->tooltip('Upload File Rate Card')
                                                                            ->modalHeading('Upload File Rate Card')
                                                                            ->form([
                                                                                FileUpload::make('file_path')
                                                                                    ->label('File Rate Card')
                                                                                    ->helperText('PDF, JPG, PNG, JPEG — maks. 2MB')
                                                                                    ->acceptedFileTypes(['application/pdf', 'image/jpeg', 'image/png'])
                                                                                    ->maxSize(2048)
                                                                                    ->directory('kol-rate-cards')
                                                                                    ->downloadable()
                                                                                    ->openable(),
                                                                            ])
                                                                            ->fillForm(function (array $arguments, Repeater $component): array {
                                                                                return [
                                                                                    'file_path' => $component->getState()[$arguments['item']]['file_path'] ?? null,
                                                                                ];
                                                                            })
                                                                            ->action(function (array $arguments, array $data, Repeater $component): void {
                                                                                $items = $component->getState();
                                                                                $items[$arguments['item']]['file_path'] = $data['file_path'];
                                                                                $component->state($items);
                                                                            }),
                                                                    ])
                                                                    ->addActionLabel('+ Tambah Rate Card')
                                                                    ->defaultItems(0)
                                                                    ->reorderable(false),
                                                            ]),

                                                    ])
                                                    ->action(function (array $data, callable $get, callable $set) {
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
                                                            'status' => 'New List',
                                                            'full_name' => $data['full_name'] ?? null,
                                                            'email' => $data['email'] ?? null,
                                                            'wa_number' => $data['wa_number'] ?? null,
                                                            'contact' => $data['contact'] ?? $data['email'] ?? null,
                                                            'terakhir_update' => $data['terakhir_update'] ?? now(),
                                                            'notes' => $data['notes'] ?? null,
                                                        ]);

                                                        // Simpan rate card per SOW
                                                        $rateCards = collect($data['rate_cards'] ?? [])
                                                            ->filter(fn($rc) => !empty($rc['channel']))
                                                            ->map(fn($rc) => [
                                                                'channel' => $rc['channel'],
                                                                'master_sow_id' => $rc['master_sow_id'] ?? null,
                                                                'custom_sow_name' => $rc['custom_sow_name'] ?? null,
                                                                'rate' => $rc['rate'] ?? null,
                                                                'file_path' => $rc['file_path'] ?? null,
                                                                'valid_from' => $rc['valid_from'] ?? null,
                                                                'valid_until' => $rc['valid_until'] ?? null,
                                                                'notes' => $rc['notes'] ?? null,
                                                            ])
                                                            ->values()
                                                            ->all();

                                                        if ($rateCards) {
                                                            $kol->rateCards()->createMany($rateCards);
                                                        }

                                                        // Isi baris ini dengan data KOL yang baru dibuat
                                                        $set('data_kol_id', $kol->id);
                                                        $set('pic', $kol->full_name ?: null);
                                                        $set('name', $kol->username);
                                                        $set('channel', $kol->channel);
                                                        $set('links', $kol->link_userprofile);
                                                        $set('followers', number_format((int) $kol->followers, 0, '.', ','));
                                                        $set('tier', $kol->tier);
                                                        $set('er_percent', (float) $kol->engagement_rate);
                                                        $set('impression', number_format((int) $kol->impressions, 0, '.', ','));
                                                        $set('engagement', number_format(intval($kol->followers * ($kol->engagement_rate / 100)), 0, '.', ','));

                                                        Notification::make()
                                                            ->success()
                                                            ->title('✅ KOL berhasil ditambahkan!')
                                                            ->body("@{$kol->username} telah disimpan ke database dan baris ini terisi otomatis.")
                                                            ->send();
                                                    }),
                                                ])
                                        ),

                                    TextInput::make('links')
                                        ->label('Link')
                                        ->placeholder('URL')
                                        ->formatStateUsing(fn($state) => is_array($state) ? implode(', ', array_filter($state)) : $state)
                                        ->dehydrateStateUsing(fn($state) => is_array($state)
                                            ? array_values(array_filter($state))
                                            : array_values(array_filter(array_map('trim', explode(',', (string) $state))))),

                                    Select::make('channel')
                                        ->label('Channel')
                                        ->options(self::kolChannelOptions())
                                        ->default('Instagram'),

                                    TextInput::make('followers')
                                        ->label('Followers')
                                        ->mask(RawJs::make('$money($input)'))
                                        ->formatStateUsing(fn($state) => $state !== null && $state !== '' ? number_format((int) self::parseNumber($state), 0, '.', ',') : null)
                                        ->dehydrateStateUsing(fn($state) => (int) self::parseNumber($state))
                                        ->live(onBlur: true)
                                        ->afterStateUpdated(function ($state, callable $set, callable $get) {
                                            $followers = (int) self::parseNumber($state);
                                            $set('tier', \App\Models\MediaPlanKol::calculateTier($followers));
                                            $set('engagement', number_format(intval($followers * (((float) self::parseNumber($get('er_percent'))) / 100)), 0, '.', ','));
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
                                            $followers = (int) self::parseNumber($get('followers'));
                                            $set('engagement', number_format(intval($followers * (((float) $state) / 100)), 0, '.', ','));
                                        }),

                                    TextInput::make('impression')
                                        ->label('Avg Views')
                                        ->mask(RawJs::make('$money($input)'))
                                        ->formatStateUsing(fn($state) => $state !== null && $state !== '' ? number_format((int) self::parseNumber($state), 0, '.', ',') : null)
                                        ->dehydrateStateUsing(fn($state) => (int) self::parseNumber($state))
                                        ->live(onBlur: true),

                                    TextInput::make('engagement')
                                        ->label('Engagement')
                                        ->formatStateUsing(fn($state) => $state !== null && $state !== '' ? number_format((int) self::parseNumber($state), 0, '.', ',') : null)
                                        ->dehydrateStateUsing(fn($state) => (int) self::parseNumber($state))
                                        ->readOnly()
                                        ->dehydrated(),

                                    Select::make('scope_items')
                                        ->label('SOW')
                                        ->multiple()
                                        ->options(function (callable $get): array {
                                            // Opsi default + SOW dari rate card KOL terpilih (agar value auto-fill valid & tampil)
                                            $base = [
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
                                            ];
                                            foreach (self::kolSowLabels($get('data_kol_id')) as $label) {
                                                $base[$label] = $label;
                                            }
                                            // Pastikan value yang sudah terpilih tetap tampil walau tak ada di daftar
                                            foreach ((array) $get('scope_items') as $selected) {
                                                if (filled($selected) && ! isset($base[$selected])) {
                                                    $base[$selected] = $selected;
                                                }
                                            }

                                            return $base;
                                        })
                                        ->searchable()
                                        ->live()
                                        ->default([])
                                        ->afterStateUpdated(function ($state, callable $set, callable $get) {
                                            $set('rate', number_format(round(self::computeRateFromSow(
                                                $get('data_kol_id'),
                                                $get('name'),
                                                $get('channel'),
                                                (array) $state
                                            )), 0, '.', ','));
                                        }),

                                    // Total rate disimpan (hidden); rincian per-SOW ditampilkan di Placeholder.
                                    Hidden::make('rate')
                                        ->dehydrateStateUsing(fn($state) => round(self::parseNumber($state)))
                                        ->default(0),

                                    // Rincian rate per SOW — tampil rapi seperti spreadsheet (Item | Rate + Total)
                                    Placeholder::make('rate_breakdown')
                                        ->label('Rate per SOW')
                                        ->content(fn(callable $get) => self::renderSowRateBreakdown($get)),

                                    // Margin % editable per KOL — diterapkan ke semua SOW-nya saat generate budget.
                                    TextInput::make('margin_percent')
                                        ->label('Margin %')
                                        ->numeric()
                                        ->suffix('%')
                                        ->minValue(0)
                                        ->maxValue(99)
                                        ->placeholder('Auto')
                                        ->helperText('Kosong = otomatis')
                                        ->live(debounce: 500),

                                    // Cost (MU PPh) — total seluruh SOW, read-only, dihitung otomatis.
                                    Placeholder::make('kol_cost_display')
                                        ->label('Cost (MU PPh)')
                                        ->content(fn(callable $get) => new \Illuminate\Support\HtmlString(
                                            '<span style="font-size:12px;font-weight:600;color:#dc2626;white-space:nowrap;">Rp ' .
                                            number_format(round(self::computeKolTotals($get)['cost']), 0, ',', '.') . '</span>'
                                        )),

                                    // Client Price — total seluruh SOW, read-only, dihitung otomatis.
                                    Placeholder::make('kol_client_display')
                                        ->label('Client Price')
                                        ->content(fn(callable $get) => new \Illuminate\Support\HtmlString(
                                            '<span style="font-size:12px;font-weight:700;color:#059669;white-space:nowrap;">Rp ' .
                                            number_format(round(self::computeKolTotals($get)['client']), 0, ',', '.') . '</span>'
                                        )),

                                    Checkbox::make('is_selected')
                                        ->label('Quotation')
                                        ->default(false)
                                        ->live(),
                                ])
                                ->defaultItems(0)
                                ->addActionLabel('Tambah Baris Manual')
                                ->reorderable()
                                ->columnSpanFull()
                                ->extraAttributes(['id' => 'kol-list-repeater'])
                                ->live(),

                            Actions::make([
                                Action::make('import_csv_kols')
                                    ->label('Import dari CSV')
                                    ->icon('heroicon-o-arrow-up-tray')
                                    ->color('white')
                                    ->modalHeading('Import KOL dari CSV')
                                    ->modalDescription('Upload CSV berisi 3 kolom: channel, link, domisili. Data lain (username, followers, tier, ER, impression, engagement, category) akan di-fetch otomatis dari API per row. Data yang sudah ada tidak terhapus — baris baru di-append.')
                                    ->modalWidth('2xl')
                                    ->modalSubmitActionLabel('Import')
                                    ->modalIcon('heroicon-o-arrow-up-tray')
                                    ->extraModalFooterActions([
                                        Action::make('download_template')
                                            ->label('Download Template CSV')
                                            ->icon('heroicon-o-arrow-down-tray')
                                            ->color('gray')
                                            ->action(fn() => self::downloadKolCsvTemplate()),
                                    ])
                                    ->form([
                                        Placeholder::make('format_info')
                                            ->label('Format Kolom')
                                            ->content(new \Illuminate\Support\HtmlString(
                                                '<div class="text-sm leading-relaxed">
                                                    Kolom <strong>wajib</strong>: <code>channel</code>, <code>link</code>.<br>
                                                    Kolom opsional: <code>domisili</code>.<br>
                                                    Channel yang didukung auto-fetch: <code>Instagram</code>, <code>Tiktok</code>, <code>Youtube Channels</code>, <code>Youtube Shorts</code>.<br>
                                                    Channel lain (<code>Threads</code>, <code>Facebook</code>, <code>Talent</code>, <code>X</code>) tetap di-import tapi data API tidak terisi otomatis.
                                                </div>'
                                            )),

                                        FileUpload::make('csv_file')
                                            ->label('File CSV')
                                            ->required()
                                            ->disk('local')
                                            ->directory('imports/kols-temp')
                                            ->visibility('private')
                                            ->maxSize(2048)
                                            ->acceptedFileTypes(['application/csv', 'application/vnd.ms-excel'])
                                            ->helperText('Maks 2 MB. Klik "Download Template CSV" di bawah untuk contoh format. Proses import bisa lambat tergantung jumlah baris (1 API call per baris).'),
                                    ])
                                    ->action(function (array $data, callable $get, callable $set): void {
                                        // Beri ruang waktu eksekusi karena tiap row = 1 API call
                                        @set_time_limit(300);

                                        $result = self::importKolsFromCsv($data['csv_file'], $get('kols') ?? []);
                                        $set('kols', array_values($result['kols']));

                                        $body = "Auto-fetch sukses: {$result['fetched']} / {$result['count']} baris.";
                                        if (!empty($result['errors'])) {
                                            $body .= ' Issue: ' . implode(' | ', array_slice($result['errors'], 0, 3));
                                            if (count($result['errors']) > 3) {
                                                $body .= ' (+' . (count($result['errors']) - 3) . ' lainnya)';
                                            }
                                        }

                                        Notification::make()
                                                    ->title($result['count'] > 0
                                                        ? "✅ Berhasil import {$result['count']} KOL"
                                                        : '⚠️ Tidak ada baris valid yang diimport')
                                            ->{$result['count'] > 0 ? 'success' : 'warning'}()
                                                ->body($body)
                                                ->send();
                                    }),
                            ])
                                ->alignment('center')
                                ->columnSpanFull(),
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

                    Step::make('Budget Items')
                        ->icon('heroicon-m-currency-dollar')
                        ->description('Input rate KOL & kalkulasi cost, client price, dan margin')
                        // Dinonaktifkan sementara: kalkulasi margin/cost/client price dipindah ke KOL List
                        // agar tidak dobel. Generate InternalBudgetItem tetap jalan di EditMediaPlan::afterSave.
                        ->hidden()
                        ->schema([
                            Section::make('💰 Budget Items')
                                ->description('Input Rate (Base) = harga pokok KOL. Cost & Client Price dikalkulasi otomatis.')
                                ->schema([
                                    Placeholder::make('budget_items_hint')
                                        ->label('')
                                        ->content(function ($record) {
                                            if (!$record) {
                                                return new \Illuminate\Support\HtmlString(
                                                    '<p class="text-sm text-warning-600 dark:text-warning-400">Simpan Media Plan terlebih dahulu, lalu kembali ke tab ini untuk mengisi rate KOL.</p>'
                                                );
                                            }
                                            return null;
                                        })
                                        ->visible(fn($record) => $record === null)
                                        ->columnSpanFull(),

                                    Placeholder::make('budget_items_sticky_css')
                                        ->hiddenLabel()
                                        ->content(new \Illuminate\Support\HtmlString('
                                            <style>
                                                #budget-items-repeater .fi-fo-repeater-table-wrapper {
                                                    overflow-x: auto;
                                                }
                                                /* Freeze KOL column */
                                                #budget-items-repeater table th:nth-child(1),
                                                #budget-items-repeater table td:nth-child(1) {
                                                    position: sticky;
                                                    left: 0;
                                                    z-index: 3;
                                                    background-color: #ffffff;
                                                }
                                                /* Freeze Scope Item column */
                                                #budget-items-repeater table th:nth-child(2),
                                                #budget-items-repeater table td:nth-child(2) {
                                                    position: sticky;
                                                    left: 160px;
                                                    z-index: 3;
                                                    background-color: #ffffff;
                                                    box-shadow: 3px 0 6px -2px rgba(0,0,0,0.10);
                                                }
                                                /* Dark mode */
                                                .dark #budget-items-repeater table th:nth-child(1),
                                                .dark #budget-items-repeater table td:nth-child(1) {
                                                    background-color: #111827;
                                                }
                                                .dark #budget-items-repeater table th:nth-child(2),
                                                .dark #budget-items-repeater table td:nth-child(2) {
                                                    background-color: #111827;
                                                    box-shadow: 3px 0 6px -2px rgba(0,0,0,0.4);
                                                }
                                                /* Header row darker shade */
                                                #budget-items-repeater table thead th:nth-child(1),
                                                #budget-items-repeater table thead th:nth-child(2) {
                                                    background-color: #f9fafb;
                                                }
                                                .dark #budget-items-repeater table thead th:nth-child(1),
                                                .dark #budget-items-repeater table thead th:nth-child(2) {
                                                    background-color: #1f2937;
                                                }
                                            </style>
                                        '))
                                        ->columnSpanFull(),

                                    Repeater::make('budget_items')
                                        ->label('Items')
                                        ->extraAttributes(['id' => 'budget-items-repeater'])
                                        ->schema([
                                            TextInput::make('kol_name')
                                                ->label('KOL')
                                                ->disabled()
                                                ->dehydrated(false),

                                            TextInput::make('scope_item')
                                                ->label('Scope Item')
                                                ->disabled(),

                                            TextInput::make('qty')
                                                ->label('Qty')
                                                ->numeric()
                                                ->default(1)
                                                ->minValue(1)
                                                ->required()
                                                ->live(debounce: 500)
                                                ->afterStateUpdated(fn($get, $set, $livewire) => self::calculateBudgetItem($get, $set, $livewire->record)),

                                            TextInput::make('rate_base')
                                                ->label('Rate (Base)')
                                                ->prefix('Rp')
                                                ->placeholder('Harga pokok KOL')
                                                ->mask(RawJs::make("\$money(\$input, '.', ',', 0)"))
                                                ->stripCharacters(',')
                                                ->dehydrateStateUsing(fn($state) => self::parseNumber($state))
                                                ->live(debounce: 500)
                                                ->afterStateUpdated(fn($get, $set, $livewire) => self::calculateBudgetItem($get, $set, $livewire->record)),

                                            Select::make('master_pph_id')
                                                ->label('Tax Type')
                                                ->options(\App\Models\MasterPph::getActiveOptions())
                                                ->native(false)
                                                ->live()
                                                ->afterStateUpdated(fn($get, $set, $livewire) => self::calculateBudgetItem($get, $set, $livewire->record)),

                                            TextInput::make('mu_pph')
                                                ->label('🔴 Cost (MU PPh)')
                                                ->prefix('Rp')
                                                ->mask(RawJs::make("\$money(\$input, '.', ',', 0)"))
                                                ->stripCharacters(',')
                                                ->dehydrateStateUsing(fn($state) => self::parseNumber($state))
                                                ->readOnly(),

                                            TextInput::make('rounded')
                                                ->label('🟢 Client Price')
                                                ->prefix('Rp')
                                                ->mask(RawJs::make("\$money(\$input, '.', ',', 0)"))
                                                ->stripCharacters(',')
                                                ->dehydrateStateUsing(fn($state) => self::parseNumber($state))
                                                ->readOnly(),

                                            TextInput::make('actual_margin_percent')
                                                ->label('Margin %')
                                                ->suffix('%')
                                                ->numeric()
                                                ->minValue(0)
                                                ->maxValue(99)
                                                ->live(debounce: 500)
                                                ->afterStateUpdated(fn($get, $set) => self::recalcBudgetItemFromMargin($get, $set))
                                                ->helperText('Bisa diedit manual'),

                                            Textarea::make('notes')
                                                ->label('Notes')
                                                ->rows(1)
                                                ->placeholder('Optional...'),

                                            TextInput::make('kol_status')
                                                ->label('Status')
                                                ->placeholder('—')
                                                ->readOnly()
                                                ->dehydrated(false),

                                            // Hidden fields
                                            Hidden::make('id'),
                                            Hidden::make('status')->default('pending'),
                                            Hidden::make('rejection_notes'),
                                            Hidden::make('sort_order')->default(0),
                                            TextInput::make('published_rate')
                                                ->hidden()
                                                ->dehydrateStateUsing(fn($state) => self::parseNumber($state)),
                                            TextInput::make('subtotal')
                                                ->hidden()
                                                ->dehydrateStateUsing(fn($state) => self::parseNumber($state)),
                                            TextInput::make('mu_target')
                                                ->hidden()
                                                ->dehydrateStateUsing(fn($state) => self::parseNumber($state)),
                                        ])
                                        ->table([
                                            TableColumn::make('KOL')->width('160px'),
                                            TableColumn::make('Scope Item')->width('160px'),
                                            TableColumn::make('Qty')->width('60px'),
                                            TableColumn::make('Rate (Base)')->width('160px'),
                                            TableColumn::make('Tax Type')->width('140px'),
                                            TableColumn::make('🔴 Cost (MU PPh)')->width('160px'),
                                            TableColumn::make('🟢 Client Price')->width('160px'),
                                            TableColumn::make('Margin %')->width('130px'),
                                            TableColumn::make('Notes')->width('160px'),
                                            TableColumn::make('Status')->width('110px'),
                                        ])
                                        ->addable(false)
                                        ->deletable(false)
                                        ->reorderable(false)
                                        ->defaultItems(0)
                                        ->visible(fn($record) => $record !== null)
                                        ->columnSpanFull(),
                                ])
                                ->columnSpanFull(),
                        ]),

                    // Step::make('Margin Setting')
                    //     ->icon('heroicon-m-calculator')
                    //     ->description('Configure margin settings for this campaign')
                    //     ->schema([
                    //         Section::make('🎯 Margin Configuration')
                    //             ->description('Setting margin akan diaplikasikan ke semua KOL dalam campaign ini saat kalkulasi Internal Budget')
                    //             ->schema([
                    //                 TextInput::make('margin_percent')
                    //                     ->label('Custom Margin %')
                    //                     ->suffix('%')
                    //                     ->numeric()
                    //                     ->step('0.01')
                    //                     ->minValue(0)
                    //                     ->maxValue(100)
                    //                     ->default(30)
                    //                     ->required()
                    //                     ->helperText('Contoh: 30 untuk 30%, 40 untuk 40%, dll'),
                    //
                    //                 Toggle::make('use_global_margin')
                    //                     ->label('Apply to All KOLs')
                    //                     ->helperText('Jika aktif, margin ini akan diterapkan ke semua KOL.')
                    //                     ->inline()
                    //                     ->default(true)
                    //                     ->live()
                    //                     ->columnSpanFull(),
                    //
                    //                 Repeater::make('kol_margins')
                    //                     ->label('Custom Margin per KOL')
                    //                     ->hidden(fn(callable $get) => $get('use_global_margin') === true)
                    //                     ->schema([
                    //                         TextInput::make('name')->disabled()->dehydrated(false)->columnSpan(2),
                    //                         TextInput::make('margin')->label('Margin %')->numeric()->suffix('%')->required()->columnSpan(1),
                    //                     ])
                    //                     ->addable(false)->deletable(false)->reorderable(false)
                    //                     ->columns(3)->columnSpanFull(),
                    //             ])
                    //             ->columns(2),
                    //     ]),
                ])
                    ->columnSpanFull()
                    ->skippable()
            ]);
    }

    /**
     * Calculate budget item values (cost, client price, margin) based on rate & PPh.
     * Margin diambil dari MediaPlan record (global) atau per-item override.
     */
    private static function calculateBudgetItem(callable $get, callable $set, $mediaPlanRecord): void
    {
        $qty = (int) ($get('qty') ?? 1);
        $rateBase = self::parseNumber($get('rate_base') ?? 0);

        if ($rateBase <= 0) {
            $set('mu_pph', 0);
            $set('published_rate', 0);
            $set('rounded', 0);
            $set('actual_margin_percent', 0);
            $set('subtotal', 0);
            $set('mu_target', 0);
            return;
        }

        $subtotal = $qty * $rateBase;

        // PPh Coefficient dari MasterPph
        $masterPphId = $get('master_pph_id');
        $pphCoefficient = 0.975; // default Pribadi
        if ($masterPphId) {
            $masterPph = \App\Models\MasterPph::find($masterPphId);
            if ($masterPph) {
                $pphCoefficient = $masterPph->getCalculatedCoefficient();
            }
        }

        $muPph = $subtotal / $pphCoefficient;

        // Margin: prioritaskan global margin dari MediaPlan
        $targetMargin = 30.0;
        if ($mediaPlanRecord && $mediaPlanRecord->use_global_margin && $mediaPlanRecord->margin_type === 'custom') {
            $targetMargin = (float) $mediaPlanRecord->margin_percent;
        } elseif ($mediaPlanRecord) {
            $targetMargin = \App\Models\MasterMargin::getMarginForAmount($subtotal);
        }

        $marginDecimal = $targetMargin / 100;
        $muTarget = $marginDecimal >= 1 ? $muPph : $muPph / (1 - $marginDecimal);

        $rounded = ceil($muTarget / 100000) * 100000;
        $actualMargin = $rounded > 0 ? (($rounded - $muPph) / $rounded) * 100 : 0;

        $fmt = fn($v) => number_format(round($v), 0, '.', ',');

        $set('subtotal', round($subtotal));
        $set('mu_pph', $fmt($muPph));
        $set('mu_target', round($muTarget));
        $set('published_rate', $fmt($muTarget));
        $set('rounded', $fmt($rounded));
        $set('actual_margin_percent', round($actualMargin, 2));
    }

    /**
     * Recalkulasi Client Price dari margin yang diedit manual.
     * Cost (MU PPh) tetap; Client Price (rounded) dihitung ulang dari margin baru.
     */
    private static function recalcBudgetItemFromMargin(callable $get, callable $set): void
    {
        $muPph = self::parseNumber($get('mu_pph') ?? 0);
        $margin = (float) ($get('actual_margin_percent') ?? 0);

        if ($muPph <= 0) {
            return;
        }

        $marginDecimal = min(max($margin, 0), 99) / 100;
        $muTarget = $marginDecimal >= 1 ? $muPph : $muPph / (1 - $marginDecimal);
        $rounded = ceil($muTarget / 100000) * 100000;
        $actualMargin = $rounded > 0 ? (($rounded - $muPph) / $rounded) * 100 : 0;

        $fmt = fn($v) => number_format(round($v), 0, '.', ',');

        $set('mu_target', round($muTarget));
        $set('published_rate', $fmt($muTarget));
        $set('rounded', $fmt($rounded));
        $set('actual_margin_percent', round($actualMargin, 2));
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

    /**
     * Helper: Build view data for the KOL overview modal from a repeater item state.
     */
    private static function buildKolOverviewData(array $item): array
    {
        $schedules = \App\Helpers\PaymentScheduleHelper::getUpcomingSchedules();
        $paymentKey = $item['payment_date'] ?? null;

        // Fetch rate cards from DataKol database
        $dataKol = \App\Models\DataKol::where('username', $item['name'] ?? null)
            ->where('channel', $item['channel'] ?? null)
            ->first();
        $rateCards = $dataKol ? $dataKol->rateCards()->with('masterSow')->get() : collect();

        // Resolve PIC ke nama (legacy data bisa menyimpan user ID)
        $picId = $item['pic'] ?? null;
        $picName = $picId
            ? (\App\Models\BvSalesList::find($picId)?->nama_sales
                ?? \App\Models\User::find($picId)?->name
                ?? $picId)
            : '—';

        return [
            'is_selected' => ($item['is_selected'] ?? false) ? 'Ya ✅' : 'Tidak',
            'status' => $item['status'] ?? '—',
            'pic' => $picName,
            'channel' => $item['channel'] ?? '—',
            'name' => $item['name'] ?? '—',
            'domisili' => $item['domisili'] ?? '—',
            'links' => implode(', ', (array) ($item['links'] ?? [])),
            'tipe_pajak_kol' => MasterPph::find($item['tipe_pajak_kol'] ?? null)?->name ?? '—',
            'followers' => !empty($item['followers']) ? number_format((int) $item['followers'], 0, ',', '.') : '—',
            'tier' => $item['tier'] ?? '—',
            'er_percent' => !empty($item['er_percent']) ? $item['er_percent'] . '%' : '—',
            'impression' => !empty($item['impression']) ? number_format((int) $item['impression'], 0, ',', '.') : '—',
            'engagement' => !empty($item['engagement']) ? number_format((int) $item['engagement'], 0, ',', '.') : '—',
            'cpi_cpv' => self::parseNumber($item['cpi_cpv'] ?? 0) > 0
                ? 'Rp ' . number_format(round(self::parseNumber($item['cpi_cpv'])), 0, ',', '.')
                : '—',
            'cpe' => self::parseNumber($item['cpe'] ?? 0) > 0
                ? 'Rp ' . number_format(round(self::parseNumber($item['cpe'])), 0, ',', '.')
                : '—',
            'scope_items' => implode(', ', (array) ($item['scope_items'] ?? [])),
            'payment_date' => ($paymentKey && isset($schedules[$paymentKey]))
                ? $schedules[$paymentKey]
                : ($paymentKey ?? '—'),
            'rate_cards' => $rateCards,
            'kol_pic_name' => $dataKol?->full_name ?: '—',
            'kol_email' => $dataKol?->email ?: '—',
            'kol_wa' => $dataKol?->wa_number ?: '—',
            'kol_category' => !empty($dataKol?->category) ? implode(', ', (array) $dataKol->category) : '—',
        ];
    }

    /**
     * Whitelist of CSV columns supported by the bulk importer.
     * Sisanya (username, followers, tier, dll) di-fetch dari API per row.
     */
    private const KOL_CSV_HEADERS = ['channel', 'link', 'domisili'];

    /**
     * Stream a CSV template (header + sample rows) for the bulk importer.
     */
    private static function downloadKolCsvTemplate(): \Symfony\Component\HttpFoundation\StreamedResponse
    {
        $samples = [
            ['Instagram', 'https://www.instagram.com/mpl.id.official/', 'Jakarta'],
            ['Tiktok', 'https://www.tiktok.com/@findydigitalkreatif', 'Bandung'],
        ];

        return response()->streamDownload(function () use ($samples) {
            $h = fopen('php://output', 'w');
            fputcsv($h, self::KOL_CSV_HEADERS);
            foreach ($samples as $sample) {
                fputcsv($h, $sample);
            }
            fclose($h);
        }, 'kol-import-template.csv', ['Content-Type' => 'text/csv']);
    }

    /**
     * Channels yang men-support API auto-fetch.
     */
    private const SCRAPABLE_CHANNELS = ['Instagram', 'Tiktok', 'Youtube Channels', 'Youtube Shorts'];

    /**
     * Parse uploaded CSV (channel, link, domisili) dan untuk tiap baris fetch profile dari API.
     * Untuk channel yang tidak supported, baris tetap di-import tanpa auto-fill.
     *
     * @return array{kols: array, count: int, fetched: int, errors: array<string>}
     */
    private static function importKolsFromCsv(string $csvPath, array $existingKols): array
    {
        $disk = \Illuminate\Support\Facades\Storage::disk('local');

        if (!$disk->exists($csvPath)) {
            return ['kols' => $existingKols, 'count' => 0, 'fetched' => 0, 'errors' => ['File CSV tidak ditemukan']];
        }

        $content = $disk->get($csvPath);
        $disk->delete($csvPath); // cleanup tmp file regardless of outcome

        $lines = preg_split('/\r\n|\n|\r/', trim((string) $content)) ?: [];
        if (count($lines) < 2) {
            return ['kols' => $existingKols, 'count' => 0, 'fetched' => 0, 'errors' => ['CSV kosong atau hanya header']];
        }

        $headers = array_map(fn($h) => strtolower(trim($h)), str_getcsv((string) array_shift($lines)));

        if (!in_array('channel', $headers, true) || !in_array('link', $headers, true)) {
            return ['kols' => $existingKols, 'count' => 0, 'fetched' => 0, 'errors' => ['Header CSV harus memuat kolom: channel, link']];
        }

        $defaultPph = MasterPph::active()->ordered()->first()?->id;
        $startRowNum = (int) (collect($existingKols)->max('row_number') ?? 0) + 1;
        $newKols = $existingKols;
        $count = 0;
        $fetched = 0;
        $errors = [];

        foreach ($lines as $idx => $line) {
            $lineNo = $idx + 2; // +1 header, +1 1-based
            if (trim($line) === '') {
                continue;
            }

            $cells = str_getcsv($line);
            if (count($cells) !== count($headers)) {
                $errors[] = "Baris {$lineNo}: jumlah kolom tidak match header";
                continue;
            }

            $row = array_combine($headers, array_map(fn($v) => is_string($v) ? trim($v) : $v, $cells));
            $channel = $row['channel'] ?? '';
            $link = $row['link'] ?? '';
            $domisili = $row['domisili'] ?? null;

            if ($channel === '' || $link === '') {
                $errors[] = "Baris {$lineNo}: channel/link kosong";
                continue;
            }

            // Default fallback values jika API gagal atau channel tidak supported
            $kolEntry = [
                'row_number' => $startRowNum++,
                'data_kol_id' => null,
                'channel' => $channel,
                'name' => $link, // fallback ke link kalau username gagal di-fetch
                'domisili' => $domisili,
                'links' => [$link],
                'tipe_pajak_kol' => $defaultPph,
                'followers' => 0,
                'tier' => null,
                'er_percent' => 0,
                'impression' => 0,
                'engagement' => 0,
                'scope_items' => [],
                'after_nego' => null,
                'payment_date' => null,
                'pic' => null,
                'status' => 'New List',
                'notes' => null,
                'categories' => null,
                'is_selected' => false,
            ];

            // Auto-fetch dari API hanya untuk scrapable channels
            if (in_array($channel, self::SCRAPABLE_CHANNELS, true)) {
                try {
                    $profile = match ($channel) {
                        'Instagram' => (new InstagramService())->getProfile($link),
                        'Tiktok' => (new TiktokService())->getProfile($link),
                        'Youtube Channels' => (new YoutubeChannelsService())->getProfile($link),
                        'Youtube Shorts' => (new YoutubeShortsService())->getProfile($link),
                    };

                    if ($profile) {
                        $followers = (int) ($profile['followers_count'] ?? 0);
                        $erPercent = (float) ($profile['engagement_rate'] ?? 0);

                        // Find or create DataKol biar terhubung ke database
                        $dataKol = DataKol::firstOrCreate(
                            ['channel' => $channel, 'username' => $profile['username']],
                            [
                                'link_userprofile' => $link,
                                'followers' => $followers,
                                'tier' => $profile['tier'] ?? \App\Models\MediaPlanKol::calculateTier($followers),
                                'engagement_rate' => $erPercent,
                                'engagements' => $profile['total_engagements'] ?? 0,
                                'impressions' => $profile['average_impressions'] ?? 0,
                                'category' => $profile['category_name'] ?? null,
                                'status' => 'New List',
                                'terakhir_update' => now(),
                            ]
                        );

                        $kolEntry['data_kol_id'] = $dataKol->id;
                        $kolEntry['name'] = $profile['username'];
                        $kolEntry['followers'] = $followers;
                        $kolEntry['tier'] = $profile['tier'] ?? \App\Models\MediaPlanKol::calculateTier($followers);
                        $kolEntry['er_percent'] = $erPercent;
                        $kolEntry['impression'] = (int) ($profile['average_impressions'] ?? 0);
                        $kolEntry['engagement'] = intval($followers * ($erPercent / 100));
                        $kolEntry['categories'] = $profile['category_name'] ?? null;
                        $fetched++;
                    } else {
                        $errors[] = "Baris {$lineNo} ({$channel}): API tidak return profile";
                    }
                } catch (\Throwable $e) {
                    $errors[] = "Baris {$lineNo} ({$channel}): " . $e->getMessage();
                    // Tetap include di kols dengan data minimal — user bisa edit manual
                }
            }

            $newKols[] = $kolEntry;
            $count++;
        }

        // Pastikan urutan sesuai row_number (A-Z sesuai urutan di CSV)
        usort($newKols, fn($a, $b) => ($a['row_number'] ?? 0) <=> ($b['row_number'] ?? 0));

        return ['kols' => $newKols, 'count' => $count, 'fetched' => $fetched, 'errors' => $errors];
    }
}
