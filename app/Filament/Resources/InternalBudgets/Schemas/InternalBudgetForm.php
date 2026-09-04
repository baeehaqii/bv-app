<?php

namespace App\Filament\Resources\InternalBudgets\Schemas;

use App\Enums\MediaPlanKolStatus;
use App\Filament\Resources\InternalBudgets\Pages\EditInternalBudget;
use App\Models\MediaPlanKol;
use App\Models\InternalBudgetItem;
use Filament\Schemas\Schema;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Repeater\TableColumn;
use Filament\Schemas\Components\Section;
use Filament\Support\RawJs;
use Filament\Actions\Action;
use Filament\Notifications\Notification;

class InternalBudgetForm
{
    /** @var array<int, array<int, string>> daftar KOL per media plan, diingat sepanjang request */
    private static array $opsiKol = [];

    /** @var array<int, array{kol:int, sow:int, campaign:?\App\Models\BvCampign}> per budget, diingat sepanjang request */
    private static array $tertahan = [];

    /**
     * Buang hitungan yang diingat. WAJIB dipanggil setelah status item berubah
     * di tengah request — kalau tidak, peringatannya masih menyebut angka lama.
     */
    public static function lupakanTertahan(): void
    {
        static::$tertahan = [];
    }

    /**
     * Item yang sudah di-approve tapi belum sampai ke Campaign Ongoing.
     *
     * Approve per KOL cuma menandai internal_budget_items.status. Yang benar-benar
     * mengisi Campaign Ongoing adalah syncCampaignKolsFromApprovedBudget(), dan itu
     * hanya jalan saat status budget berubah jadi "Approve AM". Tanpa keterangan,
     * approve terasa seperti tidak melakukan apa-apa.
     *
     * @return array{kol:int, sow:int, campaign:?\App\Models\BvCampign}
     */
    private static function approvedTertahan(?\App\Models\InternalBudget $record): array
    {
        if (! $record) {
            return ['kol' => 0, 'sow' => 0, 'campaign' => null];
        }

        return static::$tertahan[$record->id] ??= [
            'kol' => (int) $record->items()->where('status', 'approved')
                ->whereNotNull('media_plan_kol_id')->distinct()->count('media_plan_kol_id'),
            'sow' => (int) $record->items()->where('status', 'approved')->count(),
            'campaign' => $record->mediaPlan?->bvSales?->campaign()->first(),
        ];
    }

    /**
     * Id SOW yang jadi sasaran aksi baris ini.
     *
     * Approval diputuskan per KOL, bukan per SOW — sama seperti di Link Review
     * Client. Baris ringkas karena itu membawa seluruh SOW milik KOL-nya;
     * baris di mode rincian cuma membawa dirinya sendiri, jadi aksinya tetap
     * bisa dipakai untuk mengoreksi satu SOW.
     *
     * @return array<int, int>
     */
    private static function sasaranSow(Repeater $component, ?string $uuid): array
    {
        return array_values(array_filter(
            (array) (self::stateBaris($component, $uuid)['item_ids'] ?? []),
        ));
    }

    /** "SOW \"IG Reels\"" atau "6 SOW milik KOL ini" — untuk keterangan modal. */
    private static function sebutanSasaran(Repeater $component, ?string $uuid): string
    {
        $state = self::stateBaris($component, $uuid);
        $jumlah = count(self::sasaranSow($component, $uuid));

        if ($jumlah <= 1) {
            return 'SOW "' . ($state['scope_item'] ?? 'ini') . '"';
        }

        return "seluruh {$jumlah} SOW milik KOL ini";
    }

    /**
     * Isi satu baris repeater, aman terhadap uuid yang sudah tidak ada.
     *
     * Mengganti isi daftar — membuka rincian SOW, pindah halaman, ganti jumlah
     * per halaman — membuat Filament menerbitkan uuid baru untuk tiap baris,
     * sementara aksi yang barusan diklik masih memegang uuid lama. getChildSchema()
     * mengembalikan null untuk uuid itu dan getRawItemState() meledak di atasnya.
     *
     * Baris yang tidak ditemukan dianggap kosong; semua visible() di bawah membaca
     * 'id' yang blank sebagai "bukan sasaran aksi", jadi aksinya diam.
     *
     * @return array<string, mixed>
     */
    private static function stateBaris(Repeater $component, ?string $uuid): array
    {
        if (blank($uuid) || ! $component->getChildSchema($uuid)) {
            return [];
        }

        return $component->getRawItemState($uuid);
    }

    /**
     * Sama seperti stateBaris(), tapi state yang sudah lewat hook — dipakai aksi
     * yang butuh id record sungguhan.
     *
     * @return array<string, mixed>
     */
    private static function stateBarisPenuh(Repeater $component, ?string $uuid): array
    {
        if (blank($uuid) || ! $component->getChildSchema($uuid)) {
            return [];
        }

        return $component->getItemState($uuid);
    }


    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                // Section 1: Info & Status
                Section::make('Budget Information')
                    ->schema([
                        Select::make('media_plan_id')
                            ->label('Media Plan')
                            ->relationship('mediaPlan', 'campaign_name')
                            ->required()
                            ->searchable()
                            ->preload()
                            ->disabled(fn($record) => $record !== null),

                        Select::make('status')
                            ->label('Status')
                            ->options(\App\Models\InternalBudget::STATUS_OPTIONS)
                            ->default('draft')
                            ->required()
                            ->live()
                            ->afterStateUpdated(function ($state, $record) {
                                // Aktivasi campaign hanya saat status final "Approve AM"
                                if ($state !== 'approve_am' || !$record) {
                                    return;
                                }

                                $sales = $record->mediaPlan?->bvSales;
                                if (!$sales) {
                                    return;
                                }

                                // Update status sales → CAMPAIGN_LIVE jika belum
                                if ($sales->status !== \App\Enums\SalesStatus::CAMPAIGN_LIVE) {
                                    $sales->update(['status' => \App\Enums\SalesStatus::CAMPAIGN_LIVE]);
                                }

                                // 4.4 — Sync deal_value ke BvSales dari total_rounded budget
                                $record->refresh();
                                if ($record->total_rounded > 0) {
                                    $sales->update(['deal_value' => $record->total_rounded]);
                                }

                                // Sync KOL dari approved budget items ke Campaign Ongoing
                                $record->syncCampaignKolsFromApprovedBudget();

                                Notification::make()
                                    ->title('Campaign Live!')
                                    ->body('Status Sales diperbarui ke "Campaign Live". Data KOL Campaign Ongoing Internal sudah diisi sesuai SOW yang di-approve.')
                                    ->success()
                                    ->send();
                            }),

                        Textarea::make('rejection_notes')
                            ->label('Alasan Penolakan')
                            ->placeholder('Alasan penolakan budget...')
                            ->rows(3)
                            ->disabled()
                            ->visible(fn($record) => $record?->status === 'rejected')
                            ->columnSpanFull(),

                        Placeholder::make('summary_info')
                            ->label('💰 Quick Summary')
                            ->content(function ($record) {
                                if (!$record)
                                    return 'Save first to see the summary';

                                $cost = number_format($record->total_mu_pph ?? 0, 0, ',', '.');
                                $budget = number_format($record->total_rounded ?? 0, 0, ',', '.');

                                // Profit & Margin disembunyikan di Media Plan External (data internal saja).
                                return "Cost: Rp {$cost} | Budget: Rp {$budget}";
                            })
                            ->columnSpan(1),

                        Placeholder::make('margin_setting_info')
                            ->label('🎯 Margin Setting')
                            ->content(function ($record) {
                                if (!$record || !$record->mediaPlan) {
                                    return 'Please select Media Plan first';
                                }

                                $mediaPlan = $record->mediaPlan;
                                $marginType = $mediaPlan->margin_type ?? 'custom';
                                $marginPercent = $mediaPlan->margin_percent ?? null;
                                $useGlobal = $mediaPlan->use_global_margin ?? true;

                                if ($marginType === 'custom' && $useGlobal) {
                                    return new \Illuminate\Support\HtmlString(
                                        "<span class='text-warning-600 dark:text-warning-400 font-semibold'>Custom Global: {$marginPercent}%</span> " .
                                        "<span class='text-gray-500 text-sm'>(from Media Plan)</span>"
                                    );
                                } else {
                                    return new \Illuminate\Support\HtmlString(
                                        "<span class='text-gray-600 dark:text-gray-400'>Per-item margin</span> " .
                                        "<span class='text-gray-500 text-sm'>(set on each item)</span>"
                                    );
                                }
                            })
                            ->columnSpan(1),
                    ])
                    ->columns(2)
                    ->columnSpanFull(),

                // Section 2: BUDGET ITEMS (Read-only — dikonfigurasi dari Media Plan Internal)
                Section::make('💰 Budget Items')
                    ->description(function ($record): string {
                        // Setelah client submit Link Review, ubah keterangan section.
                        if ($record?->review_submitted_at) {
                            $tanggal = $record->review_submitted_at->format('d M Y, H:i');
                            return "✅ Telah disubmit oleh client pada {$tanggal}. "
                                . 'Lihat kolom "Pilihan Client" & "Feedback Client" pada tiap item untuk keputusan KOL/SOW yang dipakai. '
                                . 'Setelah difinalisasi, ubah status ke "Approve Client".';
                        }

                        return 'Items otomatis dari Media Plan Internal. Gunakan tombol "Sync from Media Plan" untuk memperbarui.';
                    })
                    ->schema([
                        // Di atas daftarnya, bukan di dekat dropdown Status: yang perlu
                        // diberi tahu adalah orang yang barusan menekan Approve.
                        Placeholder::make('approved_tertahan')
                            ->hiddenLabel()
                            ->visible(fn(?\App\Models\InternalBudget $record) => $record !== null
                                && $record->status !== 'approve_am'
                                && self::approvedTertahan($record)['sow'] > 0)
                            ->content(function (?\App\Models\InternalBudget $record): \Illuminate\Support\HtmlString {
                                ['kol' => $kol, 'sow' => $sow, 'campaign' => $campaign] = self::approvedTertahan($record);

                                $lanjutan = $campaign
                                    ? 'Ubah <strong>Status</strong> di atas menjadi <strong>&ldquo;Approve AM&rdquo;</strong> '
                                        . 'untuk mengirimkannya ke campaign <strong>' . e($campaign->campaign_name) . '</strong>.'
                                    : 'Media Plan ini belum punya campaign di Campaign Ongoing Internal, '
                                        . 'jadi belum ada tujuan pengirimannya walau statusnya diubah.';

                                return new \Illuminate\Support\HtmlString(
                                    '<div style="border:1px solid #fcd34d;background:#fffbeb;border-radius:.75rem;padding:.75rem 1rem;">'
                                    . '<p style="font-weight:600;color:#92400e;">&#9888; ' . $kol . ' KOL (' . $sow . ' SOW) '
                                    . 'sudah di-approve tapi belum masuk Campaign Ongoing</p>'
                                    . '<p style="margin-top:.25rem;font-size:.8rem;color:#b45309;">' . $lanjutan . '</p>'
                                    . '</div>'
                                );
                            })
                            ->columnSpanFull(),

                        Placeholder::make('budget_items_sticky_css')
                            // hiddenLabel(), bukan label(''): label kosong tetap
                            // dirender jadi teks "Budget items sticky css".
                            ->hiddenLabel()
                            ->content(new \Illuminate\Support\HtmlString('
                                <style>
                                    #ib-budget-items .fi-fo-repeater-table-wrapper { overflow-x: auto; }
                                    /* Freeze KOL column */
                                    #ib-budget-items table th:nth-child(1),
                                    #ib-budget-items table td:nth-child(1) {
                                        position: sticky;
                                        left: 0;
                                        z-index: 3;
                                        background-color: #ffffff;
                                    }
                                    /* Freeze Scope Item column */
                                    #ib-budget-items table th:nth-child(2),
                                    #ib-budget-items table td:nth-child(2) {
                                        position: sticky;
                                        left: 200px;
                                        z-index: 3;
                                        background-color: #ffffff;
                                        box-shadow: 3px 0 6px -2px rgba(0,0,0,0.10);
                                    }
                                    /* Header darker shade */
                                    #ib-budget-items table thead th:nth-child(1) { background-color: #f9fafb; }
                                    #ib-budget-items table thead th:nth-child(2) { background-color: #f9fafb; }
                                    /* Dark mode */
                                    .dark #ib-budget-items table th:nth-child(1),
                                    .dark #ib-budget-items table td:nth-child(1) { background-color: #111827; }
                                    .dark #ib-budget-items table th:nth-child(2),
                                    .dark #ib-budget-items table td:nth-child(2) {
                                        background-color: #111827;
                                        box-shadow: 3px 0 6px -2px rgba(0,0,0,0.4);
                                    }
                                    .dark #ib-budget-items table thead th:nth-child(1),
                                    .dark #ib-budget-items table thead th:nth-child(2) { background-color: #1f2937; }

                                    /* Scope Item pada baris ringkas: tombol yang membuka rincian SOW. */
                                    #ib-budget-items .ib-sow-link { display:inline-flex; align-items:center; gap:.4rem;
                                        font-weight:600; text-align:left; text-decoration:underline;
                                        text-underline-offset:3px; text-decoration-style:dotted; }
                                    #ib-budget-items .ib-sow-link:hover { color: var(--primary-600,#6d28d9); }
                                    #ib-budget-items .ib-sow-sisa { padding:.05rem .35rem; border-radius:.35rem;
                                        font-size:.7rem; font-weight:700; background:rgba(124,58,237,.12);
                                        color: var(--primary-600,#6d28d9); text-decoration:none; }
                                    #ib-budget-items .ib-sow-teks { opacity:.85; }
                                </style>
                            '))
                            ->columnSpanFull(),

                        Repeater::make('items')
                            // Isinya dibentuk EditInternalBudget::barisItemUntukForm(), bukan
                            // relationship(): baris bawaan halaman ini gabungan per KOL, jadi
                            // tidak satu-lawan-satu dengan record. dehydrated(false) menutup
                            // seluruh risikonya — semua kolom di sini read-only, dan setiap
                            // suntingan (Status KOL, Approve, Reject, Nego, Ganti KOL) sudah
                            // menulis langsung ke database lewat aksinya masing-masing.
                            ->dehydrated(false)
                            ->extraAttributes(['id' => 'ib-budget-items'])
                            ->schema([
                                Select::make('media_plan_kol_id')
                                    ->label('KOL')
                                    // Diingat per media plan: closure options() dijalankan ulang
                                    // untuk SETIAP baris, dan daftarnya sama persis tiap kali.
                                    ->options(function ($livewire) {
                                        $mediaPlanId = $livewire->record?->media_plan_id;
                                        if (!$mediaPlanId)
                                            return [];

                                        return static::$opsiKol[$mediaPlanId] ??= MediaPlanKol::where('media_plan_id', $mediaPlanId)
                                            ->orderBy('name')
                                            ->pluck('name', 'id')
                                            ->toArray();
                                    })
                                    ->disabled()
                                    // disabled() ikut mematikan dehydrate, dan yang tidak
                                    // ter-dehydrate TIDAK ikut getRawItemState() — itu yang dibaca
                                    // visible() tiap aksi baris. Tanpa ini tombol Detail SOW tidak
                                    // pernah muncul. Aman: repeater-nya dehydrated(false), jadi
                                    // tidak ada yang ikut tertulis saat Save.
                                    ->dehydrated(),

                                // Status KOL (editable di External) — tanpa "Payment Gateway".
                                // Tidak disimpan ke item; di-persist ke MediaPlanKol via EditInternalBudget::afterSave.
                                // native(true) -> popup di-render browser, lolos dari overflow-x & kolom freeze
                                // (kalau native(false) dropdown ketutup di belakang sel sticky).
                                Select::make('kol_status')
                                    ->label('Status KOL')
                                    // Status yang sedang dipakai KOL selalu dimasukkan ke opsi — status lama
                                    // (mis. "Locked") atau internal-only ("Payment Gateway") kalau tidak ada di
                                    // daftar akan bikin SEMUA aksi item & Save di halaman ini gagal validasi.
                                    ->options(function (callable $get): array {
                                        $options = MediaPlanKolStatus::toArrayExternal();
                                        $current = $get('kol_status')
                                            ?: MediaPlanKol::find($get('media_plan_kol_id'))?->status;

                                        if (filled($current) && ! isset($options[$current])) {
                                            $options[$current] = $current;
                                        }

                                        return $options;
                                    })
                                    ->native(true)
                                    ->dehydrated(false)
                                    ->afterStateHydrated(function (Select $component, $state, callable $get) {
                                        if (filled($state)) {
                                            return;
                                        }
                                        $kolId = $get('media_plan_kol_id');
                                        if ($kolId) {
                                            $component->state(MediaPlanKol::find($kolId)?->status);
                                        }
                                    })
                                    // Ditulis seketika, tidak menunggu Save Changes: daftar item
                                    // dipaginasi, jadi baris halaman lain sudah tidak ada lagi di
                                    // state saat Save ditekan. Aksi baris lain (Approve, Reject,
                                    // Nego) juga sudah menyimpan langsung ke database.
                                    ->live()
                                    ->afterStateUpdated(function ($state, callable $get) {
                                        $kolId = $get('media_plan_kol_id')
                                            ?: InternalBudgetItem::find($get('id'))?->media_plan_kol_id;

                                        if ($kolId && filled($state)) {
                                            MediaPlanKol::whereKey($kolId)->update(['status' => $state]);
                                        }
                                    }),

                                // Baris ringkas: teksnya sendiri yang jadi tombol pembuka
                                // rincian. Kolom aksi ada di ujung kanan tabel dan baru
                                // kelihatan setelah digeser horizontal — terlalu jauh untuk
                                // sesuatu yang dipakai sesering ini.
                                Placeholder::make('scope_item_display')
                                    ->label('Scope Item')
                                    ->content(function ($get): \Illuminate\Support\HtmlString {
                                        $teks = e((string) $get('scope_item'));
                                        $kolId = (int) ($get('media_plan_kol_id') ?? 0);
                                        $sisa = max(0, (int) ($get('jumlah_sow') ?? 1) - 1);

                                        // Mode rincian (id terisi) atau item tanpa KOL: tidak
                                        // ada yang bisa dibuka, tampilkan teks biasa.
                                        if (filled($get('id')) || ! $kolId) {
                                            return new \Illuminate\Support\HtmlString(
                                                "<span class=\"ib-sow-teks\">{$teks}</span>"
                                            );
                                        }

                                        $sisaLabel = $sisa > 0
                                            ? "<span class=\"ib-sow-sisa\">+{$sisa}</span>"
                                            : '';

                                        return new \Illuminate\Support\HtmlString(
                                            "<button type=\"button\" class=\"ib-sow-link\" "
                                            . "wire:click=\"bukaDetailKol({$kolId})\" "
                                            . "title=\"Lihat semua SOW KOL ini\">{$teks}{$sisaLabel}</button>"
                                        );
                                    }),

                                TextInput::make('qty')
                                    ->label('Qty')
                                    ->disabled(),

                                TextInput::make('rate_base')
                                    ->label('Rate (Base)')
                                    ->prefix('Rp')
                                    ->mask(RawJs::make('$money($input)'))
                                    ->disabled(),

                                Select::make('master_pph_id')
                                    ->label('Tax Type')
                                    ->options(\App\Models\MasterPph::getActiveOptions())
                                    ->disabled(),

                                TextInput::make('mu_pph')
                                    ->label('🔴 Cost (MU PPh)')
                                    ->prefix('Rp')
                                    ->mask(RawJs::make('$money($input)'))
                                    ->disabled(),

                                TextInput::make('published_rate')
                                    ->label('Published Rate')
                                    ->prefix('Rp')
                                    ->mask(RawJs::make('$money($input)'))
                                    ->disabled(),

                                TextInput::make('rounded')
                                    ->label('🟢 Client Price')
                                    ->prefix('Rp')
                                    ->mask(RawJs::make('$money($input)'))
                                    ->disabled(),

                                TextInput::make('actual_margin_percent')
                                    ->label('Margin %')
                                    ->suffix('%')
                                    ->disabled(),

                                Textarea::make('notes')
                                    ->label('Notes')
                                    ->rows(1)
                                    ->disabled(),

                                Placeholder::make('client_choice_badge')
                                    ->label('Pilihan Client')
                                    ->content(function ($get): \Illuminate\Support\HtmlString {
                                        $choice = $get('client_choice');
                                        [$label, $class] = match ($choice) {
                                            'approved' => ['✓ Dipakai', 'bg-green-100 text-green-800'],
                                            'rejected' => ['✗ Tidak', 'bg-red-100 text-red-800'],
                                            // Baris ringkas dengan SOW yang pilihannya beda-beda.
                                            'campuran' => ['◑ Sebagian', 'bg-blue-100 text-blue-800'],
                                            default    => ['— Belum', 'bg-gray-100 text-gray-600'],
                                        };
                                        return new \Illuminate\Support\HtmlString(
                                            "<span class=\"inline-flex items-center px-2 py-0.5 rounded text-xs font-medium {$class}\">{$label}</span>"
                                        );
                                    }),

                                Textarea::make('client_feedback')
                                    ->label('Feedback Client')
                                    ->rows(1)
                                    ->disabled(),

                                Hidden::make('id'),
                                // Sasaran Approve / Reject / Nego: satu SOW di mode rincian,
                                // seluruh SOW milik KOL di baris ringkas.
                                Hidden::make('item_ids')->default([]),
                                Hidden::make('scope_item'),
                                Hidden::make('jumlah_sow')->default(1),
                                Hidden::make('status')->default('pending'),
                                Hidden::make('rejection_notes'),
                                Hidden::make('nego_notes'),
                                Hidden::make('client_choice'),

                                Placeholder::make('status_badge')
                                    ->label('Status')
                                    ->content(function ($get): \Illuminate\Support\HtmlString {
                                        $status = $get('status') ?? 'pending';
                                        $colors = [
                                            'approved' => 'bg-green-100 text-green-800',
                                            'rejected' => 'bg-red-100 text-red-800',
                                            'nego'     => 'bg-yellow-100 text-yellow-800',
                                            'pending'  => 'bg-gray-100 text-gray-800',
                                            // Baris ringkas: SOW-nya tidak seragam statusnya.
                                            'campuran' => 'bg-blue-100 text-blue-800',
                                        ];
                                        $labels = [
                                            'approved' => 'Approved',
                                            'rejected' => 'Rejected',
                                            'nego'     => 'Nego',
                                            'pending'  => 'Pending',
                                            'campuran' => 'Campuran',
                                        ];
                                        $colorClass = $colors[$status] ?? $colors['pending'];
                                        $label = $labels[$status] ?? ucfirst($status);

                                        $negoNotes = $get('nego_notes');
                                        $tooltip = ($status === 'nego' && $negoNotes)
                                            ? ' title="' . e($negoNotes) . '"'
                                            : '';

                                        return new \Illuminate\Support\HtmlString(
                                            "<span class=\"inline-flex items-center px-2 py-0.5 rounded text-xs font-medium {$colorClass}\"{$tooltip}>{$label}</span>"
                                        );
                                    }),
                            ])
                            ->table([
                                TableColumn::make('KOL')->width('200px'),
                                TableColumn::make('Status KOL')->width('170px'),
                                TableColumn::make('Scope Item')->width('200px'),
                                TableColumn::make('Qty')->width('60px'),
                                TableColumn::make('Rate (Base)')->width('160px'),
                                TableColumn::make('Tax Type')->width('150px'),
                                TableColumn::make('🔴 Cost (MU PPh)')->width('170px'),
                                TableColumn::make('Published Rate')->width('170px'),
                                TableColumn::make('🟢 Client Price')->width('170px'),
                                TableColumn::make('Margin %')->width('130px'),
                                TableColumn::make('Notes')->width('180px'),
                                TableColumn::make('Pilihan Client')->width('120px'),
                                TableColumn::make('Feedback Client')->width('180px'),
                                TableColumn::make('Status')->width('110px'),
                            ])
                            ->extraItemActions([
                                // Baris bawaan halaman ini satu per KOL, bukan per SOW. Tombol
                                // ini menukar isi daftar ke SOW milik KOL tersebut; kembalinya
                                // lewat tombol di bawah tabel.
                                Action::make('detail_sow')
                                    ->label('Detail SOW')
                                    ->icon('heroicon-m-list-bullet')
                                    ->color('gray')
                                    ->iconButton()
                                    ->tooltip(fn(array $arguments, Repeater $component): string => 'Lihat '
                                        . (self::stateBaris($component, $arguments['item'] ?? '')['jumlah_sow'] ?? 0)
                                        . ' SOW milik KOL ini')
                                    ->visible(function (array $arguments, Repeater $component, $livewire): bool {
                                        $itemUuid = $arguments['item'] ?? null;
                                        if (! $itemUuid || ($livewire->kolFokus ?? null) !== null) {
                                            return false;
                                        }

                                        return (bool) (self::stateBaris($component, $itemUuid)['media_plan_kol_id'] ?? null);
                                    })
                                    ->action(function (array $arguments, Repeater $component, $livewire) {
                                        $kolId = self::stateBaris($component, $arguments['item'] ?? '')['media_plan_kol_id'] ?? null;

                                        if ($kolId) {
                                            $livewire->bukaDetailKol((int) $kolId);
                                        }
                                    }),

                                // Terbitkan SPK untuk KOL di baris ini saja.
                                // Gerbangnya sama dengan tombol batch di header: status budget
                                // harus final (client sudah approve) dan itemnya approved.
                                // Nominalnya tetap SUM(rate_base) semua item approved milik KOL
                                // itu — jadi satu SPK per KOL, bukan per baris SOW.
                                Action::make('terbitkan_spk')
                                    ->label('Terbitkan SPK')
                                    ->icon('heroicon-m-document-check')
                                    ->color('warning')
                                    ->iconButton()
                                    ->tooltip('Terbitkan SPK untuk KOL ini saja')
                                    ->visible(function (array $arguments, Repeater $component, $livewire): bool {
                                        $budget = $livewire->record;
                                        $itemUuid = $arguments['item'] ?? null;

                                        // Halaman Create belum punya record; uuid kosong saat
                                        // Filament merender tombol di luar konteks baris.
                                        if (! $itemUuid || ! $budget
                                            || ! in_array($budget->status, \App\Models\InternalBudget::STATUS_FINAL, true)) {
                                            return false;
                                        }

                                        $state = self::stateBaris($component, $itemUuid);
                                        $kolId = $state['media_plan_kol_id'] ?? null;

                                        return ($state['status'] ?? null) === 'approved'
                                            && $kolId
                                            && ! \App\Models\BvSPK::existsForKol($budget, (int) $kolId);
                                    })
                                    ->requiresConfirmation()
                                    ->modalHeading('Terbitkan SPK untuk KOL Ini')
                                    ->modalIcon('heroicon-o-document-check')
                                    ->modalIconColor('warning')
                                    ->modalDescription(function (array $arguments, Repeater $component, $livewire): string {
                                        $itemUuid = $arguments['item'] ?? null;
                                        if (! $itemUuid) {
                                            return 'Terbitkan SPK untuk KOL ini?';
                                        }

                                        $state = self::stateBaris($component, $itemUuid);
                                        $kolId = (int) ($state['media_plan_kol_id'] ?? 0);
                                        $kol = MediaPlanKol::find($kolId);

                                        $items = $livewire->record->items()
                                            ->where('status', 'approved')
                                            ->where('media_plan_kol_id', $kolId)
                                            ->get();

                                        $nominal = (float) $items->sum(fn($i) => (float) ($i->rate_base ?? 0));

                                        return 'SPK untuk ' . ($kol?->name ?? 'KOL ini') . ' akan dibuat dengan status Draft. '
                                            . 'Semua ' . $items->count() . ' SOW approved miliknya digabung jadi satu SPK, '
                                            . 'nominal Rp ' . number_format($nominal, 0, ',', '.') . ' (netto, di luar pajak). '
                                            . 'NIK & rekening diambil dari Data KOL.';
                                    })
                                    ->modalSubmitActionLabel('Terbitkan')
                                    ->action(function (array $arguments, Repeater $component, $livewire) {
                                        $itemUuid = $arguments['item'] ?? null;
                                        if (! $itemUuid) {
                                            return;
                                        }

                                        $state = self::stateBaris($component, $itemUuid);
                                        $kolId = (int) ($state['media_plan_kol_id'] ?? 0);

                                        $spk = \App\Models\BvSPK::createForKol($livewire->record, $kolId);

                                        if (! $spk) {
                                            Notification::make()
                                                ->title('SPK Tidak Dibuat')
                                                ->body('KOL ini sudah punya SPK di budget ini, atau tidak ada item approved.')
                                                ->warning()
                                                ->send();

                                            return;
                                        }

                                        Notification::make()
                                            ->title('SPK ' . $spk->spk_number . ' Dibuat')
                                            ->body(blank($spk->pihak_kedua_nik) || blank($spk->nomor_rekening)
                                                ? 'NIK/rekening masih kosong — lengkapi Data KOL atau isi manual di SPK.'
                                                : 'Lanjut ke halaman SPK untuk kirim link tanda tangan.')
                                            ->success()
                                            ->send();

                                        return redirect(\App\Filament\Resources\Spks\SpkResource::getUrl('edit', ['record' => $spk]));
                                    }),

                                Action::make('approve_item')
                                    ->label('Approve')
                                    ->icon('heroicon-m-check-circle')
                                    ->color('success')
                                    ->iconButton()
                                    ->tooltip('Approve KOL ini')
                                    ->requiresConfirmation()
                                    ->modalHeading('Approve')
                                    ->modalDescription(fn(array $arguments, Repeater $component): string => 'Approve '
                                        . self::sebutanSasaran($component, $arguments['item'] ?? null)
                                        . '? Statusnya berubah jadi Approved.')
                                    ->modalSubmitActionLabel('Ya, Approve')
                                    ->modalIcon('heroicon-o-check-circle')
                                    ->modalIconColor('success')
                                    ->visible(function (array $arguments, Repeater $component): bool {
                                        $itemUuid = $arguments['item'] ?? null;
                                        if (!$itemUuid)
                                            return true;
                                        $rawState = self::stateBaris($component, $itemUuid);
                                        if (empty(self::sasaranSow($component, $itemUuid)))
                                            return false;
                                        // "campuran" = SOW-nya belum seragam, jadi masih boleh diputuskan.
                                        $status = $rawState['status'] ?? 'pending';
                                        if (in_array($status, ['pending', 'campuran'], true))
                                            return true;
                                        return auth()->user()?->hasRole(['super_admin', 'superadmin', 'Super Admin', 'CEO', 'COO']) ?? false;
                                    })
                                    ->action(function (array $arguments, Repeater $component, $livewire) {
                                        $ids = self::sasaranSow($component, $arguments['item'] ?? null);
                                        if (empty($ids))
                                            return;

                                        // approve() per baris, bukan mass update: hook & observer
                                        // model harus tetap jalan. Paling banyak belasan baris.
                                        $items = InternalBudgetItem::whereIn('id', $ids)->get();
                                        $items->each->approve();

                                        Notification::make()
                                            ->title($items->count() . ' SOW di-approve')
                                            ->body($items->pluck('scope_item')->implode(', '))
                                            ->success()
                                            ->send();

                                        // Catatan: approve item HANYA menyetujui SOW per item (penyesuaian BV).
                                        // Promosi status budget (Review → Approve Client → Approve AM) dan
                                        // pembuatan quotation dilakukan manual oleh BV/AM via dropdown Status
                                        // dan tombol "Generate Quotation". Tidak ada auto-jump di sini.
                                        $livewire->muatUlangItems();
                                    }),

                                Action::make('reject_item')
                                    ->label('Reject')
                                    ->icon('heroicon-m-x-circle')
                                    ->color('danger')
                                    ->iconButton()
                                    ->tooltip('Reject KOL ini')
                                    ->visible(function (array $arguments, Repeater $component): bool {
                                        $itemUuid = $arguments['item'] ?? null;
                                        if (!$itemUuid)
                                            return true;
                                        $rawState = self::stateBaris($component, $itemUuid);
                                        if (empty(self::sasaranSow($component, $itemUuid)))
                                            return false;
                                        $status = $rawState['status'] ?? 'pending';
                                        if (in_array($status, ['pending', 'campuran'], true))
                                            return true;
                                        return auth()->user()?->hasRole(['super_admin', 'superadmin', 'Super Admin', 'CEO', 'COO']) ?? false;
                                    })
                                    ->form([
                                        Textarea::make('rejection_notes')
                                            ->label('Alasan Penolakan')
                                            ->placeholder('Tuliskan alasan penolakan...')
                                            ->required()
                                            ->rows(3),
                                    ])
                                    ->modalHeading('Reject')
                                    ->modalDescription(fn(array $arguments, Repeater $component): string => 'Menolak '
                                        . self::sebutanSasaran($component, $arguments['item'] ?? null) . '.')
                                    ->modalSubmitActionLabel('Reject')
                                    ->action(function (array $arguments, array $data, Repeater $component, $livewire) {
                                        $ids = self::sasaranSow($component, $arguments['item'] ?? null);
                                        if (empty($ids))
                                            return;

                                        $items = InternalBudgetItem::whereIn('id', $ids)->get();
                                        $items->each->reject($data['rejection_notes']);

                                        Notification::make()
                                            ->title($items->count() . ' SOW ditolak')
                                            ->body($items->pluck('scope_item')->implode(', '))
                                            ->warning()
                                            ->send();

                                        $livewire->muatUlangItems();
                                    }),

                                Action::make('nego_item')
                                    ->label('Nego')
                                    ->icon('heroicon-m-chat-bubble-left-right')
                                    ->color('warning')
                                    ->iconButton()
                                    ->tooltip('Tandai Nego & tambah catatan')
                                    ->visible(function (array $arguments, Repeater $component): bool {
                                        $itemUuid = $arguments['item'] ?? null;
                                        if (!$itemUuid)
                                            return true;
                                        $rawState = self::stateBaris($component, $itemUuid);
                                        if (empty(self::sasaranSow($component, $itemUuid)))
                                            return false;
                                        $status = $rawState['status'] ?? 'pending';
                                        if (in_array($status, ['pending', 'nego', 'campuran'], true))
                                            return true;
                                        return auth()->user()?->hasRole(['super_admin', 'superadmin', 'Super Admin', 'CEO', 'COO']) ?? false;
                                    })
                                    ->form([
                                        Textarea::make('nego_notes')
                                            ->label('Catatan Negosiasi')
                                            ->placeholder('Tuliskan catatan atau syarat negosiasi...')
                                            ->required()
                                            ->rows(3),
                                    ])
                                    ->modalHeading('Nego')
                                    ->modalDescription(fn(array $arguments, Repeater $component): string => 'Menandai '
                                        . self::sebutanSasaran($component, $arguments['item'] ?? null)
                                        . ' sebagai "Nego" beserta catatan negosiasinya untuk disampaikan ke client.')
                                    ->modalSubmitActionLabel('Simpan Nego')
                                    ->modalIcon('heroicon-o-chat-bubble-left-right')
                                    ->modalIconColor('warning')
                                    ->action(function (array $arguments, array $data, Repeater $component, $livewire) {
                                        $ids = self::sasaranSow($component, $arguments['item'] ?? null);
                                        if (empty($ids))
                                            return;

                                        $items = InternalBudgetItem::whereIn('id', $ids)->get();
                                        $items->each->nego($data['nego_notes']);

                                        Notification::make()
                                            ->title($items->count() . ' SOW ditandai Nego')
                                            ->body($items->pluck('scope_item')->implode(', '))
                                            ->warning()
                                            ->send();

                                        $livewire->muatUlangItems();
                                    }),

                                // KOL sudah di-ACC client tapi ternyata tidak available / client minta ganti orang.
                                Action::make('replace_kol')
                                    ->label('Ganti KOL')
                                    ->icon('heroicon-m-arrows-right-left')
                                    ->color('info')
                                    ->iconButton()
                                    ->tooltip('Ganti KOL pada SOW ini')
                                    ->visible(function (array $arguments, Repeater $component): bool {
                                        $itemUuid = $arguments['item'] ?? null;
                                        if (!$itemUuid)
                                            return true;

                                        $rawState = self::stateBaris($component, $itemUuid);
                                        // Tetap per SOW, TIDAK ikut jadi per KOL: replaceItemKol()
                                        // membuat satu baris KOL baru untuk tiap item, jadi
                                        // menjalankannya untuk 6 SOW akan melahirkan 6 baris KOL
                                        // pengganti yang orangnya sama. Hanya muncul di mode
                                        // rincian, saat barisnya memang satu SOW.
                                        if (blank($rawState['id'] ?? null))
                                            return false;

                                        // Item yang sudah di-reject/diganti tidak bisa diganti lagi.
                                        return (($rawState['status'] ?? 'pending') !== 'rejected');
                                    })
                                    ->form([
                                        Select::make('data_kol_id')
                                            ->label('KOL Pengganti')
                                            ->options(fn() => \App\Models\DataKol::orderBy('username')
                                                ->get(['id', 'username', 'channel'])
                                                ->mapWithKeys(fn($k) => [$k->id => trim("{$k->username} ({$k->channel})")])
                                                ->all())
                                            ->searchable()
                                            ->required()
                                            ->helperText('Rate diambil dari rate card SOW milik KOL pengganti.'),

                                        Textarea::make('reason')
                                            ->label('Alasan Penggantian')
                                            ->placeholder('Mis. KOL tidak available / client minta ganti')
                                            ->rows(2),
                                    ])
                                    ->modalHeading('Ganti KOL')
                                    ->modalDescription('SOW & qty tetap sama. Baris lama disimpan sebagai Rejected (jejak persetujuan client), baris pengganti dibuat status Pending dan link Review Client dibuka lagi agar client meng-ACC penggantinya.')
                                    ->modalSubmitActionLabel('Ganti KOL')
                                    ->modalIcon('heroicon-o-arrows-right-left')
                                    ->action(function (array $arguments, array $data, Repeater $component, $livewire) {
                                        $itemId = self::stateBarisPenuh($component, $arguments['item'] ?? '')['id'] ?? null;
                                        $item = $itemId ? InternalBudgetItem::find($itemId) : null;
                                        if (!$item)
                                            return;

                                        $newItem = $livewire->record->replaceItemKol(
                                            $item,
                                            (int) $data['data_kol_id'],
                                            $data['reason'] ?? null,
                                        );

                                        Notification::make()
                                            ->title('KOL Diganti')
                                            ->body("SOW \"{$newItem->scope_item}\" kini dipegang {$newItem->mediaPlanKol?->name}. Minta client meng-ACC lewat Link Review Client.")
                                            ->success()
                                            ->send();

                                        // Rate 0 = KOL pengganti belum punya rate card untuk SOW ini.
                                        if ((float) $newItem->rate_base <= 0) {
                                            Notification::make()
                                                ->title('Rate Pengganti Masih 0')
                                                ->body("Rate card SOW \"{$newItem->scope_item}\" belum ada di Database KOL {$newItem->mediaPlanKol?->name}. Lengkapi rate card-nya lalu isi Rate (Base) di baris ini.")
                                                ->warning()
                                                ->persistent()
                                                ->send();
                                        }

                                        $livewire->muatUlangItems();
                                    }),
                            ])
                            ->addable(false)
                            ->deletable(false)
                            ->reorderable(false)
                            ->defaultItems(0),

                        // Paginasi ditaruh DI BAWAH daftarnya: yang dinavigasi isi tabel.
                        // Halaman Create belum punya item tersimpan untuk dipaginasi.
                        Placeholder::make('item_pagination')
                            ->hiddenLabel()
                            ->visible(fn($livewire) => $livewire instanceof EditInternalBudget)
                            ->content(fn() => view('filament.forms.components.item-pagination'))
                            ->columnSpanFull(),
                    ])
                    ->columnSpanFull(),

                // Section 3: Totals
                Section::make('📊 Totals')
                    ->schema([
                        Placeholder::make('total_rate_display')
                            ->label('Total Rate')
                            ->content(fn($record) => 'Rp ' . number_format($record?->total_rate ?? 0, 0, ',', '.')),

                        Placeholder::make('total_cost_display')
                            ->label('Total Cost')
                            ->content(fn($record) => 'Rp ' . number_format($record?->total_mu_pph ?? 0, 0, ',', '.')),

                        Placeholder::make('total_budget_display')
                            ->label('Total Budget')
                            ->content(fn($record) => 'Rp ' . number_format($record?->total_rounded ?? 0, 0, ',', '.')),

                        // Profit & Avg Margin disembunyikan di Media Plan External (data internal saja).

                        // Hidden fields for database
                        TextInput::make('total_rate')->hidden()->numeric()->default(0),
                        TextInput::make('total_subtotal')->hidden()->numeric()->default(0),
                        TextInput::make('total_mu_pph')->hidden()->numeric()->default(0),
                        TextInput::make('total_published_rate')->hidden()->numeric()->default(0),
                        TextInput::make('total_rounded')->hidden()->numeric()->default(0),
                        TextInput::make('average_margin_percent')->hidden()->numeric()->default(0),
                    ])
                    ->columns(3)
                    ->collapsible()
                    ->columnSpanFull(),

                // Section 4: Notes
                Section::make('Notes')
                    ->schema([
                        Textarea::make('notes')
                            ->label('Internal Notes')
                            ->rows(2),

                        Textarea::make('warnings')
                            ->label('⚠️ Warnings')
                            ->rows(2)
                            ->readOnly(),
                    ])
                    ->columns(2)
                    ->collapsible()
                    ->collapsed()
                    ->columnSpanFull(),
            ]);
    }
}
