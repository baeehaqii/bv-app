<?php

namespace App\Service;

use Carbon\CarbonImmutable;
use Illuminate\Support\Str;

/**
 * Dasar bersama tiap profil migrasi spreadsheet.
 *
 * Diangkat setelah profil ketiga (Client, Pipeline, Campaign) — bukan sebelumnya:
 * bentuk yang dipakai bersama baru kelihatan setelah ada beberapa yang nyata.
 *
 * Pemetaan lewat JUDUL kolom di baris pertama, bukan huruf kolom tetap, karena
 * urutan kolom di sheet paling sering berubah. Subclass cukup mengisi aliases(),
 * previewColumns(), dan persist().
 */
abstract class SheetMigration
{
    /** Label di dropdown "Jenis data" pada halaman migrasi. */
    abstract public function label(): string;

    /** Nama tab yang dipakai kalau user tidak memilih sendiri. */
    abstract public function defaultSheetName(): ?string;

    /**
     * field => judul kolom yang diterima (hasil normalisasi: huruf kecil, tanpa
     * tanda baca, spasi tunggal).
     *
     * @return array<string, array<int, string>>
     */
    abstract public function aliases(): array;

    /** @return array<int, string> field yang ditampilkan di tabel preview */
    abstract public function previewColumns(): array;

    /**
     * Simpan satu chunk.
     *
     * @param  array<int, array<string, mixed>>  $items
     * @return array{success: int, skipped: int, failed: int, notes: array<int, string>}
     */
    abstract public function persist(array $items): array;

    /** Rapikan satu item setelah kolomnya dipetakan (normalisasi khas profil). */
    protected function refine(array $item): array
    {
        return $item;
    }

    /** Item tanpa nilai di field ini dianggap tidak bisa disimpan. */
    protected function requiredField(): string
    {
        return 'nama_brand';
    }

    /* ---------------------------------------------------------------------
     | Pemetaan judul kolom
     * ------------------------------------------------------------------- */

    /**
     * @param  array<int, mixed>  $headerRow
     * @return array<int, string> index kolom => field
     */
    public function mapHeaders(array $headerRow): array
    {
        $peta = [];

        foreach ($headerRow as $i => $judul) {
            $normal = self::normalize((string) $judul);

            if ($normal === '') {
                continue;
            }

            foreach ($this->aliases() as $field => $alias) {
                // Satu field hanya boleh diisi SATU kolom, yang paling kiri.
                // Sheet nyata sering punya dua judul yang menyusut jadi sama —
                // "Projected Nett Margin" (rupiah) dan "Projected Nett Margin %"
                // kehilangan bedanya setelah tanda baca dibuang — dan kolom
                // belakangan akan menimpa nilai kolom depan kalau dibiarkan.
                if (in_array($normal, $alias, true) && ! in_array($field, $peta, true)) {
                    $peta[$i] = $field;
                    break;
                }
            }
        }

        return $peta;
    }

    /**
     * Judul kolom yang memang SENGAJA tidak diambil — angka turunan yang dihitung
     * aplikasi, kolom duplikat, atau yang tidak punya padanan. Dipisahkan dari
     * kolom yang benar-benar tidak dikenali supaya user tidak mengira ada yang
     * gagal terbaca padahal itu keputusan.
     *
     * @return array<int, string> nama sudah ternormalisasi
     */
    public function ignoredHeaders(): array
    {
        return [];
    }

    /**
     * Judul kolom yang tidak cocok alias mana pun — ditunjukkan ke user supaya
     * jelas kolom apa saja yang tidak ikut termigrasi.
     *
     * @param  array<int, mixed>  $headerRow
     * @return array<int, string>
     */
    public function unmappedHeaders(array $headerRow): array
    {
        return $this->pisahHeader($headerRow)['tidak_dikenali'];
    }

    /**
     * @param  array<int, mixed>  $headerRow
     * @return array{diabaikan: array<int, string>, tidak_dikenali: array<int, string>}
     */
    public function pisahHeader(array $headerRow): array
    {
        $terpetakan = $this->mapHeaders($headerRow);
        $diabaikan = $this->ignoredHeaders();

        $sisa = collect($headerRow)
            ->reject(fn($judul, $i) => isset($terpetakan[$i]) || self::normalize((string) $judul) === '')
            ->map(fn($judul) => (string) $judul)
            ->unique()
            ->values();

        return [
            'diabaikan' => $sisa->filter(fn($j) => in_array(self::normalize($j), $diabaikan, true))->values()->all(),
            'tidak_dikenali' => $sisa->reject(fn($j) => in_array(self::normalize($j), $diabaikan, true))->values()->all(),
        ];
    }

    /**
     * Baris mana yang berisi judul kolom.
     *
     * Tidak selalu baris pertama: banyak sheet diawali judul besar, baris total,
     * dan catatan "*Di isi oleh BD". Jadi dicari baris yang paling banyak
     * kolomnya dikenali, bukan diasumsikan.
     *
     * @param  array<int, array<int, mixed>>  $rows
     */
    public function headerRowIndex(array $rows): int
    {
        $terbaik = 0;
        $skor = 0;

        foreach (array_slice($rows, 0, 12, true) as $i => $row) {
            $cocok = count($this->mapHeaders($row));

            if ($cocok > $skor) {
                $skor = $cocok;
                $terbaik = $i;
            }
        }

        return $terbaik;
    }

    /** @param array<int, array<int, mixed>> $rows */
    public function headerRow(array $rows): array
    {
        return $rows[$this->headerRowIndex($rows)] ?? [];
    }

    /**
     * Berapa baris yang ditempati judul kolom. Lebih dari satu bila sheet punya
     * sub-judul (mis. "Qty"/"Item" di baris bawah "Scope of Work").
     */
    public function headerRowSpan(): int
    {
        return 1;
    }

    /**
     * Baris mentah → item siap simpan. Baris judul dicari sendiri, baris di
     * atasnya diabaikan.
     *
     * @param  array<int, array<int, mixed>>  $rows
     * @return array<int, array<string, mixed>>
     */
    public function parseRows(array $rows): array
    {
        if (count($rows) < 2) {
            return [];
        }

        $barisJudul = $this->headerRowIndex($rows);
        $peta = $this->mapHeaders($this->headerRow($rows));
        $mulai = $barisJudul + $this->headerRowSpan();
        $items = [];

        foreach (array_slice($rows, $mulai) as $n => $row) {
            $item = [];

            foreach ($peta as $i => $field) {
                $item[$field] = self::bersihkan($row[$i] ?? null);
            }

            // Baris kosong (pemisah antar blok di sheet) dilewati diam-diam.
            if (collect($item)->filter(fn($v) => $v !== null && $v !== '')->isEmpty()) {
                continue;
            }

            $item = $this->refine($item);

            // Nomor baris seperti yang terlihat di Google Sheets (mulai dari 1).
            $item['_row'] = $mulai + $n + 1;
            $item['_note'] ??= blank($item[$this->requiredField()] ?? null)
                ? 'Kolom wajib kosong — baris dilewati.'
                : null;

            $items[] = $item;
        }

        return $items;
    }

    /* ---------------------------------------------------------------------
     | Pembersih nilai sel
     * ------------------------------------------------------------------- */

    public static function normalize(string $teks): string
    {
        // "%" diubah jadi kata, bukan dibuang: judul "Projected Nett Margin" dan
        // "Projected Nett Margin %" adalah dua kolom berbeda (rupiah vs persen),
        // dan bedanya cuma tanda itu.
        $teks = str_replace('%', ' persen ', $teks);

        return trim(preg_replace('/\s+/', ' ', Str::lower(preg_replace('/[^\p{L}\p{N}\s]+/u', ' ', $teks))) ?? '');
    }

    protected static function bersihkan(mixed $nilai): mixed
    {
        return is_string($nilai) ? trim($nilai) : $nilai;
    }

    /**
     * Serial Google (hari 0 = 1899-12-30) atau teks tanggal → Y-m-d.
     *
     * Sel rusak ditolak jadi null, bukan dipaksa: angka telanjang seperti "10"
     * itu sisa kolom lain, dan tahun di luar 2000–2100 pasti salah baca.
     */
    public static function toDate(mixed $nilai): ?string
    {
        if ($nilai === null || $nilai === '') {
            return null;
        }

        if (is_numeric($nilai)) {
            $serial = (float) $nilai;

            if ($serial < 25000 || $serial > 80000) {
                return null;
            }

            return CarbonImmutable::create(1899, 12, 30)->addDays((int) $serial)->toDateString();
        }

        try {
            $tanggal = CarbonImmutable::parse((string) $nilai);
        } catch (\Throwable) {
            return null;
        }

        return ($tanggal->year >= 2000 && $tanggal->year <= 2100) ? $tanggal->toDateString() : null;
    }

    /** Angka dari sel yang bisa berisi "Rp1.000.000", "1,000,000", atau angka asli. */
    public static function toNumber(mixed $nilai): ?float
    {
        if ($nilai === null || $nilai === '') {
            return null;
        }

        if (is_numeric($nilai)) {
            return (float) $nilai;
        }

        $bersih = preg_replace('/[^0-9,.\-]/', '', (string) $nilai) ?? '';
        // Pemisah ribuan Indonesia (titik) dibuang, koma desimal jadi titik.
        $bersih = str_replace(['.', ','], ['', '.'], $bersih);

        return is_numeric($bersih) ? (float) $bersih : null;
    }

    /**
     * Nama client/agency yang mirip tapi tidak sama persis — dipakai memperingatkan
     * salah ketik di sheet sebelum jadi baris kembar (mis. "Injouney" vs "Injourney").
     *
     * @param  \Illuminate\Support\Collection<int, string>  $kandidat
     */
    public static function miripDengan(string $nama, \Illuminate\Support\Collection $kandidat): ?string
    {
        $n = self::normalize($nama);

        return $kandidat->first(function (string $k) use ($n, $nama) {
            $kn = self::normalize($k);

            // Nama pendek sengaja dilewati: pada "UM" vs "UIP" jarak 2 huruf itu
            // hampir seluruh katanya, jadi peringatannya cuma jadi derau.
            return $kn !== $n
                && strlen($n) >= 6
                && strlen($kn) >= 6
                && abs(strlen($kn) - strlen($n)) <= 2
                && levenshtein($kn, $n) <= 2
                && $k !== $nama;
        });
    }
}
