<?php

namespace App\Filament\Pages;

use App\Filament\Resources\DataKols\Tables\DataKolsTable;
use App\Models\DataKol;
use App\Service\AiWriter;
use App\Service\TiktokService;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Illuminate\Support\Collection;

/**
 * KOL Analyzer — analisis mendalam SATU channel KOL.
 *
 * Sumber datanya sama persis dengan KOL Data (tabel `data_kols`, 1 baris = 1
 * channel), jadi begitu sebuah channel masuk di KOL Data ia otomatis muncul di
 * sini. Halaman ini tidak memanggil API scraping sama sekali kecuali lewat aksi
 * "Ambil Audiens" yang harus diklik sendiri — semua angka lain sudah tersimpan
 * saat channel di-scrape.
 */
class KolAnalyzer extends Page implements HasTable
{
    use InteractsWithTable;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-chart-bar-square';
    protected static string|\UnitEnum|null $navigationGroup = 'KOL Area';
    protected static ?int $navigationSort = 2;
    protected static ?string $navigationLabel = 'KOL Analyzer';
    protected static ?string $title = 'KOL Analyzer';
    protected static ?string $slug = 'kol-analyzer';
    protected string $view = 'filament.pages.kol-analyzer';

    /** Channel yang sedang dianalisis (id baris data_kols). */
    public ?int $channelId = null;

    /** 'overview' | 'latest' */
    public string $tab = 'overview';

    /**
     * Daftar KOL — konfigurasi tabelnya DIPAKAI ULANG dari KOL Data supaya kolom,
     * filter, dan agregat per-KOL-nya sama persis. Yang diganti hanya aksinya:
     * di sini barisnya membuka analisis, bukan form edit.
     */
    public function table(Table $table): Table
    {
        // Halaman biasa (bukan Resource) tidak punya query bawaan — harus disuplai sendiri.
        return DataKolsTable::configure($table->query(DataKol::query()))
            ->recordActions([
                Action::make('analisis')
                    ->label('Analisis')
                    ->icon('heroicon-o-chart-bar-square')
                    ->action(fn(DataKol $record) => $this->channelId = $record->id),
            ])
            // Klik di mana pun pada baris ikut membuka analisis.
            ->recordAction('analisis')
            ->toolbarActions([]);
    }

    public function getChannelProperty(): ?DataKol
    {
        return $this->channelId ? DataKol::find($this->channelId) : null;
    }

    /** Semua channel milik KOL yang sama — tabel "Social Data" seperti di KOL Data. */
    public function getSiblingsProperty(): Collection
    {
        return $this->channel?->channelSiblings() ?? collect();
    }

    /**
     * Angka gabungan seluruh channel KOL ini — kartu ringkasan di atas tabel
     * Social Data. Aturan agregasinya milik model, sama dengan kolom KOL Data.
     *
     * @return array<string, int|float|string>
     */
    public function getGabunganProperty(): array
    {
        return $this->channel?->crossChannelSummary() ?? [];
    }

    /**
     * Postingan untuk tab Latest Performa. Tab Overview memakai angka rata-rata
     * yang tersimpan (dari seluruh postingan saat scraping), tab ini memakai
     * 10 postingan terakhir apa adanya.
     */
    public function getPostsProperty(): array
    {
        return $this->channel?->latestPosts() ?? [];
    }

    /**
     * Rata-rata untuk tab Latest Performa — dihitung ulang HANYA dari 10 postingan
     * terakhir, jadi angkanya bisa beda dari tab Overview. Itu memang tujuannya:
     * membandingkan performa terkini vs performa keseluruhan.
     *
     * @return array{likes: int, comments: int, views: int, vtr: ?float, posts: int, videos: int, photos: int}
     */
    public function getLatestStatsProperty(): array
    {
        $posts = $this->posts;
        $jumlah = count($posts);
        $followers = (int) ($this->channel?->followers ?? 0);

        if ($jumlah === 0) {
            return ['likes' => 0, 'comments' => 0, 'views' => 0, 'vtr' => null, 'posts' => 0, 'videos' => 0, 'photos' => 0];
        }

        $rata = fn(string $key) => (int) round(array_sum(array_column($posts, $key)) / $jumlah);
        $views = $rata('views');

        return [
            'likes' => $rata('likes'),
            'comments' => $rata('comments'),
            'views' => $views,
            'vtr' => ($followers > 0 && $views > 0) ? round($views / $followers * 100, 2) : null,
            'posts' => $jumlah,
            'videos' => count(array_filter($posts, fn($p) => $p['is_video'])),
            'photos' => count(array_filter($posts, fn($p) => ! $p['is_video'])),
        ];
    }

    /**
     * Titik-titik grafik Follower Growth. Kosong sampai channel di-scrape minimal
     * dua tanggal berbeda — tidak ada histori followers dari sumber data mana pun.
     */
    public function getGrowthProperty(): Collection
    {
        return $this->channel?->snapshots()->get() ?? collect();
    }

    public function getTopHashtagsProperty(): array
    {
        return $this->channel?->topHashtags() ?? [];
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('kembali')
                ->label('Kembali ke Daftar KOL')
                ->icon('heroicon-o-arrow-left')
                ->color('gray')
                ->visible(fn() => $this->channelId !== null)
                ->action(fn() => $this->channelId = null),

            Action::make('kartu_ai')
                ->label(fn() => $this->channel?->ai_insight ? 'Tulis Ulang Kartu AI' : 'Buat Kartu AI')
                ->icon('heroicon-o-sparkles')
                ->color('info')
                ->visible(fn() => $this->channelId !== null && AiWriter::configured())
                ->requiresConfirmation()
                ->modalHeading('Tulis Kartu Profil AI')
                ->modalDescription('Sekali klik memakai satu panggilan berbayar ke Gemini. '
                    . 'Hasilnya disimpan di KOL ini dan bisa diunduh sebagai PDF.')
                ->modalSubmitActionLabel('Ya, tulis sekarang')
                ->action(function () {
                    $channel = $this->channel;

                    try {
                        $teks = AiWriter::write(
                            'Kamu analis influencer di agency Indonesia. Tulis kartu profil KOL dalam '
                            . 'Bahasa Indonesia untuk dibaca tim sales: 1 paragraf pembuka tentang siapa '
                            . 'dan kekuatannya, lalu baris-baris pendek berisi jenis brand yang cocok dan '
                            . 'catatan risiko bila ada. Jangan mengarang angka di luar data yang diberikan, '
                            . 'dan jangan memakai format markdown.',
                            $channel->factsForAi(),
                        );
                    } catch (\Throwable $e) {
                        Notification::make()->danger()->title('Kartu AI gagal dibuat')->body($e->getMessage())->send();

                        return;
                    }

                    $channel->update(['ai_insight' => $teks, 'ai_insight_at' => now()]);

                    Notification::make()->success()->title('Kartu AI tersimpan')->send();
                }),

            Action::make('unduh_kartu_ai')
                ->label('Download Kartu (PDF)')
                ->icon('heroicon-o-arrow-down-tray')
                ->color('gray')
                ->visible(fn() => filled($this->channel?->ai_insight))
                ->url(fn() => route('kol-card.pdf', ['dataKol' => $this->channelId]))
                ->openUrlInNewTab(),

            Action::make('fetch_audience')
                ->label('Ambil Data Audiens')
                ->icon('heroicon-o-globe-alt')
                ->color('warning')
                // Hanya TikTok yang punya endpoint audiens di ScrapeCreators.
                ->visible(fn() => $this->channel?->channel === 'Tiktok')
                ->requiresConfirmation()
                ->modalHeading('Ambil Sebaran Negara Audiens')
                ->modalDescription(
                    'Endpoint ini memakai ' . TiktokService::AUDIENCE_CREDITS . ' kredit sekali panggil — '
                    . 'jauh lebih mahal dari endpoint lain, jadi tidak dijalankan otomatis. '
                    . 'Yang tersedia hanya sebaran NEGARA; kota, umur, dan gender tidak disediakan sumber data.'
                )
                ->modalSubmitActionLabel('Ya, ambil sekarang')
                ->action(function () {
                    $channel = $this->channel;

                    try {
                        $negara = (new TiktokService())->getAudienceCountries($channel->link_userprofile);
                    } catch (\Throwable $e) {
                        Notification::make()->danger()
                            ->title('Gagal mengambil data audiens')
                            ->body($e->getMessage())
                            ->send();

                        return;
                    }

                    $channel->update([
                        'audience_countries' => $negara,
                        'audience_fetched_at' => now(),
                    ]);

                    Notification::make()->success()
                        ->title(count($negara) . ' negara audiens tersimpan')
                        ->send();
                }),
        ];
    }
}
