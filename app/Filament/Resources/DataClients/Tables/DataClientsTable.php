<?php

namespace App\Filament\Resources\DataClients\Tables;

use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Support\HtmlString;

class DataClientsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('type')
                    ->label('Tipe')
                    ->badge()
                    ->color(fn(string $state): string => match ($state) {
                        'agency' => 'warning',
                        'direct' => 'success',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn(string $state): string => match ($state) {
                        'agency' => 'Agency',
                        'direct' => 'Direct Brand',
                        default => $state,
                    }),

                TextColumn::make('nama_brand')
                    ->label('Nama Brand')
                    ->searchable()
                    ->sortable()
                    ->weight('semibold'),

                TextColumn::make('agency_name')
                    ->label('Agency')
                    ->placeholder('-')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: false),

                TextColumn::make('produk')
                    ->label('Produk')
                    ->searchable(),

                TextColumn::make('category')
                    ->label('Kategori')
                    ->searchable(),

                // DC-03: PIC Internal (Sales)
                TextColumn::make('picInternalSales.nama_sales')
                    ->label('PIC Internal (Sales)')
                    ->placeholder('-')
                    ->searchable()
                    ->badge()
                    ->color('gray'),

                // DC-03: PIC sesuai Client Type
                TextColumn::make('nama_pic')
                    ->label('PIC Client')
                    ->placeholder('-')
                    ->searchable(),

                TextColumn::make('priority')
                    ->label('Prioritas')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),

                // DC-04: Kolom Campaign — klik untuk lihat daftar (DC-05)
                TextColumn::make('campaigns_count')
                    ->label('Campaign')
                    ->getStateUsing(fn($record): int => $record->campaigns()->count())
                    ->badge()
                    ->color(fn(int $state): string => $state > 0 ? 'primary' : 'gray')
                    ->action(
                        Action::make('lihatCampaign')
                            ->label('Daftar Campaign')
                            ->modalHeading(fn($record): string => 'Campaign — ' . $record->nama_brand)
                            ->modalContent(function ($record): HtmlString {
                                $campaigns = $record->campaigns()->orderByDesc('created_at')->get();

                                if ($campaigns->isEmpty()) {
                                    return new HtmlString('<p class="text-sm text-gray-500 py-4 text-center">Belum ada campaign untuk client ini.</p>');
                                }

                                $rows = $campaigns->map(function ($c) {
                                    $status = e($c->status ?? '-');
                                    $name = e($c->campaign_name ?? '-');
                                    $start = $c->start_date ? $c->start_date->format('d M Y') : '-';
                                    $end = $c->end_date ? $c->end_date->format('d M Y') : '-';
                                    $cost = $c->total_cost ? 'Rp ' . number_format($c->total_cost, 0, ',', '.') : '-';

                                    return "<tr class='border-b text-sm'>
                                        <td class='py-2 pr-4 font-medium'>{$name}</td>
                                        <td class='py-2 pr-4'>{$start} – {$end}</td>
                                        <td class='py-2 pr-4'>{$cost}</td>
                                        <td class='py-2'><span class='text-xs font-semibold uppercase'>{$status}</span></td>
                                    </tr>";
                                })->join('');

                                return new HtmlString("
                                    <div class='overflow-x-auto'>
                                        <table class='w-full text-left'>
                                            <thead>
                                                <tr class='border-b text-xs uppercase text-gray-400'>
                                                    <th class='py-2 pr-4'>Nama Campaign</th>
                                                    <th class='py-2 pr-4'>Periode</th>
                                                    <th class='py-2 pr-4'>Total Cost</th>
                                                    <th class='py-2'>Status</th>
                                                </tr>
                                            </thead>
                                            <tbody>{$rows}</tbody>
                                        </table>
                                    </div>
                                ");
                            })
                            ->modalSubmitAction(false)
                            ->modalCancelActionLabel('Tutup'),
                    ),

                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->color(fn(?string $state): string => match ($state) {
                        'Newest' => 'info',
                        'Number of Meeting' => 'primary',
                        'Brief' => 'warning',
                        'Waiting Feedback' => 'danger',
                        'Not Available' => 'gray',
                        default => 'gray',
                    })
                    ->searchable(),

                TextColumn::make('date_outreach')
                    ->label('Tgl Outreach')
                    ->date('d M Y')
                    ->sortable(),

                TextColumn::make('date_follow_up')
                    ->label('Tgl Follow Up')
                    ->date('d M Y')
                    ->sortable(),

                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('type')
                    ->label('Client Type')
                    ->options([
                        'direct' => 'Direct Brand',
                        'agency' => 'Agency',
                    ]),

                SelectFilter::make('status')
                    ->label('Status')
                    ->options([
                        'Newest' => 'Newest',
                        'Number of Meeting' => 'Number of Meeting',
                        'Brief' => 'Brief',
                        'Waiting Feedback' => 'Waiting Feedback',
                        'Not Available' => 'Not Available',
                    ]),
            ])
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('nama_brand', 'asc');
    }
}
