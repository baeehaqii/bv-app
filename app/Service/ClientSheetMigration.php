<?php

namespace App\Service;

use App\Models\BvSalesList;
use App\Models\DataClient;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Migrasi Data Client dari Google Spreadsheet.
 *
 * Pemetaan kolomnya lewat JUDUL di baris pertama, bukan huruf kolom tetap:
 * tiap orang menyusun sheet-nya sendiri, dan urutan kolom paling sering berubah.
 * Selama judulnya termasuk salah satu alias di bawah, sheet-nya kebaca.
 *
 * Kolom yang butuh perlakuan khusus (sales, agency, tanggal) mengikuti aturan
 * yang sama persis dengan import CSV di App\Filament\Imports\DataClientImporter,
 * supaya dua jalur impor tidak menghasilkan baris yang beda.
 */
class ClientSheetMigration
{
    /**
     * field DataClient => judul kolom yang diterima (huruf besar-kecil & tanda
     * baca diabaikan; yang dicocokkan hasil normalisasi).
     *
     * @var array<string, array<int, string>>
     */
    public const ALIASES = [
        'nama_brand' => ['nama brand', 'brand', 'nama brand agency', 'client', 'nama client', 'nama', 'client brand'],
        'type' => ['tipe', 'type', 'tipe client', 'direct agency'],
        'category' => ['kategori', 'category'],
        'priority' => ['prioritas', 'priority'],
        'website' => ['website', 'web', 'situs'],
        'parent_brand' => ['parent brand', 'induk brand', 'grup'],
        'status_client' => ['status client', 'status klien'],
        'status' => ['status campaign', 'status'],
        'pic_internal_sales' => ['pic internal', 'pic internal sales', 'sales', 'nama sales'],
        // "Brand / Agency": isinya "Direct" atau NAMA agency yang menangani brand
        // itu — bukan kolom tipe. Nilai "Direct" diperlakukan sebagai tanpa agency.
        'agency_handled_by' => ['dihandel agency', 'handled by agency', 'agency', 'brand agency'],
        'agency_brands' => ['brand yang dihandel', 'agency brands', 'brand handled'],
        'date_outreach' => ['tanggal outreach', 'date outreach', 'outreach', 'months', 'month', 'bulan'],
        'date_follow_up' => ['tanggal follow up', 'date follow up', 'follow up'],
        'instagram' => ['instagram', 'ig'],
        'tiktok' => ['tiktok', 'tt'],
        'youtube' => ['youtube', 'yt'],
        'threads' => ['threads'],
        'account_owner' => ['account owner', 'pemilik akun'],
        'top' => ['top', 'top hari', 'term of payment'],
        'notes' => ['catatan', 'notes', 'keterangan'],
        'alamat' => ['alamat', 'address'],
    ];

    /** Kolom yang ditampilkan di tabel preview. */
    public const PREVIEW_COLUMNS = [
        'nama_brand', 'type', 'category', 'priority', 'status_client',
        'pic_internal_sales', 'date_outreach', 'website',
    ];

    /**
     * Judul kolom → nama field. Judul yang tidak dikenali diabaikan (dilaporkan
     * lewat unmappedHeaders()).
     *
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

            foreach (self::ALIASES as $field => $alias) {
                if (in_array($normal, $alias, true)) {
                    // Judul kembar: yang pertama menang, sisanya diabaikan.
                    $peta[$i] ??= $field;

                    if (($peta[$i] ?? null) === $field) {
                        break;
                    }
                }
            }
        }

        return $peta;
    }

    /**
     * Judul kolom di sheet yang tidak cocok alias mana pun — ditunjukkan ke user
     * supaya jelas kolom apa saja yang tidak ikut termigrasi.
     *
     * @param  array<int, mixed>  $headerRow
     * @return array<int, string>
     */
    public function unmappedHeaders(array $headerRow): array
    {
        $terpetakan = $this->mapHeaders($headerRow);

        return collect($headerRow)
            ->reject(fn($judul, $i) => isset($terpetakan[$i]) || self::normalize((string) $judul) === '')
            ->map(fn($judul) => (string) $judul)
            ->values()
            ->all();
    }

    /**
     * Baris mentah (baris 0 = judul) → item siap simpan.
     *
     * @param  array<int, array<int, mixed>>  $rows
     * @return array<int, array<string, mixed>>
     */
    public function parseRows(array $rows): array
    {
        if (count($rows) < 2) {
            return [];
        }

        $peta = $this->mapHeaders($rows[0]);
        $items = [];

        foreach (array_slice($rows, 1) as $n => $row) {
            $item = [];

            foreach ($peta as $i => $field) {
                $item[$field] = self::bersihkan($row[$i] ?? null);
            }

            // Baris kosong (pemisah antar blok di sheet) dilewati diam-diam.
            if (collect($item)->filter(fn($v) => $v !== null && $v !== '')->isEmpty()) {
                continue;
            }

            $item['type'] = self::normalizeType($item['type'] ?? null);
            $item['priority'] = self::normalizePriority($item['priority'] ?? null);
            $item['status_client'] = self::normalizeStatusClient($item['status_client'] ?? null);
            $item['date_outreach'] = self::toDate($item['date_outreach'] ?? null);
        $item['agency_handled_by'] = self::normalizeAgency($item['agency_handled_by'] ?? null);
            $item['date_follow_up'] = self::toDate($item['date_follow_up'] ?? null);
            $item['top'] = isset($item['top']) && $item['top'] !== '' ? (int) $item['top'] : null;

            // +2: baris 1 adalah judul, dan nomor baris di Sheets mulai dari 1.
            $item['_row'] = $n + 2;
            $item['_note'] = blank($item['nama_brand'] ?? null)
                ? 'Nama brand kosong — baris dilewati.'
                : null;

            $items[] = $item;
        }

        return $items;
    }

    /**
     * Simpan satu chunk. Idempoten: kunci baris = nama_brand + type, sama dengan
     * resolveRecord() di import CSV, jadi menjalankan ulang tidak menggandakan.
     *
     * @param  array<int, array<string, mixed>>  $items
     * @return array{success: int, skipped: int, failed: int, notes: array<int, string>}
     */
    public function persist(array $items): array
    {
        $hasil = ['success' => 0, 'skipped' => 0, 'failed' => 0, 'notes' => []];

        DB::transaction(function () use ($items, &$hasil) {
            foreach ($items as $item) {
                if (blank($item['nama_brand'] ?? null)) {
                    $hasil['skipped']++;
                    $hasil['notes'][] = "Baris {$item['_row']}: nama brand kosong, dilewati.";

                    continue;
                }

                try {
                    $this->simpanSatu($item, $hasil);
                    $hasil['success']++;
                } catch (\Throwable $e) {
                    $hasil['failed']++;
                    $hasil['notes'][] = "Baris {$item['_row']} ({$item['nama_brand']}): {$e->getMessage()}";
                }
            }
        });

        return $hasil;
    }

    /** @param  array{notes: array<int, string>}  $hasil */
    private function simpanSatu(array $item, array &$hasil): void
    {
        $client = DataClient::firstOrNew([
            'nama_brand' => $item['nama_brand'],
            'type' => $item['type'],
        ]);

        foreach (['category', 'priority', 'website', 'parent_brand', 'status_client', 'status',
            'instagram', 'tiktok', 'youtube', 'threads', 'account_owner', 'notes', 'alamat',
            'date_follow_up', 'top'] as $field) {
            // Sel kosong TIDAK menimpa data yang sudah ada — sheet sering hanya
            // mengisi sebagian kolom, dan migrasi ulang tidak boleh mengosongkan.
            if (($item[$field] ?? null) !== null && $item[$field] !== '') {
                $client->{$field} = $item[$field];
            }
        }

        // Satu brand bisa muncul di banyak baris (satu per bulan/campaign). Yang
        // disimpan bulan PALING AWAL — kapan client itu mulai digarap — bukan
        // baris terakhir yang kebetulan diproses.
        if (filled($item['date_outreach'] ?? null)) {
            $client->date_outreach = min(
                (string) $item['date_outreach'],
                (string) ($client->date_outreach ?: $item['date_outreach']),
            );
        }

        if (filled($item['pic_internal_sales'] ?? null)) {
            $nama = trim((string) $item['pic_internal_sales']);
            $sales = BvSalesList::firstOrCreate(['nama_sales' => $nama]);

            if ($sales->wasRecentlyCreated) {
                $hasil['notes'][] = "Sales baru dibuat di master: \"{$nama}\".";
            }

            $client->pic_internal_sales_id = $sales->id;
        }

        $agency = null;

        if ($client->type === 'direct' && filled($item['agency_handled_by'] ?? null)) {
            $nama = trim((string) $item['agency_handled_by']);
            $agency = DataClient::firstOrCreate(['nama_brand' => $nama, 'type' => 'agency']);

            if ($agency->wasRecentlyCreated) {
                $hasil['notes'][] = "Agency baru dibuat: \"{$nama}\".";
            }

            $client->agency_client_id = $agency->id;
            $client->has_agency = true;
        }

        if ($client->type === 'agency' && filled($item['agency_brands'] ?? null)) {
            $client->agency_brands = $this->agencyBrands((string) $item['agency_brands']);
        }

        $client->save();

        // WAJIB setelah save(): daftar agency_brands milik agency adalah SUMBER
        // relasinya di app ini — hook DataClient::syncAgencyBrands() yang
        // menurunkannya jadi agency_client_id, bukan sebaliknya. Kalau daftar ini
        // tidak ikut diisi, kolom "Brand Di-handle" di tab Agency menunjukkan 0.
        // Dan kalau dipanggil SEBELUM save(), hook itu membuat baris direct-nya
        // sendiri sehingga barisnya dobel.
        if ($agency) {
            $this->daftarkanKeAgency($agency, $client);
        }
    }

    /** Tambahkan brand ke daftar "Brand yang Di-handle" milik agency, sekali saja. */
    private function daftarkanKeAgency(DataClient $agency, DataClient $client): void
    {
        $daftar = collect($agency->agency_brands ?? []);

        if ($daftar->contains(fn($b) => trim((string) ($b['nama_brand'] ?? '')) === $client->nama_brand)) {
            return;
        }

        $pic = collect($client->pic_clients ?? [])->first() ?? [];

        $agency->agency_brands = $daftar->push([
            'nama_brand' => $client->nama_brand,
            'category' => $client->category,
            'nama_pic' => $pic['name'] ?? $pic['nama_pic'] ?? null,
            'email' => $pic['email'] ?? null,
            'wa_number' => $pic['wa_number'] ?? null,
            'description' => $client->notes,
        ])->all();

        $agency->save();
    }

    /**
     * "Garuda Food; Indomie" → daftar brand, dilengkapi data brand direct yang
     * sudah ada. Format & isinya mengikuti import CSV.
     *
     * @return array<int, array<string, mixed>>
     */
    private function agencyBrands(string $raw): array
    {
        $names = collect(explode(';', $raw))->map(fn($n) => trim($n))->filter()->unique()->values();

        $directs = DataClient::where('type', 'direct')
            ->whereIn('nama_brand', $names->all())
            ->get()
            ->keyBy('nama_brand');

        return $names->map(function (string $name) use ($directs) {
            $direct = $directs->get($name);
            $pic = collect($direct?->pic_clients ?? [])->first() ?? [];

            return [
                'nama_brand' => $name,
                'category' => $direct?->category,
                'nama_pic' => $pic['name'] ?? $pic['nama_pic'] ?? null,
                'email' => $pic['email'] ?? null,
                'wa_number' => $pic['wa_number'] ?? null,
                'description' => $direct?->notes,
            ];
        })->all();
    }

    /* ---------------------------------------------------------------------
     | Pembersih nilai sel
     * ------------------------------------------------------------------- */

    private static function normalize(string $teks): string
    {
        return trim(preg_replace('/\s+/', ' ', Str::lower(preg_replace('/[^\p{L}\p{N}\s]+/u', ' ', $teks))) ?? '');
    }

    private static function bersihkan(mixed $nilai): mixed
    {
        return is_string($nilai) ? trim($nilai) : $nilai;
    }

    /** Apa pun yang mirip "agency" jadi agency; selain itu direct (default sistem). */
    private static function normalizeType(mixed $nilai): string
    {
        return Str::contains(Str::lower((string) $nilai), 'agen') ? 'agency' : 'direct';
    }

    /** P0–P3; angka telanjang "1" ikut diterima jadi P1. */
    private static function normalizePriority(mixed $nilai): ?string
    {
        $teks = Str::upper(trim((string) $nilai));

        if (preg_match('/P?([0-3])/', $teks, $m)) {
            return 'P' . $m[1];
        }

        return null;
    }

    /**
     * Kolom agency sering berisi kata "Direct" untuk menandai TANPA agency —
     * itu bukan nama agency, jadi jangan sampai terbuat baris agency bernama
     * "Direct".
     */
    private static function normalizeAgency(mixed $nilai): ?string
    {
        $teks = trim((string) $nilai);

        return ($teks === '' || Str::lower($teks) === 'direct') ? null : $teks;
    }

    private static function normalizeStatusClient(mixed $nilai): ?string
    {
        $teks = Str::lower(trim((string) $nilai));

        return in_array($teks, ['won', 'lost', 'revision', 'mediaplan', 'awaiting', 'invoicing'], true)
            ? $teks
            : null;
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
}
