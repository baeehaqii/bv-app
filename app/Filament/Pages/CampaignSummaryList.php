<?php

namespace App\Filament\Pages;

use App\Filament\Concerns\MenarikPerformaBertahap;
use App\Filament\Resources\BvCampigns\BvCampignResource;
use App\Models\BvCampaignKol;
use App\Models\BvCampign;
use App\Service\AiWriter;
use App\Service\CampaignSummary;
use App\Service\PostCommentsFetcher;
use App\Service\PostPerformanceService;
use Exception;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Support\Enums\FontWeight;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Url;

/**
 * Campaign Summary — daftar campaign internal yang jalan, lalu ringkasannya.
 *
 * Daftar dan detail sengaja SATU halaman: kalau detailnya dipindah ke halaman
 * resource lain, sidebar berpindah sorotan ke "Campaign Ongoing Internal" dan
 * user merasa terlempar keluar dari menu ini. Mode ditentukan `$campaignId`,
 * yang juga masuk query string supaya halamannya tetap bisa di-bookmark.
 *
 * Satu Livewire component hanya boleh punya satu tabel, jadi table() berganti
 * kueri & kolom mengikuti mode — bukan dua tabel yang saling berebut state.
 */
class CampaignSummaryList extends Page implements HasTable
{
    use InteractsWithTable;
    use MenarikPerformaBertahap;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-presentation-chart-line';
    protected static string|\UnitEnum|null $navigationGroup = 'Campaign Area';
    protected static ?int $navigationSort = 0;
    protected static ?string $navigationLabel = 'Campaign Summary';
    protected static ?string $title = 'Campaign Summary';
    protected static ?string $slug = 'campaign-summary';
    protected string $view = 'filament.pages.campaign-summary-list';

    #[Url(as: 'campaign', history: true)]
    public ?int $campaignId = null;

    /**
     * Retrieve History merender riwayat SEMUA postingan, dan satu campaign bisa
     * berisi puluhan postingan — dipotong per halaman supaya tidak jadi gulungan
     * panjang yang tak ada habisnya.
     */
    public int $historyPerPage = 5;

    public int $historyPage = 1;

    /** Ganti isi per halaman → balik ke halaman 1, jangan tertinggal di halaman kosong. */
    public function updatedHistoryPerPage(): void
    {
        $this->historyPage = 1;
    }

    public function updatedCampaignId(): void
    {
        $this->historyPage = 1;
    }

    /** Cakupan Fetch All di halaman ini: semua postingan campaign yang sudah tayang. */
    protected function antreanFetch(): \Illuminate\Support\Collection
    {
        return $this->summary?->published() ?? collect();
    }

    /** Jumlah halaman Retrieve History; minimal 1 supaya penunjuknya tidak "0 dari 0". */
    public function getHistoryPagesProperty(): int
    {
        $total = $this->summary?->published()->count() ?? 0;

        return max(1, (int) ceil($total / max(1, $this->historyPerPage)));
    }

    /**
     * Postingan pada halaman yang sedang dibuka.
     *
     * @return \Illuminate\Support\Collection<int, BvCampaignKol>
     */
    public function getHistoryPageItemsProperty(): \Illuminate\Support\Collection
    {
        $published = $this->summary?->published() ?? collect();

        // Halaman dijepit ke rentang yang ada: hapus baris di halaman terakhir
        // dan nomor halamannya bisa melewati batas.
        $halaman = min(max(1, $this->historyPage), $this->historyPages);

        return $published->forPage($halaman, max(1, $this->historyPerPage));
    }

    public static function getNavigationBadge(): ?string
    {
        return 'Testing';
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'warning';
    }

    public function getTitle(): string
    {
        return $this->campaign
            ? "Campaign Summary — {$this->campaign->campaign_name}"
            : 'Campaign Summary';
    }

    public function getCampaignProperty(): ?BvCampign
    {
        return $this->campaignId ? BvCampign::find($this->campaignId) : null;
    }

    public function getSummaryProperty(): ?CampaignSummary
    {
        return $this->campaign ? new CampaignSummary($this->campaign) : null;
    }

    public function table(Table $table): Table
    {
        return $this->campaign
            ? $this->contentListTable($table)
            : $this->campaignListTable($table);
    }

    /** Mode daftar: campaign internal yang sudah jalan. */
    private function campaignListTable(Table $table): Table
    {
        return $table
            ->query(BvCampign::query()
                ->where('campaign_type', BvCampign::TYPE_INTERNAL)
                ->whereIn('status', ['ongoing', 'completed'])
                ->withCount(['kols as posted_count' => fn($q) => $q
                    ->where('brief_status', 'approved')
                    ->whereNotNull('post_url')
                    ->where('post_url', '!=', '')])
                ->withSum(['kols as total_views' => fn($q) => $q->where('brief_status', 'approved')], 'views')
                // total_engagement itu accessor (likes+comments+shares+saves), bukan
                // kolom yang terpelihara — menjumlahkannya lewat SQL akan membaca nilai basi.
                ->withSum(
                    ['kols as total_engagement_sum' => fn($q) => $q->where('brief_status', 'approved')],
                    DB::raw('likes + comments + shares + saves'),
                ))
            ->columns([
                TextColumn::make('campaign_name')->label('Campaign')
                    ->weight(FontWeight::SemiBold)
                    ->description(fn(BvCampign $r) => $r->client?->nama_brand)
                    ->searchable()->sortable(),

                TextColumn::make('status')->label('Status')->badge()
                    ->color(fn($state) => $state === 'ongoing' ? 'success' : 'gray')
                    ->formatStateUsing(fn($state) => ucfirst((string) $state)),

                TextColumn::make('posted_count')->label('Konten Tayang')->alignEnd()->sortable(),

                TextColumn::make('total_views')->label('Views')->alignEnd()
                    ->formatStateUsing(fn($state) => number_format((int) $state, 0, ',', '.'))->sortable(),

                TextColumn::make('total_engagement_sum')->label('Engagement')->alignEnd()->color('primary')
                    ->formatStateUsing(fn($state) => number_format((int) $state, 0, ',', '.'))->sortable(),

                TextColumn::make('updated_at')->label('Diupdate')->since()->sortable(),
            ])
            ->recordActions([
                Action::make('buka_summary')
                    ->label('Lihat Summary')
                    ->icon('heroicon-o-chart-bar-square')
                    // Pindah lewat URL, bukan sekadar set properti: Filament membangun
                    // tabel SEKALI per request, jadi mengubah mode di tengah request
                    // menyisakan tabel daftar campaign di tempat Content List.
                    ->url(fn(BvCampign $record) => static::getUrl(['campaign' => $record->getKey()])),
            ])
            ->recordAction('buka_summary')
            ->defaultSort('updated_at', 'desc')
            ->emptyStateHeading('Belum ada campaign internal yang jalan')
            ->emptyStateDescription('Campaign muncul di sini setelah statusnya Ongoing.')
            ->emptyStateIcon('heroicon-o-presentation-chart-line');
    }

    /** Mode detail: daftar postingan KOL campaign yang sedang dibuka. */
    private function contentListTable(Table $table): Table
    {
        return $table
            ->query(BvCampaignKol::query()
                ->where('campaign_id', $this->campaignId)
                ->where('brief_status', 'approved'))
            ->columns([
                TextColumn::make('creator_name')->label('Creator Name')
                    ->weight(FontWeight::SemiBold)
                    ->description(fn(BvCampaignKol $r) => $r->username ? '@' . $r->username : null)
                    ->searchable()->sortable(),

                TextColumn::make('upload_status')->label('Upload Status')->badge()
                    ->state(fn(BvCampaignKol $r) => $r->isPublished() ? 'COMPLETED' : 'PENDING')
                    ->color(fn($state) => $state === 'COMPLETED' ? 'success' : 'warning'),

                TextColumn::make('platform')->label('Platform')->badge()
                    ->formatStateUsing(fn($state) => BvCampaignKol::PLATFORMS[$state] ?? ucfirst((string) $state)),

                TextColumn::make('tier')->label('Category')
                    ->formatStateUsing(fn($state) => $state ? ucfirst($state) : '—'),

                TextColumn::make('price')->label('Cost (IDR)')->alignEnd()
                    ->formatStateUsing(fn($state) => number_format((float) $state, 0, ',', '.'))->sortable(),

                TextColumn::make('views')->label('View')->alignEnd()
                    ->formatStateUsing(fn($state) => number_format((int) $state, 0, ',', '.'))->sortable(),

                TextColumn::make('total_engagement')->label('Engagement')->alignEnd()->color('primary')
                    ->formatStateUsing(fn($state) => number_format((int) $state, 0, ',', '.')),

                TextColumn::make('likes')->label('Like')->alignEnd()->numeric()->sortable(),
                TextColumn::make('comments')->label('Comment')->alignEnd()->numeric()->sortable(),
                TextColumn::make('shares')->label('Share')->alignEnd()->numeric()->toggleable(),
                TextColumn::make('saves')->label('Save')->alignEnd()->numeric()->toggleable(),

                TextColumn::make('cpe')->label('CPE (IDR)')->alignEnd()
                    ->state(fn(BvCampaignKol $r) => number_format($r->cpe(), 0, ',', '.'))
                    ->tooltip('Cost per Engagement — cost / engagement'),
            ])
            ->recordActions([
                Action::make('open_post')->label('Detail')
                    ->icon('heroicon-o-arrow-top-right-on-square')
                    ->url(fn(BvCampaignKol $r) => $r->post_url)
                    ->openUrlInNewTab()
                    ->visible(fn(BvCampaignKol $r) => $r->isPublished()),

                Action::make('fetch_one')->label('Fetch')
                    ->icon('heroicon-o-arrow-path')->color('gray')
                    ->visible(fn(BvCampaignKol $r) => $r->isPublished())
                    ->action(function (BvCampaignKol $record) {
                        try {
                            $updated = (new PostPerformanceService())->fetchAndUpdateKol($record);
                        } catch (Exception $e) {
                            Notification::make()->danger()->title('Fetch gagal')->body($e->getMessage())->send();

                            return;
                        }

                        Notification::make()->success()
                            ->title('Performa diperbarui')
                            ->body('Views: ' . number_format($updated->views) . ' · Likes: ' . number_format($updated->likes))
                            ->send();
                    }),
            ])
            ->defaultSort('views', 'desc')
            ->emptyStateHeading('Belum ada KOL yang di-approve')
            ->emptyStateIcon('heroicon-o-document-text');
    }

    /**
     * Daftar aksi header di-cache Filament sejak mount, jadi semuanya selalu
     * didaftarkan dan yang menentukan tampil/tidak adalah visible() — bukan
     * percabangan di sini, yang akan membeku pada mode saat halaman dibuka.
     *
     * Aksi mode detail dikumpulkan ke satu dropdown "Aksi": lima tombol berjajar
     * mendorong judul campaign sampai terpotong jadi tiga baris. Yang tetap
     * berdiri sendiri cuma navigasinya — itu jalan keluar dari halaman.
     */
    protected function getHeaderActions(): array
    {
        $detail = fn() => $this->campaignId !== null;

        return [
            Action::make('ke_modul')
                ->label('Campaign Ongoing Internal')
                ->icon('heroicon-o-arrow-top-right-on-square')
                ->color('gray')
                ->visible(fn() => ! $detail())
                ->url(BvCampignResource::getUrl()),

            Action::make('kembali')
                ->label('Kembali ke Daftar')
                ->icon('heroicon-o-arrow-left')
                ->color('gray')
                ->visible($detail)
                ->url(static::getUrl()),

            ActionGroup::make([
                Action::make('export_pdf')
                    ->label('Export PDF')
                    ->icon('heroicon-o-arrow-down-tray')
                    ->color('gray')
                    ->url(fn() => route('campaign-summary.pdf', ['bvCampign' => $this->campaignId]))
                    ->openUrlInNewTab(),

                Action::make('fetch_all')
                    ->label('Fetch All Performance')
                    ->icon('heroicon-o-arrow-path')
                    ->requiresConfirmation()
                    ->modalDescription(fn () => 'Menarik ulang metrik '
                        .($this->summary?->published()->count() ?? 0)
                        .' postingan yang sudah tayang, sekaligus mencatat satu baris Retrieve History. '
                        .'Diproses bertahap — jangan tutup tab selama berjalan.')
                    ->disabled(fn () => $this->fetching)
                    ->action(fn () => $this->startFetchAll()),

                Action::make('ringkasan_ai')
                    ->label('Ringkasan AI')
                    ->icon('heroicon-o-sparkles')
                    ->color('info')
                    ->visible(fn() => $detail() && AiWriter::configured())
                    ->requiresConfirmation()
                    ->modalHeading('Tulis Ulang Ringkasan AI')
                    ->modalDescription('Sekali klik memakai satu panggilan berbayar ke Gemini. '
                        . 'Hasilnya disimpan, jadi tidak perlu diklik lagi sampai angkanya berubah.')
                    ->modalSubmitActionLabel('Ya, tulis sekarang')
                    ->action(function () {
                        try {
                            $teks = AiWriter::write(
                                'Kamu analis kampanye influencer di agency Indonesia. Tulis ringkasan '
                                . 'performa campaign dalam Bahasa Indonesia, 3 paragraf pendek: (1) hasil '
                                . 'secara umum, (2) metrik yang menonjol dan yang lemah beserta alasannya, '
                                . '(3) satu rekomendasi konkret untuk campaign berikutnya. Jangan mengarang '
                                . 'angka di luar data yang diberikan, dan jangan memakai format markdown.',
                                $this->summary->factsForAi(),
                            );
                        } catch (Exception $e) {
                            Notification::make()->danger()->title('Ringkasan gagal dibuat')->body($e->getMessage())->send();

                            return;
                        }

                        $this->campaign->update(['ai_summary' => $teks, 'ai_summary_at' => now()]);

                        Notification::make()->success()->title('Ringkasan AI diperbarui')->send();
                    }),

                Action::make('analyze_sentiment')
                    ->label('Analisis Sentimen')
                    ->icon('heroicon-o-chat-bubble-left-right')
                    ->color('warning')
                    ->requiresConfirmation()
                    ->modalHeading('Ambil & Analisis Komentar')
                    ->modalDescription(fn() => 'Komentar diambil per postingan dan tiap postingan memakai kredit API, '
                        . 'jadi tidak dijalankan otomatis. Yang akan diambil: '
                        . ($this->summary?->published()->count() ?? 0) . ' postingan tayang. '
                        . 'Threads dilewati — belum ada endpoint komentarnya.')
                    ->modalSubmitActionLabel('Ya, ambil sekarang')
                    ->action(function () {
                        $fetcher = new PostCommentsFetcher();
                        $berhasil = 0;
                        $komentar = 0;

                        foreach ($this->summary->published() as $kol) {
                            if (! PostCommentsFetcher::supports($kol->platform)) {
                                continue;
                            }

                            try {
                                $teks = $fetcher->fetch($kol->post_url, $kol->platform);
                            } catch (Exception $e) {
                                continue;
                            }

                            $kol->update(['comments_data' => $teks, 'comments_fetched_at' => now()]);
                            $berhasil++;
                            $komentar += count($teks);
                        }

                        Notification::make()
                            ->title($berhasil > 0 ? 'Analisis sentimen selesai' : 'Tidak ada komentar terambil')
                            ->body("{$komentar} komentar dari {$berhasil} postingan.")
                            ->{$berhasil > 0 ? 'success' : 'warning'}()
                            ->send();
                    }),
            ])
                ->label('Aksi')
                ->icon('heroicon-o-ellipsis-horizontal')
                ->button()
                ->visible($detail),
        ];
    }
}
