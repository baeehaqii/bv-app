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
    /**
     * Parse any number format to float
     * Handles both:
     * - US format from RawJs $money: "400,000" (comma = thousand)
     * - Indonesia format: "400.000" (dot = thousand)
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
     * Get Progressive PPh Coefficient based on Subtotal amount
     * Based on Excel formula for Gross Up PPH Coefficient
     */
    private static function getPphCoefficient(float $subtotal): float
    {
        return match (true) {
            $subtotal <= 60_000_000 => 0.97,        // PPh 3%
            $subtotal <= 110_000_000 => 0.925,      // PPh 7.5%
            $subtotal <= 400_000_000 => 0.925,      // PPh 7.5%
            $subtotal <= 600_000_000 => 0.9,        // PPh 10%
            $subtotal <= 800_000_000 => 0.89,       // PPh 11%
            $subtotal <= 1_000_000_000 => 0.875,    // PPh 12.5%
            $subtotal <= 1_900_000_000 => 0.83,     // PPh 17%
            $subtotal <= 3_200_000_000 => 0.825,    // PPh 17.5%
            $subtotal <= 5_000_000_000 => 0.79,     // PPh 21%
            default => 0.775,                        // PPh 22.5%
        };
    }

    /**
     * Get Tax Rate based on MU PPh amount
     * Based on Excel formula for Tax percentage
     */
    private static function getTaxRate(float $muPph): float
    {
        return match (true) {
            $muPph <= 60_000_000 => 0.05,           // 5%
            $muPph <= 110_000_000 => 0.15,          // 15%
            $muPph <= 400_000_000 => 0.25,          // 25%
            $muPph <= 5_000_000_000 => 0.30,        // 30%
            default => 0.35,                         // 35%
        };
    }

    /**
     * Calculate and set all item values
     * Formula based on Excel Internal Budget calculation
     */
    private static function calculateItemValues(callable $get, callable $set): void
    {
        $qty = (int) ($get('qty') ?? 1);
        $rateBaseRaw = $get('rate_base') ?? 0;
        $rateBase = self::parseNumber($rateBaseRaw);

        // Debug logging
        \Illuminate\Support\Facades\Log::info('📊 Budget Calculation', [
            'rate_base_raw' => $rateBaseRaw,
            'rate_base_parsed' => $rateBase,
            'qty' => $qty,
        ]);

        if ($rateBase <= 0) {
            $set('subtotal', 0);
            $set('pph_coefficient', 0);
            $set('tax_rate', 0);
            $set('mu_pph', 0);
            $set('mu_target', 0);
            $set('published_rate', 0);
            $set('rounded', 0);
            $set('actual_margin_percent', 0);
            return;
        }

        // Step 1: Calculate subtotal (Qty × Rate)
        $subtotal = $qty * $rateBase;

        // Step 2: Get PPh Coefficient from selected Master PPH
        $masterPphId = $get('master_pph_id');
        if ($masterPphId) {
            $masterPph = \App\Models\MasterPph::find($masterPphId);
            if ($masterPph) {
                $pphCoefficient = $masterPph->getCalculatedCoefficient();
            } else {
                $pphCoefficient = 0.975; // Fallback to Pribadi
            }
        } else {
            $pphCoefficient = 0.975; // Fallback to Pribadi
        }

        // Step 3: MU PPh (Real Cost) = Subtotal / Coefficient
        $muPph = $subtotal / $pphCoefficient;

        // Step 4: Get Tax Rate for display - use flexible tax if enabled
        $useFlexibleTax = $get('use_flexible_tax') ?? false;
        if ($useFlexibleTax) {
            $taxRateOverride = $get('tax_rate_percent');
            if ($taxRateOverride !== null && $taxRateOverride !== '') {
                $taxRate = self::parseNumber($taxRateOverride) / 100; // Convert to decimal
            } else {
                $taxRate = self::getTaxRate($muPph); // Fallback to auto if toggle enabled but no value
            }
        } else {
            // Auto calculate based on MU PPh
            $taxRate = self::getTaxRate($muPph);
        }

        // Step 5: Calculate target margin
        // Priority: 1. Media Plan Global Margin, 2. Item Flexible Margin, 3. Auto from MasterMargin
        $targetMargin = null;

        // First, try to get margin from Media Plan (global setting)
        $mediaPlanId = $get('../../media_plan_id'); // Navigate up from item to parent form
        if ($mediaPlanId) {
            $mediaPlan = \App\Models\MediaPlan::find($mediaPlanId);
            if ($mediaPlan && $mediaPlan->use_global_margin && $mediaPlan->margin_type === 'custom') {
                // Use custom margin from Media Plan
                $targetMargin = (float) $mediaPlan->margin_percent;
                \Illuminate\Support\Facades\Log::info('📊 Using Media Plan Global Margin', [
                    'media_plan_id' => $mediaPlanId,
                    'margin_type' => $mediaPlan->margin_type,
                    'margin_percent' => $targetMargin,
                ]);
            }
        }

        // If no global margin, check item-level flexible margin
        if ($targetMargin === null) {
            $useFlexibleMargin = $get('use_flexible_margin') ?? false;
            if ($useFlexibleMargin) {
                $marginOverride = $get('margin_percent_override');
                if ($marginOverride !== null && $marginOverride !== '') {
                    $targetMargin = self::parseNumber($marginOverride);
                } else {
                    $targetMargin = 30.0; // Fallback to 30% if toggle enabled but no value
                }
            }
        }

        // If still no margin, use auto calculation from MasterMargin
        if ($targetMargin === null) {
            $targetMargin = \App\Models\MasterMargin::getMarginForAmount($subtotal);
        }

        // Step 6: MU Target = MU PPh / (1 - margin/100)
        $marginDecimal = $targetMargin / 100;
        if ($marginDecimal >= 1) {
            $muTarget = $muPph; // Prevent division by zero
        } else {
            $muTarget = $muPph / (1 - $marginDecimal);
        }

        // Step 7: Published Rate = MU Target (can be manually adjusted)
        $publishedRate = $muTarget;

        // Step 8: Rounded = ROUNDUP to nearest 100,000
        $rounded = ceil($publishedRate / 100000) * 100000;

        // Step 9: Actual Margin = (Rounded - MU PPh) / Rounded × 100
        $actualMargin = $rounded > 0 ? (($rounded - $muPph) / $rounded) * 100 : 0;

        // Format helper for money display (US format: 1,000,000)
        $formatMoney = fn($value) => number_format(round($value), 0, '.', ',');

        // Set values - format money fields with commas for mask display
        $set('subtotal', round($subtotal));
        $set('pph_coefficient', $pphCoefficient);
        $set('tax_rate', $taxRate * 100); // Display as percentage
        $set('mu_pph', $formatMoney($muPph));
        $set('mu_target', round($muTarget));
        $set('published_rate', $formatMoney($publishedRate));
        $set('rounded', $formatMoney($rounded));
        $set('actual_margin_percent', round($actualMargin, 2));

        // Store target margin for database
        if (empty($get('target_margin_percent'))) {
            $set('target_margin_percent', $targetMargin);
        }

        // Log final values
        \Illuminate\Support\Facades\Log::info('📊 Budget Result', [
            'subtotal' => $subtotal,
            'pph_coefficient' => $pphCoefficient,
            'use_flexible_tax' => $useFlexibleTax,
            'tax_rate_display' => $taxRate * 100, // Display as percentage
            'use_flexible_margin' => $useFlexibleMargin,
            'target_margin' => $targetMargin,
            'mu_pph' => round($muPph),
            'mu_target' => round($muTarget),
            'rounded' => round($rounded),
            'margin' => round($actualMargin, 2),
        ]);
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
                                Select::make('kol_status')
                                    ->label('Status KOL')
                                    ->options(MediaPlanKolStatus::toArrayExternal())
                                    ->searchable()
                                    ->native(false)
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
