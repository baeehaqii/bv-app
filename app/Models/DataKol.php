<?php

namespace App\Models;

use App\Service\KolPostNormalizer;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DataKol extends Model
{
    protected $guarded = [];

    protected $casts = [
        'category' => 'array',
        'terakhir_update' => 'date',
        'latest_posts' => 'array',
        'audience_countries' => 'array',
        'audience_fetched_at' => 'datetime',
        'ai_insight_at' => 'datetime',
        'is_verified' => 'boolean',
    ];

    /**
     * Baris baru selalu punya `kol_key`. Defaultnya = username, jadi KOL yang
     * handle-nya sama di semua platform tetap otomatis satu grup tanpa perlu
     * digabungkan manual.
     */
    protected static function booted(): void
    {
        static::saving(function (self $kol) {
            if (blank($kol->kol_key)) {
                $kol->kol_key = $kol->username;
            }
        });
    }

    /**
     * Tabel ini menyimpan 1 BARIS PER CHANNEL; satu KOL dikenali dari `kol_key`.
     * Relasi self-join ini yang menyatukan channel-channel milik KOL yang sama,
     * dan bisa di-eager-load / di-agregat (withSum, withMax) supaya daftar KOL
     * tidak N+1.
     *
     * DULU kuncinya `username`, jadi KOL yang handle-nya beda tiap platform
     * (@windabasudara_ vs @winda_basudara) terbaca sebagai dua orang. Sekarang
     * baris-baris itu bisa disatukan lewat aksi "Gabungkan" di KOL Data.
     */
    public function channels(): HasMany
    {
        return $this->hasMany(static::class, 'kol_key', 'kol_key');
    }

    /**
     * Satu baris per KOL untuk halaman daftar. Wakilnya = channel dengan followers
     * TERBANYAK (itu yang paling masuk akal dibuka saat klik Detail), seri dipecah
     * oleh id terbesar. Angka lintas channel diambil dari relasi `channels`.
     *
     * Subquery berkorelasi, bukan window function: harus jalan di MySQL (produksi)
     * maupun SQLite (test).
     */
    public function scopeOneRowPerKol(Builder $query): Builder
    {
        return $query->whereIn('id', static::query()
            ->selectRaw('MAX(id)')
            ->whereRaw('COALESCE(followers, 0) = (
                SELECT MAX(COALESCE(followers, 0)) FROM data_kols AS terbanyak
                WHERE terbanyak.kol_key = data_kols.kol_key
            )')
            ->groupBy('kol_key'));
    }

    /**
     * Tier & batasnya sekarang satu sumber dengan Media Plan Internal dan
     * service scraping: master data "Tier KOL" di halaman Masterdata Media
     * Plan Internal. Dulu tiga skema terpisah yang diam-diam berbeda.
     *
     * @return array<string, array{0:int, 1:?int}>
     */
    public static function tierRanges(): array
    {
        return MediaPlanCalcSetting::current()->tierRanges();
    }

    /** Tier dari total followers gabungan semua channel. */
    public static function tierFor(int $followers): string
    {
        return MediaPlanCalcSetting::current()->tierFor($followers);
    }

    /** Semua baris channel milik KOL ini, termasuk baris ini sendiri. */
    public function channelSiblings()
    {
        return $this->channels()->with('rateCards')->orderBy('channel')->get();
    }

    /**
     * Angka gabungan seluruh channel KOL ini — dipakai ringkasan KOL Analyzer.
     *
     * Aturan agregasinya sengaja sama persis dengan kolom di KOL Data
     * (DataKolsTable): followers & engagements dijumlahkan, ER dan avg views
     * dirata-rata antar channel, tier dihitung ulang dari followers gabungan.
     * Di sana bentuknya withSum/withAvg karena harus bisa di-sort & di-filter
     * lewat SQL; di sini koleksinya memang sudah dimuat.
     *
     * @return array{channels: int, followers: int, engagements: int,
     *               engagement_rate: float, average_views: int, tier: string}
     */
    public function crossChannelSummary(): array
    {
        $channels = $this->channelSiblings();
        $followers = (int) $channels->sum('followers');

        return [
            'channels' => $channels->count(),
            'followers' => $followers,
            'engagements' => (int) $channels->sum('engagements'),
            'engagement_rate' => round((float) $channels->avg('engagement_rate'), 2),
            'average_views' => (int) round((float) $channels->avg('average_views')),
            'tier' => static::tierFor($followers),
        ];
    }

    public function rateCards(): HasMany
    {
        return $this->hasMany(KolRateCard::class)->orderByDesc('valid_from');
    }

    /** Riwayat SPK/PKS KOL ini — campaign apa saja yang pernah dia tanda tangani. */
    public function spks(): HasMany
    {
        return $this->hasMany(BvSPK::class, 'data_kol_id')->latest('tanggal_perjanjian');
    }

    public function tipePajakKol(): BelongsTo
    {
        return $this->belongsTo(MasterPph::class, 'tipe_pajak_kol');
    }

    /** Riwayat followers channel ini — sumber grafik Follower Growth. */
    public function snapshots(): HasMany
    {
        return $this->hasMany(DataKolSnapshot::class)->orderBy('captured_on');
    }

    /**
     * Catat kondisi channel hari ini. Dipanggil tiap kali channel di-scrape.
     * Satu baris per tanggal — refresh berkali-kali sehari memperbarui, bukan menumpuk.
     */
    public function recordSnapshot(): void
    {
        $angka = [
            'followers' => (int) $this->followers,
            'engagement_rate' => (float) $this->engagement_rate,
            'engagements' => (int) $this->engagements,
            'impressions' => (int) $this->impressions,
        ];

        // whereDate(), bukan updateOrCreate(['captured_on' => ...]): cast `date`
        // menyimpan '2026-08-01 00:00:00' sedangkan query builder tidak ikut meng-cast,
        // jadi pencocokan string mentah meleset dan malah melanggar unique index.
        $hariIni = $this->snapshots()->whereDate('captured_on', now()->toDateString())->first();

        $hariIni
            ? $hariIni->update($angka)
            : $this->snapshots()->create([...$angka, 'captured_on' => now()->toDateString()]);
    }

    /** @return array<int, array<string, mixed>> 10 postingan terakhir hasil normalisasi. */
    public function latestPosts(): array
    {
        return $this->latest_posts ?? [];
    }

    /** @return array<string, int> hashtag => berapa postingan memakainya. */
    public function topHashtags(int $limit = 10): array
    {
        return KolPostNormalizer::topHashtags($this->latestPosts(), $limit);
    }

    /**
     * Fakta KOL yang dikirim ke model AI untuk kartu profil. Angka mentah dalam
     * teks datar — model cuma perlu angkanya, dan prompt pendek lebih murah.
     */
    public function factsForAi(): string
    {
        $gab = $this->crossChannelSummary();
        $angka = fn($n) => number_format((int) $n, 0, ',', '.');

        $baris = [
            'KOL @' . $this->username . ($this->full_name ? ' (' . $this->full_name . ')' : '') . '.',
            'Tier ' . $gab['tier'] . ', aktif di ' . $gab['channels'] . ' channel, total '
                . $angka($gab['followers']) . ' followers.',
            'Gabungan: engagement ' . $angka($gab['engagements']) . ', ER '
                . number_format($gab['engagement_rate'], 2) . '%, rata-rata views '
                . $angka($gab['average_views']) . '.',
        ];

        foreach ($this->channelSiblings() as $channel) {
            $baris[] = 'Channel ' . $channel->channel . ': ' . $angka($channel->followers)
                . ' followers, ER ' . number_format((float) $channel->engagement_rate, 2)
                . '%, avg views ' . $angka($channel->average_views)
                . ', ' . $angka($channel->media_count) . ' postingan.';
        }

        if ($this->biography) {
            $baris[] = 'Bio: ' . $this->biography;
        }

        if ($hashtag = $this->topHashtags(8)) {
            $baris[] = 'Hashtag yang sering dipakai: ' . implode(', ', array_keys($hashtag)) . '.';
        }

        if ($this->audience_countries) {
            $negara = collect($this->audience_countries)->take(5)
                ->map(fn($n) => ($n['country'] ?? '?') . ' ' . number_format((float) ($n['percentage'] ?? 0), 1) . '%')
                ->implode(', ');
            $baris[] = 'Sebaran audiens: ' . $negara . '.';
        }

        return implode("\n", $baris);
    }

    /**
     * View-Through Rate: rata-rata views dibanding followers.
     * >100% berarti kontennya tembus ke luar followers (FYP/Explore) — itu wajar,
     * jadi tidak di-clamp seperti ER.
     */
    public function viewThroughRate(): ?float
    {
        if (! $this->followers || ! $this->average_views) {
            return null;
        }

        return round(($this->average_views / $this->followers) * 100, 2);
    }
}
