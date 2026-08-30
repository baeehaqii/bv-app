<?php

namespace App\Filament\Resources\DataKols\Tables;

use App\Models\DataKol;
use Filament\Actions\Action;
use Filament\Actions\BulkAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

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

                        // Sengaja subquery kol_key, bukan having() pada alias
                        // channels_sum_followers: alias itu hilang saat Filament
                        // menghitung total baris (count() membuang daftar select).
                        return $query->whereIn('kol_key', DataKol::query()
                            ->select('kol_key')
                            ->groupBy('kol_key')
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
                // Pembatal aksi "Gabungkan". Channel yang dilepas kembali berdiri
                // sendiri dengan kunci = username-nya.
                Action::make('pisahkan')
                    ->label('Pisahkan')
                    ->icon('heroicon-o-scissors')
                    ->color('gray')
                    ->visible(fn(DataKol $record) => $record->channels->count() > 1)
                    ->modalHeading(fn(DataKol $record) => "Pisahkan Channel — @{$record->username}")
                    ->modalSubmitActionLabel('Pisahkan')
                    ->schema(fn(DataKol $record) => [
                        Select::make('ids')
                            ->label('Channel yang dilepas dari KOL ini')
                            ->options($record->channels
                                ->mapWithKeys(fn(DataKol $c) => [$c->id => $c->channel . ' — @' . $c->username])
                                ->all())
                            ->multiple()
                            ->required()
                            ->native(false),
                    ])
                    ->action(function (array $data) {
                        $baris = DataKol::whereIn('id', $data['ids'] ?? [])->get();

                        foreach ($baris as $channel) {
                            $channel->update(['kol_key' => $channel->username]);
                        }

                        Notification::make()->success()
                            ->title($baris->count() . ' channel dipisahkan')
                            ->send();
                    }),

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
                    // Menyatukan orang yang sama tapi handle-nya beda tiap platform.
                    // Yang diubah cuma `kol_key`; username tiap baris tetap apa adanya.
                    BulkAction::make('gabungkan')
                        ->label('Gabungkan jadi 1 KOL')
                        ->icon('heroicon-o-link')
                        ->color('warning')
                        ->requiresConfirmation()
                        ->modalHeading('Gabungkan KOL')
                        ->modalDescription('Baris terpilih dianggap orang yang sama, jadi angkanya '
                            . 'dijumlahkan bersama di KOL Data dan KOL Analyzer. Username tiap channel '
                            . 'tidak diubah. Bisa dibatalkan lewat aksi "Pisahkan Channel".')
                        ->modalSubmitActionLabel('Ya, gabungkan')
                        ->deselectRecordsAfterCompletion()
                        ->action(function (Collection $records) {
                            $kunci = $records->pluck('kol_key')->filter()->unique();

                            if ($kunci->count() < 2) {
                                Notification::make()->warning()
                                    ->title('Tidak ada yang perlu digabung')
                                    ->body('Baris yang dipilih sudah satu KOL.')
                                    ->send();

                                return;
                            }

                            // Wakilnya = KOL dengan followers terbanyak, sama dengan
                            // aturan baris wakil di scopeOneRowPerKol().
                            $utama = $records->sortByDesc('followers')->first()->kol_key;
                            $jumlah = DataKol::whereIn('kol_key', $kunci)->update(['kol_key' => $utama]);

                            Notification::make()->success()
                                ->title($kunci->count() . ' KOL digabung jadi satu')
                                ->body($jumlah . ' baris channel sekarang bernaung di @' . $utama . '.')
                                ->send();
                        }),

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
