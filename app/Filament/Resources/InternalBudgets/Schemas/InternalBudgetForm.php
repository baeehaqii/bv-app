<?php

namespace App\Filament\Resources\InternalBudgets\Schemas;

use App\Models\MediaPlanKol;
use Filament\Schemas\Schema;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Repeater;
use Filament\Schemas\Components\Section;
use Filament\Support\RawJs;

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

        // Step 2: Get Progressive PPh Coefficient based on Subtotal
        $pphCoefficient = self::getPphCoefficient($subtotal);

        // Step 3: MU PPh (Real Cost) = Subtotal / Coefficient
        $muPph = $subtotal / $pphCoefficient;

        // Step 4: Get Tax Rate for display
        $taxRate = self::getTaxRate($muPph);

        // Step 5: MU Target = MU PPh / 0.7 (fixed ~30% margin target)
        $muTarget = $muPph / 0.7;

        // Step 6: Published Rate = MU Target (can be manually adjusted)
        $publishedRate = $muTarget;

        // Step 7: Rounded = ROUNDUP to nearest 100,000
        $rounded = ceil($publishedRate / 100000) * 100000;

        // Step 8: Actual Margin = (Rounded - MU PPh) / Rounded × 100
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

        // Log final values
        \Illuminate\Support\Facades\Log::info('📊 Budget Result', [
            'subtotal' => $subtotal,
            'pph_coefficient' => $pphCoefficient,
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
                            ->options([
                                'draft' => 'Draft',
                                'pending' => 'Pending Review',
                                'approved' => 'Approved',
                                'rejected' => 'Rejected',
                            ])
                            ->default('draft')
                            ->required(),

                        Placeholder::make('summary_info')
                            ->label('💰 Quick Summary')
                            ->content(function ($record) {
                                if (!$record)
                                    return 'Simpan dulu untuk melihat summary';

                                $cost = number_format($record->total_mu_pph ?? 0, 0, ',', '.');
                                $budget = number_format($record->total_rounded ?? 0, 0, ',', '.');
                                $profit = number_format(($record->total_rounded ?? 0) - ($record->total_mu_pph ?? 0), 0, ',', '.');
                                $margin = number_format($record->average_margin_percent ?? 0, 2, ',', '.');

                                return "Cost: Rp {$cost} | Budget: Rp {$budget} | Profit: Rp {$profit} | Margin: {$margin}%";
                            })
                            ->columnSpanFull(),
                    ])
                    ->columns(2)
                    ->columnSpanFull(),

                // Section 2: BUDGET ITEMS
                Section::make('💰 Budget Items')
                    ->description('Input Rate (Base) = harga modal. Kalkulasi otomatis!')
                    ->schema([
                        Repeater::make('items')
                            ->relationship()
                            ->schema([
                                Select::make('media_plan_kol_id')
                                    ->label('KOL')
                                    ->options(function ($livewire) {
                                        $mediaPlanId = $livewire->record?->media_plan_id;
                                        if (!$mediaPlanId)
                                            return [];
                                        return MediaPlanKol::where('media_plan_id', $mediaPlanId)
                                            ->pluck('name', 'id')
                                            ->toArray();
                                    })
                                    ->searchable()
                                    ->placeholder('Pilih KOL')
                                    ->live()
                                    ->afterStateUpdated(function ($state, callable $set) {
                                        // Auto-fill first scope item when KOL is selected
                                        if ($state) {
                                            $kol = MediaPlanKol::find($state);
                                            if ($kol && !empty($kol->scope_items)) {
                                                // Set first scope item as default
                                                $set('scope_item', $kol->scope_items[0] ?? null);
                                            }
                                        }
                                    }),

                                Select::make('scope_item')
                                    ->label('Scope Item')
                                    ->placeholder('Pilih scope item')
                                    ->options(function (callable $get) {
                                        $kolId = $get('media_plan_kol_id');
                                        if (!$kolId) {
                                            return [];
                                        }

                                        $kol = MediaPlanKol::find($kolId);
                                        if (!$kol || empty($kol->scope_items)) {
                                            return [];
                                        }

                                        // Convert scope_items array to options
                                        return collect($kol->scope_items)
                                            ->mapWithKeys(fn($item) => [$item => $item])
                                            ->toArray();
                                    })
                                    ->required()
                                    ->searchable()
                                    ->helperText('Pilih dari scope items KOL'),

                                TextInput::make('qty')
                                    ->label('Qty')
                                    ->numeric()
                                    ->default(1)
                                    ->minValue(1)
                                    ->required()
                                    ->live(debounce: 500)
                                    ->afterStateUpdated(fn($get, $set) => self::calculateItemValues($get, $set)),

                                // RATE BASE - with money mask
                                // Note: Don't use ->numeric() here as it conflicts with $money mask
                                TextInput::make('rate_base')
                                    ->label('💵 Rate (Base)')
                                    ->helperText('Harga modal dari vendor')
                                    ->placeholder('Masukan Nominal')
                                    ->required()
                                    ->prefix('Rp')
                                    ->mask(RawJs::make('$money($input)'))
                                    ->dehydrateStateUsing(fn($state) => self::parseNumber($state))
                                    ->live(debounce: 500)
                                    ->afterStateUpdated(fn($get, $set) => self::calculateItemValues($get, $set)),

                                // PPh Coefficient - auto calculated based on subtotal
                                TextInput::make('pph_coefficient')
                                    ->label('Koefisien PPh')
                                    ->helperText('Auto (0.97-0.775)')
                                    ->readOnly()
                                    ->dehydrated(false), // Don't save to DB

                                // Tax Rate - auto calculated based on MU PPh
                                TextInput::make('tax_rate')
                                    ->label('Tax Rate')
                                    ->suffix('%')
                                    ->helperText('Auto (5%-35%)')
                                    ->readOnly()
                                    ->dehydrated(false), // Don't save to DB

                                // MU PPh - with money mask (read-only output)
                                TextInput::make('mu_pph')
                                    ->label('🔴 MU PPh (Cost)')
                                    ->helperText('Biaya keluar real')
                                    ->prefix('Rp')
                                    ->mask(RawJs::make('$money($input)'))
                                    ->dehydrateStateUsing(fn($state) => self::parseNumber($state))
                                    ->readOnly(),

                                // Published Rate - with money mask (can be manually adjusted)
                                TextInput::make('published_rate')
                                    ->label('� Published Rate')
                                    ->helperText('MU PPh / 0.7 (bisa diedit)')
                                    ->prefix('Rp')
                                    ->mask(RawJs::make('$money($input)'))
                                    ->dehydrateStateUsing(fn($state) => self::parseNumber($state))
                                    ->live(debounce: 500)
                                    ->afterStateUpdated(function ($state, callable $get, callable $set) {
                                        // Recalculate rounded and margin when published rate is manually changed
                                        $publishedRate = self::parseNumber($state);
                                        $muPph = self::parseNumber($get('mu_pph'));
                                        
                                        if ($publishedRate > 0) {
                                            $rounded = ceil($publishedRate / 100000) * 100000;
                                            $margin = (($rounded - $muPph) / $rounded) * 100;
                                            
                                            $formatMoney = fn($value) => number_format(round($value), 0, '.', ',');
                                            $set('rounded', $formatMoney($rounded));
                                            $set('actual_margin_percent', round($margin, 2));
                                        }
                                    }),

                                // Rounded - with money mask (read-only output)
                                TextInput::make('rounded')
                                    ->label('🟢 Rounded (Client)')
                                    ->helperText('Harga ke client')
                                    ->prefix('Rp')
                                    ->mask(RawJs::make('$money($input)'))
                                    ->dehydrateStateUsing(fn($state) => self::parseNumber($state))
                                    ->readOnly(),

                                // Margin - percentage
                                TextInput::make('actual_margin_percent')
                                    ->label('Margin %')
                                    ->suffix('%')
                                    ->dehydrateStateUsing(fn($state) => self::parseNumber($state))
                                    ->readOnly(),

                                // Hidden fields for database
                                TextInput::make('subtotal')
                                    ->hidden()
                                    ->numeric()
                                    ->dehydrateStateUsing(fn($state) => self::parseNumber($state)),
                                TextInput::make('mu_target')
                                    ->hidden()
                                    ->numeric()
                                    ->dehydrateStateUsing(fn($state) => self::parseNumber($state)),
                                TextInput::make('sort_order')
                                    ->hidden()
                                    ->numeric()
                                    ->default(0),

                                Textarea::make('notes')
                                    ->label('Notes')
                                    ->rows(1)
                                    ->columnSpanFull(),
                            ])
                            ->columns(3)
                            ->collapsible()
                            ->cloneable()
                            ->defaultItems(0)
                            ->itemLabel(function (array $state): ?string {
                                $item = $state['scope_item'] ?? 'New Item';
                                $rounded = self::parseNumber($state['rounded'] ?? 0);
                                if ($rounded > 0) {
                                    return $item . ' → Rp ' . number_format($rounded, 0, ',', '.');
                                }
                                return $item;
                            })
                            ->addActionLabel('➕ Tambah Item')
                            ->reorderableWithButtons(),
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

                        Placeholder::make('profit_display')
                            ->label('Profit')
                            ->content(function ($record) {
                                $profit = ($record?->total_rounded ?? 0) - ($record?->total_mu_pph ?? 0);
                                return 'Rp ' . number_format($profit, 0, ',', '.');
                            }),

                        Placeholder::make('margin_display')
                            ->label('Avg Margin')
                            ->content(fn($record) => number_format($record?->average_margin_percent ?? 0, 2, ',', '.') . '%'),

                        // Hidden fields for database
                        TextInput::make('total_rate')->hidden()->numeric()->default(0),
                        TextInput::make('total_subtotal')->hidden()->numeric()->default(0),
                        TextInput::make('total_mu_pph')->hidden()->numeric()->default(0),
                        TextInput::make('total_published_rate')->hidden()->numeric()->default(0),
                        TextInput::make('total_rounded')->hidden()->numeric()->default(0),
                        TextInput::make('average_margin_percent')->hidden()->numeric()->default(0),
                    ])
                    ->columns(5)
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
