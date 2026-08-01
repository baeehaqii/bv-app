<?php

namespace App\Filament\Resources\InternalBudgets\Schemas;

use App\Enums\MediaPlanKolStatus;
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
                        Placeholder::make('budget_items_sticky_css')
                            ->label('')
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
                                </style>
                            '))
                            ->columnSpanFull(),

                        Repeater::make('items')
                            ->relationship()
                            ->extraAttributes(['id' => 'ib-budget-items'])
                            ->schema([
                                Select::make('media_plan_kol_id')
                                    ->label('KOL')
                                    ->options(function ($livewire) {
                                        $mediaPlanId = $livewire->record?->media_plan_id;
                                        if (!$mediaPlanId)
                                            return [];
                                        return MediaPlanKol::where('media_plan_id', $mediaPlanId)
                                            ->orderBy('name')
                                            ->pluck('name', 'id')
                                            ->toArray();
                                    })
                                    ->disabled(),

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
                                    }),

                                TextInput::make('scope_item')
                                    ->label('Scope Item')
                                    ->disabled(),

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
                                        ];
                                        $labels = [
                                            'approved' => 'Approved',
                                            'rejected' => 'Rejected',
                                            'nego'     => 'Nego',
                                            'pending'  => 'Pending',
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

                                        $state = $component->getRawItemState($itemUuid);
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

                                        $state = $component->getRawItemState($itemUuid);
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

                                        $state = $component->getRawItemState($itemUuid);
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
                                    ->tooltip('Approve item ini')
                                    ->requiresConfirmation()
                                    ->modalHeading('Approve SOW Item')
                                    ->modalDescription(function (array $arguments, Repeater $component): string {
                                        $itemUuid = $arguments['item'] ?? null;
                                        if (!$itemUuid)
                                            return 'Apakah Anda yakin ingin meng-approve item ini?';
                                        $rawState = $component->getRawItemState($itemUuid);
                                        $scopeItem = $rawState['scope_item'] ?? 'item ini';
                                        return "Apakah Anda yakin ingin meng-approve SOW \"{$scopeItem}\"? Tindakan ini akan mengubah status item menjadi Approved.";
                                    })
                                    ->modalSubmitActionLabel('Ya, Approve')
                                    ->modalIcon('heroicon-o-check-circle')
                                    ->modalIconColor('success')
                                    ->visible(function (array $arguments, Repeater $component): bool {
                                        $itemUuid = $arguments['item'] ?? null;
                                        if (!$itemUuid)
                                            return true;
                                        $rawState = $component->getRawItemState($itemUuid);
                                        $status = $rawState['status'] ?? 'pending';
                                        if ($status === 'pending')
                                            return true;
                                        return auth()->user()?->hasRole(['super_admin', 'superadmin', 'Super Admin', 'CEO', 'COO']) ?? false;
                                    })
                                    ->action(function (array $arguments, Repeater $component, $livewire) {
                                        $itemUuid = $arguments['item'] ?? null;
                                        if (!$itemUuid)
                                            return;

                                        // Get the actual database ID from form state
                                        $itemState = $component->getItemState($itemUuid);
                                        $itemId = $itemState['id'] ?? null;
                                        if (!$itemId)
                                            return;

                                        $item = InternalBudgetItem::find($itemId);
                                        if (!$item)
                                            return;

                                        $item->approve();

                                        Notification::make()
                                            ->title('Item Approved')
                                            ->body("Item {$item->scope_item} berhasil di-approve.")
                                            ->success()
                                            ->send();

                                        // Catatan: approve item HANYA menyetujui SOW per item (penyesuaian BV).
                                        // Promosi status budget (Review → Approve Client → Approve AM) dan
                                        // pembuatan quotation dilakukan manual oleh BV/AM via dropdown Status
                                        // dan tombol "Generate Quotation". Tidak ada auto-jump di sini.
                                        $component->clearCachedExistingRecords();
                                        $livewire->refreshFormData(['items']);
                                    }),

                                Action::make('reject_item')
                                    ->label('Reject')
                                    ->icon('heroicon-m-x-circle')
                                    ->color('danger')
                                    ->iconButton()
                                    ->tooltip('Reject item ini')
                                    ->visible(function (array $arguments, Repeater $component): bool {
                                        $itemUuid = $arguments['item'] ?? null;
                                        if (!$itemUuid)
                                            return true;
                                        $rawState = $component->getRawItemState($itemUuid);
                                        $status = $rawState['status'] ?? 'pending';
                                        if ($status === 'pending')
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
                                    ->modalHeading('Reject Item')
                                    ->modalSubmitActionLabel('Reject')
                                    ->action(function (array $arguments, array $data, Repeater $component, $livewire) {
                                        $itemUuid = $arguments['item'] ?? null;
                                        if (!$itemUuid)
                                            return;

                                        $itemState = $component->getItemState($itemUuid);
                                        $itemId = $itemState['id'] ?? null;
                                        if (!$itemId)
                                            return;

                                        $item = InternalBudgetItem::find($itemId);
                                        if (!$item)
                                            return;

                                        $item->reject($data['rejection_notes']);

                                        Notification::make()
                                            ->title('Item Rejected')
                                            ->body("Item {$item->scope_item} ditolak.")
                                            ->warning()
                                            ->send();

                                        $component->clearCachedExistingRecords();
                                        $livewire->refreshFormData(['items']);
                                    }),

                                Action::make('nego_item')
                                    ->label('Nego')
                                    ->icon('heroicon-m-chat-bubble-left-right')
                                    ->color('warning')
                                    ->iconButton()
                                    ->tooltip('Tandai sebagai Nego & tambah catatan')
                                    ->visible(function (array $arguments, Repeater $component): bool {
                                        $itemUuid = $arguments['item'] ?? null;
                                        if (!$itemUuid)
                                            return true;
                                        $rawState = $component->getRawItemState($itemUuid);
                                        $status = $rawState['status'] ?? 'pending';
                                        if (in_array($status, ['pending', 'nego']))
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
                                    ->modalHeading('Nego Item')
                                    ->modalDescription('Tandai item ini sebagai "Nego" dan tambahkan catatan negosiasi untuk disampaikan ke client.')
                                    ->modalSubmitActionLabel('Simpan Nego')
                                    ->modalIcon('heroicon-o-chat-bubble-left-right')
                                    ->modalIconColor('warning')
                                    ->action(function (array $arguments, array $data, Repeater $component, $livewire) {
                                        $itemUuid = $arguments['item'] ?? null;
                                        if (!$itemUuid)
                                            return;

                                        $itemState = $component->getItemState($itemUuid);
                                        $itemId = $itemState['id'] ?? null;
                                        if (!$itemId)
                                            return;

                                        $item = InternalBudgetItem::find($itemId);
                                        if (!$item)
                                            return;

                                        $item->nego($data['nego_notes']);

                                        Notification::make()
                                            ->title('Item Ditandai Nego')
                                            ->body("Item \"{$item->scope_item}\" ditandai sebagai Nego.")
                                            ->warning()
                                            ->send();

                                        $component->clearCachedExistingRecords();
                                        $livewire->refreshFormData(['items']);
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

                                        // Item yang sudah di-reject/diganti tidak bisa diganti lagi.
                                        return (($component->getRawItemState($itemUuid)['status'] ?? 'pending') !== 'rejected');
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
                                        $itemId = $component->getItemState($arguments['item'] ?? '')['id'] ?? null;
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

                                        $component->clearCachedExistingRecords();
                                        $livewire->refreshFormData(['items']);
                                    }),
                            ])
                            ->addable(false)
                            ->deletable(false)
                            ->reorderable(false)
                            ->defaultItems(0),
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
