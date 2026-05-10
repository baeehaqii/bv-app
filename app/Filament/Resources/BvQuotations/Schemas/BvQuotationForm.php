<?php

namespace App\Filament\Resources\BvQuotations\Schemas;

use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class BvQuotationForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                // ── SOW per KOL (read-only, from approved InternalBudgetItems) ──
                Section::make('📋 SOW per KOL')
                    ->description('Item yang telah di-approve dari Media Plan External.')
                    ->schema([
                        Placeholder::make('approved_items_table')
                            ->label('')
                            ->content(function ($record): \Illuminate\Support\HtmlString {
                                $budget = $record?->internalBudget;
                                if (!$budget) {
                                    return new \Illuminate\Support\HtmlString(
                                        '<p class="text-sm text-gray-400 italic">Tidak ada budget yang terhubung ke quotation ini.</p>'
                                    );
                                }

                                $items = $budget->items()
                                    ->where('status', 'approved')
                                    ->with('mediaPlanKol')
                                    ->orderBy('sort_order')
                                    ->get();

                                if ($items->isEmpty()) {
                                    return new \Illuminate\Support\HtmlString(
                                        '<p class="text-sm text-gray-400 italic">Belum ada item yang di-approve.</p>'
                                    );
                                }

                                $rows = $items->map(function ($item) {
                                    $kol = e($item->mediaPlanKol?->name ?? '—');
                                    $scope = e($item->scope_item ?? '—');
                                    $qty = (int) ($item->qty ?? 1);
                                    $price = 'Rp ' . number_format((float) ($item->rounded ?? 0), 0, ',', '.');

                                    return "
                                        <tr class='border-b border-gray-100 dark:border-gray-700'>
                                            <td class='py-2 pr-4 text-sm text-gray-800 dark:text-gray-200'>{$kol}</td>
                                            <td class='py-2 pr-4 text-sm text-gray-800 dark:text-gray-200'>{$scope}</td>
                                            <td class='py-2 pr-4 text-sm text-center text-gray-600 dark:text-gray-400'>{$qty}</td>
                                            <td class='py-2 text-sm text-right font-medium text-gray-800 dark:text-gray-200'>{$price}</td>
                                        </tr>";
                                })->join('');

                                $total = 'Rp ' . number_format((float) ($budget->total_rounded ?? 0), 0, ',', '.');

                                return new \Illuminate\Support\HtmlString("
                                    <div class='overflow-x-auto'>
                                        <table class='w-full border-collapse'>
                                            <thead>
                                                <tr class='border-b-2 border-gray-200 dark:border-gray-600'>
                                                    <th class='text-left py-2 pr-4 text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400'>KOL</th>
                                                    <th class='text-left py-2 pr-4 text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400'>Scope Item (SOW)</th>
                                                    <th class='text-center py-2 pr-4 text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400'>Qty</th>
                                                    <th class='text-right py-2 text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400'>Client Price</th>
                                                </tr>
                                            </thead>
                                            <tbody>{$rows}</tbody>
                                            <tfoot>
                                                <tr class='border-t-2 border-gray-300 dark:border-gray-500'>
                                                    <td colspan='3' class='pt-3 text-sm font-semibold text-gray-700 dark:text-gray-300'>Total</td>
                                                    <td class='pt-3 text-sm font-bold text-right text-gray-900 dark:text-white'>{$total}</td>
                                                </tr>
                                            </tfoot>
                                        </table>
                                    </div>
                                ");
                            })
                            ->columnSpanFull(),
                    ])
                    ->visible(fn($record) => $record?->internalBudget !== null)
                    ->columnSpanFull(),

                // ── Quotation Details ──
                Section::make('📄 Detail Quotation')
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                TextInput::make('quotation_number')
                                    ->label('Nomor Quotation')
                                    ->required(),

                                Select::make('status')
                                    ->label('Status')
                                    ->options([
                                        'draft' => 'Draft',
                                        'sent' => 'Sent',
                                        'accepted' => 'Accepted',
                                        'rejected' => 'Rejected',
                                        'expired' => 'Expired',
                                    ])
                                    ->default('draft')
                                    ->required()
                                    ->native(false),

                                DatePicker::make('quotation_date')
                                    ->label('Tanggal Quotation')
                                    ->required()
                                    ->native(false)
                                    ->displayFormat('d M Y'),

                                DatePicker::make('expiry_date')
                                    ->label('Berlaku Hingga')
                                    ->native(false)
                                    ->displayFormat('d M Y'),

                                TextInput::make('client_name')
                                    ->label('Nama Client')
                                    ->required()
                                    ->columnSpan(1),

                                TextInput::make('client_email')
                                    ->label('Email Client')
                                    ->email()
                                    ->columnSpan(1),
                            ]),

                        Textarea::make('client_address')
                            ->label('Alamat Client')
                            ->rows(2)
                            ->columnSpanFull(),
                    ])
                    ->columnSpanFull(),

                // ── Finansial ──
                Section::make('💰 Finansial')
                    ->schema([
                        Grid::make(3)
                            ->schema([
                                TextInput::make('subtotal')
                                    ->label('Subtotal')
                                    ->prefix('Rp')
                                    ->required()
                                    ->numeric()
                                    ->default(0.0),

                                TextInput::make('discount')
                                    ->label('Diskon')
                                    ->prefix('Rp')
                                    ->required()
                                    ->numeric()
                                    ->default(0.0),

                                TextInput::make('total_amount')
                                    ->label('Total')
                                    ->prefix('Rp')
                                    ->required()
                                    ->numeric()
                                    ->default(0.0),
                            ]),
                    ])
                    ->columnSpanFull(),

                // ── Catatan ──
                Section::make('📝 Catatan')
                    ->schema([
                        Textarea::make('notes')
                            ->label('Catatan Internal')
                            ->rows(3)
                            ->columnSpanFull(),

                        Textarea::make('terms_conditions')
                            ->label('Syarat & Ketentuan')
                            ->rows(4)
                            ->columnSpanFull(),
                    ])
                    ->columnSpanFull(),

                TextInput::make('user_id')
                    ->hidden()
                    ->required()
                    ->numeric(),
            ]);
    }
}
