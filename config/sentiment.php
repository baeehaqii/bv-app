<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Leksikon Sentimen Komentar (Bahasa Indonesia + slang)
    |--------------------------------------------------------------------------
    |
    | ScrapeCreators hanya mengirim TEKS komentar — tidak ada skor sentimen dari
    | sana. Klasifikasinya dihitung sendiri dengan pencocokan kata di bawah ini.
    |
    | Cara kerja: tiap komentar diberi skor = (bobot kata positif) - (bobot kata
    | negatif). Skor akhir dipetakan ke bucket lewat `thresholds`.
    |
    | Ini leksikon, BUKAN NLP: sarkasme dan negasi berlapis tidak tertangkap.
    | Untuk komentar KOL yang pendek-pendek ini biasanya cukup, dan tim bisa
    | menambah kata di sini tanpa mengubah kode. Kalau akurasinya terasa kurang,
    | jalur upgrade-nya mengganti SentimentAnalyzer::score() dengan panggilan LLM.
    |
    */

    'positive' => [
        // bobot 2 — pujian kuat
        'keren' => 2, 'mantap' => 2, 'mantul' => 2, 'bagus' => 2, 'suka' => 2,
        'cakep' => 2, 'kece' => 2, 'terbaik' => 2, 'sempurna' => 2, 'luar biasa' => 2,
        'recommended' => 2, 'rekomendasi' => 2, 'worth' => 2, 'gokil' => 2,
        'anjay' => 2, 'gacor' => 2, 'sultan' => 2, 'juara' => 2, 'top' => 2,
        'love' => 2, 'amazing' => 2, 'perfect' => 2, 'best' => 2, 'awesome' => 2,

        // bobot 1 — positif ringan
        'lucu' => 1, 'seru' => 1, 'enak' => 1, 'murah' => 1, 'cantik' => 1,
        'ganteng' => 1, 'good' => 1, 'nice' => 1, 'oke' => 1, 'sip' => 1,
        'semangat' => 1, 'sehat' => 1, 'makasih' => 1, 'terimakasih' => 1,
        'membantu' => 1, 'bermanfaat' => 1, 'informatif' => 1, 'jujur' => 1,
        'mau' => 1, 'pengen' => 1, 'beli' => 1, 'checkout' => 1, 'wishlist' => 1,
    ],

    'negative' => [
        'jelek' => 2, 'buruk' => 2, 'parah' => 2, 'zonk' => 2, 'php' => 2,
        'penipu' => 2, 'nipu' => 2, 'bohong' => 2, 'hoax' => 2, 'scam' => 2,
        'kecewa' => 2, 'benci' => 2, 'sampah' => 2, 'norak' => 2, 'alay' => 2,
        'gagal' => 2, 'rugi' => 2, 'bad' => 2, 'worst' => 2, 'terrible' => 2,

        'mahal' => 1, 'lama' => 1, 'ribet' => 1, 'aneh' => 1, 'bosen' => 1,
        'garing' => 1, 'kurang' => 1, 'gaje' => 1, 'lebay' => 1, 'cringe' => 1,
        'males' => 1, 'ga suka' => 1, 'gak suka' => 1, 'nggak suka' => 1,
    ],

    /*
    | Skor → bucket. Diperiksa dari atas ke bawah, kecocokan pertama menang.
    | `min` inklusif. Warna dipakai langsung oleh badge di halaman summary.
    */
    'buckets' => [
        'excellent' => ['label' => 'Excellent', 'min' => 3, 'color' => '#16a34a'],
        'good' => ['label' => 'Good', 'min' => 1, 'color' => '#4ade80'],
        'neutral' => ['label' => 'Neutral', 'min' => 0, 'color' => '#60a5fa'],
        'average' => ['label' => 'Average', 'min' => -1, 'color' => '#fbbf24'],
        'negative' => ['label' => 'Negative', 'min' => -99, 'color' => '#f87171'],
    ],

    /*
    | Kata yang dibuang saat menghitung Top Buzz Word. Tanpa ini daftarnya cuma
    | berisi "yang", "di", "ini" — tidak ada gunanya.
    */
    'stopwords' => [
        'yang', 'yg', 'dan', 'di', 'ke', 'dari', 'ini', 'itu', 'ada', 'aku', 'saya',
        'kamu', 'kak', 'kk', 'bang', 'bg', 'min', 'gan', 'sis', 'nya', 'aja', 'ajah',
        'sih', 'dong', 'deh', 'kok', 'ya', 'yaa', 'iya', 'gak', 'ga', 'nggak', 'tidak',
        'juga', 'udah', 'sudah', 'lagi', 'kalo', 'kalau', 'buat', 'untuk', 'pada',
        'apa', 'siapa', 'kenapa', 'gimana', 'gitu', 'gini', 'jadi', 'bisa', 'mau',
        'the', 'and', 'for', 'you', 'are', 'with', 'this', 'that', 'its',
    ],

    /** Panjang minimal sebuah kata supaya dihitung sebagai buzz word. */
    'min_word_length' => 3,

    /** Komentar yang diambil per postingan. Tiap postingan = 1 panggilan berbayar. */
    'comments_per_post' => 100,
];
