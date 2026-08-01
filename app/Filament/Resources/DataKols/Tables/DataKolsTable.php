<?php

namespace App\Filament\Resources\DataKols\Tables;

use App\Models\DataKol;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

/**
 * Daftar KOL — SATU BARIS PER KOL, bukan per channel.
 *
 * data_kols menyimpan 1 baris per channel (lihat DataKol::channels()), jadi tabel
 * ini memakai baris wakil dari scopeOneRowPerKol() lalu menampilkan angka gabungan
 * lewat agregat withSum/withMax. Rincian per channel ada di halaman edit.
 */
class DataKolsTable
{
    /** Warna badge channel — dipakai di kolom daftar maupun modal. */
    public const CHANNEL_COLORS = [
        'Instagram' => 'danger',
        'Tiktok' => 'info',
        'TikTok' => 'info',
        'Youtube Channels' => 'danger',
        'Youtube Shorts' => 'danger',
        'Threads' => 'gray',
        'Facebook' => 'info',
        'X' => 'gray',
        'Talent' => 'warning',
    ];

    public static function configure(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn(Builder $query) => $query
                ->oneRowPerKol()
                ->with(['channels.rateCards.masterSow', 'channels.spks'])
                ->withSum('channels', 'followers')
                ->withSum('channels', 'engagements')
                ->withAvg('channels', 'impressions')
                ->withAvg('channels', 'engagement_rate')
                ->withMax('channels', 'terakhir_update'))
            ->columns([
                TextColumn::make('username')
                    ->label('KOL')
                    ->description(fn(DataKol $record) => $record->full_name ?: null)
                    ->searchable()
                    ->sortable()
                    ->copyable()
                    ->icon('heroicon-o-at-symbol')
                    ->weight('medium'),

                // Semua channel yang sudah ter-scraping, bukan cuma baris wakil.
                TextColumn::make('channels.channel')
                    ->label('Channel')
                    ->badge()
                    ->color(fn(string $state): string => self::CHANNEL_COLORS[$state] ?? 'gray')
                    ->listWithLineBreaks()
                    ->limitList(3)
                    ->expandableLimitedList(),

                TextColumn::make('channels_sum_followers')
                    ->label('Followers')
                    ->sortable()
                    ->alignEnd()
                    ->formatStateUsing(fn($state) => number_format((int) $state))
                    ->description(fn(DataKol $record) => $record->channels->count() . ' channel'),

                // Tier dihitung ulang dari followers GABUNGAN — kolom `tier` di DB
                // hanya berlaku untuk satu channel, jadi tidak dipakai di sini.
                TextColumn::make('tier')
                    ->label('Tier')
                    ->badge()
                    ->state(fn(DataKol $record) => DataKol::tierFor((int) $record->channels_sum_followers))
                    ->color(fn(string $state): string => match ($state) {
                        'Mega' => 'success',
                        'Macro' => 'warning',
                        'Micro' => 'primary',
                        'Nano' => 'info',
                        default => 'gray',
                    })
                    ->icon(fn(string $state): string => match ($state) {
                        'Mega' => 'heroicon-o-star',
                        'Macro' => 'heroicon-o-fire',
                        'Micro' => 'heroicon-o-sparkles',
                        'Nano' => 'heroicon-o-light-bulb',
                        default => 'heroicon-o-user',
                    }),

                TextColumn::make('channels_avg_engagement_rate')
                    ->label('ER %')
                    ->sortable()
                    ->badge()
                    ->color(fn($state): string => match (true) {
                        $state >= 5 => 'success',
                        $state >= 3 => 'warning',
                        $state >= 1 => 'primary',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn($state) => number_format((float) $state, 2) . '%')
                    ->tooltip('Rata-rata engagement rate seluruh channel'),

                TextColumn::make('channels_sum_engagements')
                    ->label('Total Engagements')
                    ->sortable()
                    ->alignEnd()
                    ->formatStateUsing(fn($state) => number_format((int) $state))
                    ->toggleable(),

                TextColumn::make('channels_avg_impressions')
                    ->label('Avg Impressions')
                    ->sortable()
                    ->alignEnd()
                    ->formatStateUsing(fn($state) => number_format((int) $state))
                    ->tooltip('Rata-rata impresi seluruh channel')
                    ->toggleable(),

                TextColumn::make('category')
                    ->label('Category')
                    ->badge()
                    // Kategori bisa beda antar channel — gabungkan tanpa duplikat.
                    ->state(fn(DataKol $record) => $record->channels
                        ->flatMap(fn(DataKol $c) => (array) $c->category)
                        ->filter()
                        ->unique()
                        ->values()
                        ->all())
                    ->color('gray')
                    ->toggleable(),

                TextColumn::make('contact')
                    ->label('Contact')
                    ->searchable()
                    ->copyable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('channels_max_terakhir_update')
                    ->label('Last Update')
                    ->date('d M Y')
                    ->sortable()
                    ->placeholder('—'),
            ])
            ->filters([
                // Tier di kolom dihitung dari followers GABUNGAN, jadi filternya juga
                // harus ke jumlah itu — bukan ke kolom `tier` baris wakil.
                SelectFilter::make('tier')
                    ->label('Filter by Tier')
                    ->options([
                        'Mega' => 'Mega (1M+)',
                        'Macro' => 'Macro (100K-999K)',
                        'Micro' => 'Micro (10K-99K)',
                        'Nano' => 'Nano (1K-9K)',
                        'Mini' => 'Mini (<1K)',
                    ])
                    ->multiple()
                    ->query(function (Builder $query, array $data): Builder {
                        $tiers = array_intersect($data['values'] ?? [], array_keys(DataKol::TIER_RANGES));

                        if (! $tiers) {
                            return $query;
                        }

                        // Aman dari injeksi: yang di-interpolasi hanya integer dari
                        // TIER_RANGES, kuncinya sudah disaring lewat array_intersect.
                        $kondisi = collect($tiers)
                            ->map(function (string $tier): string {
                                [$min, $max] = DataKol::TIER_RANGES[$tier];

                                return $max === null
                                    ? "SUM(followers) >= {$min}"
                                    : "SUM(followers) BETWEEN {$min} AND {$max}";
                            })
                            ->implode(' OR ');

                        // Sengaja subquery username, bukan having() pada alias
                        // channels_sum_followers: alias itu hilang saat Filament
                        // menghitung total baris (count() membuang daftar select).
                        return $query->whereIn('username', DataKol::query()
                            ->select('username')
                            ->groupBy('username')
                            ->havingRaw("({$kondisi})"));
                    }),

                // Difilter ke KOL yang PUNYA channel tersebut, bukan ke baris wakilnya —
                // kalau tidak, KOL multi-channel hilang saat filter tidak kena barisnya.
                SelectFilter::make('channel')
                    ->label('Filter by Channel')
                    ->options(\App\Filament\Resources\DataKols\Schemas\DataKolForm::$channelOptions)
                    ->multiple()
                    ->query(fn(Builder $query, array $data) => filled($data['values'] ?? [])
                        ? $query->whereHas('channels', fn(Builder $q) => $q->whereIn('channel', $data['values']))
                        : $query),
            ])
            ->recordActions([
                Action::make('spk')
                    ->label('SPK')
                    ->icon('heroicon-o-document-check')
                    ->color(fn(DataKol $record) => self::spkCount($record) ? 'success' : 'gray')
                    ->badge(fn(DataKol $record) => self::spkCount($record) ?: null)
                    ->modalHeading(fn(DataKol $record) => "Riwayat SPK — @{$record->username}")
                    ->modalContent(fn(DataKol $record) => view('filament.data-kols.spk-list', [
                        'spks' => $record->channels->flatMap->spks->sortByDesc('tanggal_perjanjian'),
                    ]))
                    ->modalSubmitAction(false)
                    ->modalCancelActionLabel('Tutup')
                    ->modalWidth('4xl'),

                Action::make('rate_card')
                    ->label('Ratecard')
                    ->icon('heroicon-o-banknotes')
                    ->color(fn(DataKol $record) => self::rateCardCount($record) ? 'warning' : 'gray')
                    ->badge(fn(DataKol $record) => self::rateCardCount($record) ?: null)
                    ->modalHeading(fn(DataKol $record) => "Rate Card — @{$record->username}")
                    ->modalContent(fn(DataKol $record) => view('filament.data-kols.rate-cards', [
                        'record' => $record,
                    ]))
                    ->modalSubmitAction(false)
                    ->modalCancelActionLabel('Tutup')
                    ->modalWidth('4xl'),

                EditAction::make()->label('Detail')->icon('heroicon-o-arrow-top-right-on-square'),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('username');
    }

    private static function spkCount(DataKol $record): int
    {
        return $record->channels->sum(fn(DataKol $c) => $c->spks->count());
    }

    private static function rateCardCount(DataKol $record): int
    {
        return $record->channels->sum(fn(DataKol $c) => $c->rateCards->count());
    }
}
