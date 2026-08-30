<?php

namespace App\Service;

use App\Models\BvCampaignKol;
use App\Models\BvCampign;
use Illuminate\Support\Collection;

/**
 * Angka gabungan seluruh postingan KOL sebuah campaign — isi halaman Campaign Summary.
 *
 * Semua di sini turunan dari baris bv_campaign_kols yang sudah ada; tidak ada
 * panggilan API. Dikumpulkan di satu kelas supaya blade tidak berisi rumus, dan
 * supaya rumus CPE/CPV/CPM cuma ditulis sekali.
 */
class CampaignSummary
{
    /** @var Collection<int, BvCampaignKol> */
    public readonly Collection $kols;

    public function __construct(public readonly BvCampign $campaign)
    {
        $this->kols = $campaign->kols()
            ->where('brief_status', 'approved')
            ->orderByDesc('views')
            ->get();
    }

    /** Hanya postingan yang benar-benar sudah tayang yang dihitung sebagai konten. */
    public function published(): Collection
    {
        return $this->kols->filter->isPublished();
    }

    /**
     * Kartu-kartu di bagian atas halaman.
     *
     * @return array<string, array{label: string, value: string, hint: ?string}>
     */
    public function cards(): array
    {
        $t = $this->totals();
        $uang = fn(float $n) => 'IDR ' . number_format($n, 0, ',', '.');
        $angka = fn($n) => number_format((int) $n, 0, ',', '.');

        return [
            'views' => ['label' => 'View', 'value' => $angka($t['views']), 'hint' => null],
            'engagement' => ['label' => 'Engagement', 'value' => $angka($t['engagement']), 'hint' => 'like + comment + share + save'],
            'likes' => ['label' => 'Like', 'value' => $angka($t['likes']), 'hint' => null],
            'comments' => ['label' => 'Comment', 'value' => $angka($t['comments']), 'hint' => null],
            'shares' => ['label' => 'Share', 'value' => $angka($t['shares']), 'hint' => null],
            'saves' => ['label' => 'Save', 'value' => $angka($t['saves']), 'hint' => null],
            'cost' => ['label' => 'Cost', 'value' => $uang($t['cost']), 'hint' => 'harga ke client'],
            'cpe' => ['label' => 'CPE', 'value' => $uang($this->cpe()), 'hint' => 'cost / engagement'],
            'cpv' => ['label' => 'CPV', 'value' => $uang($this->cpv()), 'hint' => 'cost / view'],
            'cpm' => ['label' => 'CPM', 'value' => $uang($this->cpm()), 'hint' => 'cost / 1.000 view'],
            'er' => ['label' => 'Engagement Rate', 'value' => number_format($this->engagementRate(), 2) . '%', 'hint' => null],
            'content' => [
                'label' => 'Total Content Posted',
                'value' => $angka($this->published()->count()) . ' / ' . $angka($this->kols->count()),
                'hint' => 'sudah tayang / total KOL',
            ],
        ];
    }

    /** @return array<string, float> */
    public function totals(): array
    {
        return [
            'views' => (float) $this->kols->sum('views'),
            'likes' => (float) $this->kols->sum('likes'),
            'comments' => (float) $this->kols->sum('comments'),
            'shares' => (float) $this->kols->sum('shares'),
            'saves' => (float) $this->kols->sum('saves'),
            'engagement' => (float) $this->kols->sum('total_engagement'),
            'cost' => (float) $this->kols->sum('price'),
            'followers' => (float) $this->kols->sum('followers_count'),
        ];
    }

    public function cpe(): float
    {
        $t = $this->totals();

        return $t['engagement'] > 0 ? round($t['cost'] / $t['engagement'], 2) : 0.0;
    }

    public function cpv(): float
    {
        $t = $this->totals();

        return $t['views'] > 0 ? round($t['cost'] / $t['views'], 2) : 0.0;
    }

    public function cpm(): float
    {
        $t = $this->totals();

        return $t['views'] > 0 ? round($t['cost'] / $t['views'] * 1000, 2) : 0.0;
    }

    /** ER campaign: engagement gabungan dibanding views; jatuh ke followers bila belum ada views. */
    public function engagementRate(): float
    {
        $t = $this->totals();
        $basis = $t['views'] > 0 ? $t['views'] : $t['followers'];

        return $basis > 0 ? round($t['engagement'] / $basis * 100, 2) : 0.0;
    }

    /* ---------------------------------------------------------------------
     | Campaign Performance
     * ------------------------------------------------------------------- */

    /**
     * Penilaian tiap metrik terhadap benchmark di config/kol.php.
     *
     * @return array<int, array{key: string, label: string, value: string, verdict: string}>
     */
    public function metricsOverview(): array
    {
        $benchmark = config('kol.campaign_benchmark');

        $metrik = [
            ['key' => 'er', 'label' => 'E.R', 'raw' => $this->engagementRate(),
             'value' => number_format($this->engagementRate(), 2) . '%', 'higher_is_better' => true],
            ['key' => 'vtr', 'label' => 'VTR', 'raw' => $this->viewThroughRate(),
             'value' => number_format($this->viewThroughRate(), 2) . '%', 'higher_is_better' => true],
            ['key' => 'cpe', 'label' => 'CPE', 'raw' => $this->cpe(),
             'value' => 'IDR ' . number_format($this->cpe(), 0, ',', '.'), 'higher_is_better' => false],
            ['key' => 'cpv', 'label' => 'CPV', 'raw' => $this->cpv(),
             'value' => 'IDR ' . number_format($this->cpv(), 0, ',', '.'), 'higher_is_better' => false],
            ['key' => 'cpm', 'label' => 'CPM', 'raw' => $this->cpm(),
             'value' => 'IDR ' . number_format($this->cpm(), 0, ',', '.'), 'higher_is_better' => false],
        ];

        return array_map(function (array $m) use ($benchmark) {
            $ambang = $benchmark[$m['key']] ?? null;

            return [
                'key' => $m['key'],
                'label' => $m['label'],
                'value' => $m['value'],
                'verdict' => $ambang ? self::verdict($m['raw'], $ambang, $m['higher_is_better']) : 'unknown',
            ];
        }, $metrik);
    }

    /** excellent | good | bad — dipakai warna badge Metrics Overview. */
    private static function verdict(float $nilai, array $ambang, bool $makinBesarMakinBaik): string
    {
        if ($nilai <= 0) {
            return 'unknown';
        }

        $lolos = fn(float $batas) => $makinBesarMakinBaik ? $nilai >= $batas : $nilai <= $batas;

        return match (true) {
            $lolos($ambang['excellent']) => 'excellent',
            $lolos($ambang['good']) => 'good',
            default => 'bad',
        };
    }

    public function viewThroughRate(): float
    {
        $t = $this->totals();

        return $t['followers'] > 0 ? round($t['views'] / $t['followers'] * 100, 2) : 0.0;
    }

    /** Nilai 0–5: berapa dari metrik yang dinilai berakhir excellent/good. */
    public function successScore(): array
    {
        $metrik = array_filter($this->metricsOverview(), fn(array $m) => $m['verdict'] !== 'unknown');
        $lolos = count(array_filter($metrik, fn(array $m) => in_array($m['verdict'], ['excellent', 'good'], true)));

        return ['score' => $lolos, 'max' => max(1, count($metrik))];
    }

    /** @return Collection<int, BvCampaignKol> 3 KOL dengan engagement tertinggi. */
    public function topCreators(int $limit = 3): Collection
    {
        return $this->kols->sortByDesc('total_engagement')->take($limit)->values();
    }

    /* ---------------------------------------------------------------------
     | Campaign Sentiments
     * ------------------------------------------------------------------- */

    /** @return array<int, string> semua komentar tersimpan dari seluruh postingan. */
    public function allComments(): array
    {
        return $this->kols->flatMap->commentTexts()->all();
    }

    public function sentiment(): array
    {
        return SentimentAnalyzer::summarize($this->allComments());
    }

    /** @return array<string, int> */
    public function buzzWords(int $limit = 10): array
    {
        return SentimentAnalyzer::buzzWords($this->allComments(), $limit);
    }

    public function commentsFetchedAt(): ?\Illuminate\Support\Carbon
    {
        return $this->kols->max('comments_fetched_at');
    }

    /** Postingan yang komentarnya belum pernah diambil — dipakai label tombol. */
    public function pendingCommentFetch(): Collection
    {
        return $this->published()->filter(
            fn(BvCampaignKol $k) => PostCommentsFetcher::supports($k->platform) && $k->comments_fetched_at === null,
        );
    }

    /**
     * Fakta campaign yang dikirim ke model AI. Sengaja angka mentah dalam teks
     * datar, bukan JSON penuh: yang dinilai model cuma angkanya, dan prompt yang
     * pendek lebih murah. Tidak ada nama client/brand di sini — model tidak perlu
     * tahu identitas untuk menilai performa.
     */
    public function factsForAi(): string
    {
        $t = $this->totals();
        $sentimen = $this->sentiment();
        $skor = $this->successScore();
        $angka = fn($n) => number_format((int) $n, 0, ',', '.');

        $baris = [
            'Periode konten: ' . $this->published()->count() . ' dari ' . $this->kols->count() . ' KOL sudah tayang.',
            'Views: ' . $angka($t['views']) . '. Engagement: ' . $angka($t['engagement'])
                . ' (like ' . $angka($t['likes']) . ', comment ' . $angka($t['comments'])
                . ', share ' . $angka($t['shares']) . ', save ' . $angka($t['saves']) . ').',
            'Cost ke client: IDR ' . $angka($t['cost']) . '. CPE IDR ' . $angka($this->cpe())
                . ', CPV IDR ' . $angka($this->cpv()) . ', CPM IDR ' . $angka($this->cpm()) . '.',
            'Engagement rate ' . number_format($this->engagementRate(), 2) . '%, VTR '
                . number_format($this->viewThroughRate(), 2) . '%.',
            'Penilaian terhadap benchmark internal: ' . $skor['score'] . ' dari ' . $skor['max'] . ' metrik lolos.',
        ];

        foreach ($this->metricsOverview() as $m) {
            $baris[] = 'Metrik ' . $m['label'] . ' = ' . $m['value'] . ' (' . $m['verdict'] . ').';
        }

        $baris[] = $sentimen['total'] > 0
            ? 'Sentimen dari ' . $angka($sentimen['total']) . ' komentar, skor ' . $sentimen['score'] . '/5, sebaran: '
                . collect($sentimen['percentages'])->map(fn($p, $k) => $k . ' ' . number_format($p, 1) . '%')->implode(', ') . '.'
            : 'Komentar belum pernah diambil, jadi sentimen tidak diketahui.';

        $teratas = $this->topCreators()
            ->map(fn(BvCampaignKol $k) => ($k->username ?: $k->creator_name) . ' (' . $angka($k->total_engagement) . ' engagement)')
            ->implode(', ');

        if ($teratas !== '') {
            $baris[] = 'Creator dengan engagement tertinggi: ' . $teratas . '.';
        }

        return implode("\n", $baris);
    }
}
