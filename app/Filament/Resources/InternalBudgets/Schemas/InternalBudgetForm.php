<?php

namespace App\Filament\Resources\InternalBudgets\Schemas;

use Filament\Schemas\Schema;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Schemas\Components\Section;

class InternalBudgetForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Scope of Work')
                    ->columnSpanFull()
                    ->schema([
                        TextInput::make('scopeofwork_item')
                            ->label('Item')
                            ->readOnly()
                            ->dehydrated(),

                        TextInput::make('qty')
                            ->label('Qty')
                            ->numeric()
                            ->default(1)
                            ->live(onBlur: true)
                            ->afterStateUpdated(function (callable $set, callable $get) {
                                self::recalculateSubtotal($set, $get);
                            }),
                    ])->columns(2),

                Section::make('Rate & Cost')
                    ->columnSpanFull()
                    ->schema([
                        TextInput::make('rate')
                            ->label('Rate (Base)')
                            ->numeric()
                            ->prefix('Rp ')
                            ->live(onBlur: true)
                            ->afterStateUpdated(function (callable $set, callable $get) {
                                self::recalculateSubtotal($set, $get);
                                self::recalculateMuPph($set, $get);
                                self::recalculateMuTarget($set, $get);
                            })
                            ->helperText('Modal/HPP - Bayar ke Vendor'),

                        TextInput::make('subtotal')
                            ->label('Subtotal Rate')
                            ->numeric()
                            ->prefix('Rp ')
                            ->readOnly()
                            ->dehydrated(),

                        TextInput::make('gross_up_coeff')
                            ->label('Gross Up PPh Coeff')
                            ->numeric()
                            ->default(0.97)
                            ->readOnly()
                            ->dehydrated()
                            ->helperText('Constant: 0.97 (PPh 3%)'),

                        TextInput::make('tax')
                            ->label('Tax')
                            ->numeric()
                            ->default(0.05)
                            ->readOnly()
                            ->dehydrated()
                            ->helperText('Reference only (5%)'),

                        TextInput::make('mu_pph')
                            ->label('MU PPh (Real Cost)')
                            ->numeric()
                            ->prefix('Rp ')
                            ->readOnly()
                            ->dehydrated()
                            ->helperText('Real cost = Rate / 0.97'),
                    ])->columns(3),

                Section::make('Pricing Strategy')
                    ->columnSpanFull()
                    ->schema([
                        TextInput::make('mu_target')
                            ->label('MU (Target)')
                            ->numeric()
                            ->prefix('Rp ')
                            ->readOnly()
                            ->dehydrated()
                            ->helperText('Guideline price (40% margin target)'),

                        TextInput::make('published_rate')
                            ->label('Published Rate')
                            ->numeric()
                            ->prefix('Rp ')
                            ->live(onBlur: true)
                            ->afterStateUpdated(function (callable $set, callable $get) {
                                self::recalculateRounded($set, $get);
                                self::recalculateMargin($set, $get);
                            })
                            ->helperText('Manual input - Harga jual final'),

                        TextInput::make('rounded')
                            ->label('Rounded')
                            ->numeric()
                            ->prefix('Rp ')
                            ->readOnly()
                            ->dehydrated()
                            ->helperText('Pembulatan ke ratusan ribu'),

                        TextInput::make('margin_percent')
                            ->label('Margin %')
                            ->numeric()
                            ->suffix('%')
                            ->readOnly()
                            ->dehydrated()
                            ->extraAttributes(function ($state) {
                                if ($state && $state < 30) {
                                    return ['style' => 'color: #dc2626; font-weight: bold;'];
                                }
                                return [];
                            })
                            ->helperText('Warning: Red if < 30%'),
                    ])->columns(2),
            ]);
    }

    private static function recalculateSubtotal(callable $set, callable $get): void
    {
        $qty = (int) ($get('qty') ?? 1);
        $rate = (int) ($get('rate') ?? 0);
        $subtotal = $qty * $rate;
        $set('subtotal', $subtotal);
    }

    private static function recalculateMuPph(callable $set, callable $get): void
    {
        $rate = (int) ($get('rate') ?? 0);
        $grossUpCoeff = (float) ($get('gross_up_coeff') ?? 0.97);

        if ($rate > 0) {
            $muPph = $rate / $grossUpCoeff;
            $set('mu_pph', intval($muPph));
        } else {
            $set('mu_pph', 0);
        }
    }

    private static function recalculateMuTarget(callable $set, callable $get): void
    {
        $muPph = (int) ($get('mu_pph') ?? 0);
        $marginTarget = 0.40; // 40% target margin

        if ($muPph > 0) {
            $muTarget = $muPph / (1 - $marginTarget);
            $set('mu_target', intval($muTarget));
        } else {
            $set('mu_target', 0);
        }
    }

    private static function recalculateRounded(callable $set, callable $get): void
    {
        $publishedRate = (int) ($get('published_rate') ?? 0);

        if ($publishedRate > 0) {
            $rounded = ceil($publishedRate / 100000) * 100000;
            $set('rounded', intval($rounded));
        } else {
            $set('rounded', 0);
        }
    }

    private static function recalculateMargin(callable $set, callable $get): void
    {
        $rounded = (int) ($get('rounded') ?? 0);
        $muPph = (int) ($get('mu_pph') ?? 0);

        if ($rounded > 0) {
            $profit = $rounded - $muPph;
            $marginPercent = ($profit / $rounded) * 100;
            $set('margin_percent', number_format($marginPercent, 2));
        } else {
            $set('margin_percent', 0);
        }
    }
}
