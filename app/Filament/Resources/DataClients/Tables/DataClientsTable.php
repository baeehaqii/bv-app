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
                        'direct' => 'info',
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
                    ->getStateUsing(fn($record) => collect($record->pics ?? [])
                        ->filter(fn($p) => !empty($p['agency']))
                        ->count())
                    ->badge()
                    ->color(fn($state) => $state > 0 ? 'primary' : 'gray')
                    ->action(
                        Action::make('lihatAgency')
                            ->modalHeading(fn($record): string => 'Agency — ' . $record->nama_brand)
                            ->modalSubmitAction(false)
                            ->modalCancelActionLabel('Tutup')
                            ->modalContent(function ($record): HtmlString {
                                $agencies = collect($record->pics ?? [])
                                    ->filter(fn($p) => !empty($p['agency']));

                                if ($agencies->isEmpty()) {
                                    return new HtmlString('<p class="text-sm text-gray-500 py-4 text-center">Tidak ada data agency.</p>');
                                }

                                $rows = $agencies->map(function ($p) {
                                    return '<tr class="border-b text-sm">
                                        <td class="py-2 pr-4 font-medium">' . e($p['agency'] ?? '-') . '</td>
                                        <td class="py-2 pr-4">' . e($p['name'] ?? '-') . '</td>
                                        <td class="py-2 pr-4">' . e($p['email'] ?? '-') . '</td>
                                        <td class="py-2">' . e($p['wa_number'] ?? '-') . '</td>
                                    </tr>';
                                })->join('');

                                return new HtmlString('
                                    <div class="overflow-x-auto">
                                        <table class="w-full text-left">
                                            <thead>
                                                <tr class="border-b text-xs uppercase text-gray-400">
                                                    <th class="py-2 pr-4">Nama Agency</th>
                                                    <th class="py-2 pr-4">PIC Agency</th>
                                                    <th class="py-2 pr-4">Email</th>
                                                    <th class="py-2">WhatsApp</th>
                                                </tr>
                                            </thead>
                                            <tbody>' . $rows . '</tbody>
                                        </table>
                                    </div>
                                ');
                            })
                            ->visible(fn($record) => collect($record->pics ?? [])->filter(fn($p) => !empty($p['agency']))->isNotEmpty())
                    ),

                TextColumn::make('category')
                    ->label('Kategori')
                    ->searchable(),

                TextColumn::make('agency_brands_count')
                    ->label('Brand Di-handle')
                    ->getStateUsing(fn($record) => count($record->agency_brands ?? []))
                    ->badge()
                    ->color(fn($state) => $state > 0 ? 'success' : 'gray')
                    ->suffix(fn($state) => $state > 0 ? ' brand' : '')
                    ->visible(fn() => true)
                    ->action(
                        Action::make('lihatBrands')
                            ->modalHeading(fn($record): string => 'Brand yang Di-handle — ' . ($record->nama_brand ?: 'Agency'))
                            ->modalSubmitAction(false)
                            ->modalCancelActionLabel('Tutup')
                            ->modalContent(function ($record): HtmlString {
                                $brands = collect($record->agency_brands ?? []);

                                if ($brands->isEmpty()) {
                                    return new HtmlString('<p class="text-sm text-gray-500 py-4 text-center">Tidak ada data brand.</p>');
                                }

                                $rows = $brands->map(function ($b) {
                                    return '<tr class="border-b text-sm">
                                        <td class="py-2 pr-4 font-medium">' . e($b['nama_brand'] ?? '-') . '</td>
                                        <td class="py-2 pr-4">' . e($b['category'] ?? '-') . '</td>
                                        <td class="py-2 pr-4">' . e($b['nama_pic'] ?? '-') . '</td>
                                        <td class="py-2 pr-4">' . e($b['email'] ?? '-') . '</td>
                                        <td class="py-2">' . e($b['wa_number'] ?? '-') . '</td>
                                    </tr>';
                                })->join('');

                                return new HtmlString('
                                    <div class="overflow-x-auto">
                                        <table class="w-full text-left">
                                            <thead>
                                                <tr class="border-b text-xs uppercase text-gray-400">
                                                    <th class="py-2 pr-4">Nama Brand</th>
                                                    <th class="py-2 pr-4">Kategori</th>
                                                    <th class="py-2 pr-4">PIC Brand</th>
                                                    <th class="py-2 pr-4">Email</th>
                                                    <th class="py-2">WhatsApp</th>
                                                </tr>
                                            </thead>
                                            <tbody>' . $rows . '</tbody>
                                        </table>
                                    </div>
                                ');
                            })
                            ->visible(fn($record) => $record->type === 'agency' && !empty($record->agency_brands))
                    ),

                // DC-03: PIC Internal (Sales)
                TextColumn::make('picInternalSales.nama_sales')
                    ->label('PIC Internal (Sales)')
                    ->placeholder('-')
                    ->searchable()
                    ->badge()
                    ->color('gray'),

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

                TextColumn::make('instagram')
                    ->label('Instagram')
                    ->placeholder('-')
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('tiktok')
                    ->label('TikTok')
                    ->placeholder('-')
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('youtube')
                    ->label('YouTube')
                    ->placeholder('-')
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('threads')
                    ->label('Threads')
                    ->placeholder('-')
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('status')
                    ->label('Status Campaign')
                    ->badge()
                    ->color(fn(?string $state): string => match ($state) {
                        'draft' => 'gray',
                        'upcoming' => 'info',
                        'ongoing' => 'warning',
                        'completed' => 'success',
                        'cancelled' => 'danger',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn(?string $state): string => match ($state) {
                        'draft' => 'Draft',
                        'upcoming' => 'Upcoming',
                        'ongoing' => 'Ongoing',
                        'completed' => 'Completed',
                        'cancelled' => 'Cancelled',
                        default => $state ?? '-',
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
                    ->label('Status Campaign')
                    ->options([
                        'draft' => 'Draft',
                        'upcoming' => 'Upcoming',
                        'ongoing' => 'Ongoing',
                        'completed' => 'Completed',
                        'cancelled' => 'Cancelled',
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
            ->defaultSort('created_at', 'desc');
    }
}
