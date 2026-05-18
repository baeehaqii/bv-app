<?php

namespace App\Filament\Resources\BvQuotations\Schemas;

use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;
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

                                // Group by KOL: KOL yang sama dijadikan 1 baris, SOW digabung
                                $grouped = $items->groupBy(fn($item) => $item->mediaPlanKol?->id ?? $item->scope_item);

                                $rows = $grouped->map(function ($kolItems) {
                                    $kolName = e($kolItems->first()->mediaPlanKol?->name ?? '—');

                                    $sowBadges = $kolItems->map(function ($item) {
                                        $sow = e($item->scope_item ?? '—');
                                        $qty = (int) ($item->qty ?? 1);
                                        $label = $qty > 1 ? "{$sow} ×{$qty}" : $sow;
                                        return "<span style='display:inline-block;margin:2px 3px 2px 0;padding:2px 8px;border-radius:9999px;font-size:11px;font-weight:500;background:#eef2ff;color:#4f46e5;'>{$label}</span>";
                                    })->join('');

                                    $totalPrice = 'Rp ' . number_format((float) $kolItems->sum('rounded'), 0, ',', '.');

                                    return "
                                        <tr style='border-bottom:1px solid #f3f4f6;'>
                                            <td style='padding:10px 12px 10px 0;font-size:13px;font-weight:600;color:#111827;vertical-align:top;white-space:nowrap;'>{$kolName}</td>
                                            <td style='padding:10px 12px;font-size:13px;color:#374151;'>{$sowBadges}</td>
                                            <td style='padding:10px 0 10px 12px;font-size:13px;font-weight:600;color:#111827;text-align:right;white-space:nowrap;vertical-align:top;'>{$totalPrice}</td>
                                        </tr>";
                                })->join('');

                                $total = 'Rp ' . number_format((float) ($budget->total_rounded ?? 0), 0, ',', '.');

                                return new \Illuminate\Support\HtmlString("
                                    <div style='overflow-x:auto;'>
                                        <table style='width:100%;border-collapse:collapse;'>
                                            <thead>
                                                <tr style='border-bottom:2px solid #e5e7eb;'>
                                                    <th style='text-align:left;padding:8px 12px 8px 0;font-size:11px;font-weight:600;text-transform:uppercase;letter-spacing:.05em;color:#6b7280;white-space:nowrap;'>KOL / Creator</th>
                                                    <th style='text-align:left;padding:8px 12px;font-size:11px;font-weight:600;text-transform:uppercase;letter-spacing:.05em;color:#6b7280;'>Scope of Work</th>
                                                    <th style='text-align:right;padding:8px 0 8px 12px;font-size:11px;font-weight:600;text-transform:uppercase;letter-spacing:.05em;color:#6b7280;white-space:nowrap;'>Client Price</th>
                                                </tr>
                                            </thead>
                                            <tbody>{$rows}</tbody>
                                            <tfoot>
                                                <tr style='border-top:2px solid #d1d5db;'>
                                                    <td colspan='2' style='padding-top:10px;font-size:13px;font-weight:600;color:#374151;'>Total Penawaran</td>
                                                    <td style='padding-top:10px;font-size:14px;font-weight:700;text-align:right;color:#111827;'>{$total}</td>
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

                // ── Tanda Tangan Digital ──
                Section::make('✍️ Tanda Tangan Digital')
                    ->description('Upload file tanda tangan (PNG transparan direkomendasikan, maks. 1 MB).')
                    ->columns(3)
                    ->schema([
                        FileUpload::make('ttd_pic_client')
                            ->label('TTD PIC Client')
                            ->image()
                            ->imageEditor()
                            ->disk('public')
                            ->directory('ttd/quotations')
                            ->acceptedFileTypes(['image/png', 'image/jpeg', 'image/webp'])
                            ->maxSize(1024)
                            ->helperText('PNG transparan direkomendasikan.'),

                        FileUpload::make('ttd_sales_bv')
                            ->label('TTD Sales BV')
                            ->image()
                            ->imageEditor()
                            ->disk('public')
                            ->directory('ttd/quotations')
                            ->acceptedFileTypes(['image/png', 'image/jpeg', 'image/webp'])
                            ->maxSize(1024),

                        FileUpload::make('ttd_bd_sales')
                            ->label('TTD BD / Director BV')
                            ->image()
                            ->imageEditor()
                            ->disk('public')
                            ->directory('ttd/quotations')
                            ->acceptedFileTypes(['image/png', 'image/jpeg', 'image/webp'])
                            ->maxSize(1024),
                    ])
                    ->columnSpanFull(),

                TextInput::make('user_id')
                    ->hidden()
                    ->required()
                    ->numeric(),
            ]);
    }
}
