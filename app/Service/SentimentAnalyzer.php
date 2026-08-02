<?php

namespace App\Service;

/**
 * Klasifikasi sentimen komentar + Top Buzz Word.
 *
 * Berbasis leksikon (config/sentiment.php), bukan NLP: tiap komentar diberi skor
 * dari kata positif dikurangi kata negatif, lalu dipetakan ke bucket. Sengaja
 * offline dan transparan — tim bisa menambah kata sendiri, dan hasilnya bisa
 * ditelusuri kata per kata. Sarkasme tidak tertangkap; itu batas yang diterima
 * saat memilih pendekatan ini.
 */
class SentimentAnalyzer
{
    /**
     * Skor satu komentar. Positif = condong bagus, negatif = condong buruk.
     */
    public static function score(string $comment): int
    {
        $teks = ' ' . mb_strtolower(trim($comment)) . ' ';
        $skor = 0;

        foreach (['positive' => 1, 'negative' => -1] as $kamus => $arah) {
            foreach (config("sentiment.{$kamus}", []) as $kata => $bobot) {
                // Dibungkus spasi: "top" tidak boleh ikut kena dari "stop"/"laptop".
                if (str_contains($teks, ' ' . $kata . ' ')) {
                    $skor += $arah * $bobot;
                }
            }
        }

        return $skor;
    }

    /** Bucket sentimen satu komentar: excellent|good|neutral|average|negative. */
    public static function bucket(string $comment): string
    {
        $skor = self::score($comment);

        foreach (config('sentiment.buckets', []) as $kunci => $bucket) {
            if ($skor >= $bucket['min']) {
                return $kunci;
            }
        }

        return 'neutral';
    }

    /**
     * Ringkasan sebaran sentimen sekumpulan komentar.
     *
     * @param  array<int, string>  $comments
     * @return array{total: int, counts: array<string, int>, percentages: array<string, float>, score: float}
     */
    public static function summarize(array $comments): array
    {
        $counts = array_fill_keys(array_keys(config('sentiment.buckets', [])), 0);
        $total = 0;

        foreach ($comments as $comment) {
            if (blank($comment)) {
                continue;
            }

            $counts[self::bucket($comment)]++;
            $total++;
        }

        $persen = $total > 0
            ? array_map(fn(int $n) => round($n / $total * 100, 2), $counts)
            : array_fill_keys(array_keys($counts), 0.0);

        return [
            'total' => $total,
            'counts' => $counts,
            'percentages' => $persen,
            'score' => self::starScore($counts, $total),
        ];
    }

    /**
     * Nilai 0–5 untuk kartu "Sentiments Score".
     * Bobot per bucket: excellent 1.0, good 0.75, neutral 0.5, average 0.25, negative 0.
     * Jadi campaign yang komentarnya netral semua dapat 2.5/5, bukan 0.
     */
    private static function starScore(array $counts, int $total): float
    {
        if ($total === 0) {
            return 0.0;
        }

        $bobot = ['excellent' => 1.0, 'good' => 0.75, 'neutral' => 0.5, 'average' => 0.25, 'negative' => 0.0];
        $jumlah = 0.0;

        foreach ($counts as $bucket => $n) {
            $jumlah += $n * ($bobot[$bucket] ?? 0.5);
        }

        return round($jumlah / $total * 5, 1);
    }

    /**
     * Kata yang paling sering muncul di komentar, tanpa stopword dan tanpa angka.
     *
     * @param  array<int, string>  $comments
     * @return array<string, int> kata => frekuensi
     */
    public static function buzzWords(array $comments, int $limit = 10): array
    {
        $stopwords = array_flip(config('sentiment.stopwords', []));
        $minPanjang = (int) config('sentiment.min_word_length', 3);
        $counts = [];

        foreach ($comments as $comment) {
            // Emoji & tanda baca dibuang; huruf/angka Unicode dipertahankan supaya
            // komentar berbahasa Indonesia tidak tercabik.
            preg_match_all('/[\p{L}]{' . $minPanjang . ',}/u', mb_strtolower((string) $comment), $cocok);

            foreach ($cocok[0] as $kata) {
                if (isset($stopwords[$kata])) {
                    continue;
                }

                $counts[$kata] = ($counts[$kata] ?? 0) + 1;
            }
        }

        arsort($counts);

        return array_slice($counts, 0, $limit, preserve_keys: true);
    }
}
