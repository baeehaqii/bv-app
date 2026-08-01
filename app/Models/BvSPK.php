<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BvSPK extends Model
{
    protected $table = 'bv_s_p_k_s';

    /**
     * PIHAK PERTAMA — penandatangan tetap BV Network, dirakit dari
     * config/company.php supaya identitas perusahaan tidak tersebar di banyak
     * blade. Tidak perlu kolom karena sama di semua SPK.
     */
    public static function pihakPertama(): array
    {
        return [
            'perusahaan' => config('company.name'),
            'brand' => config('company.brand'),
            'nama' => config('company.signer.nama'),
            'jabatan' => config('company.signer.jabatan'),
            'alamat' => config('company.address'),
        ];
    }

    const MONTHS_ID = [
        1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April',
        5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus',
        9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember',
    ];

    const TERMIN_1_DEFAULT = 'Pembayaran penuh dilakukan setelah konten dipublikasi';

    /**
     * Klausul opsional — dipetakan ke ayat yang BENAR-BENAR ada di
     * resources/views/pdf/kol-contract.blade.php, bukan daftar generik.
     * `pasal` cuma label untuk UI; yang menentukan posisi cetak adalah kunci
     * array-nya di blade. Kalau menambah kunci di sini, tambahkan juga
     * pemakaiannya di blade — kalau tidak, klausulnya tidak akan pernah tercetak
     * (dijaga oleh test "setiap klausul terpakai di blade").
     */
    const CLAUSES = [
        'konten_tidak_dihapus' => [
            'label' => 'Konten Tidak Dihapus',
            'pasal' => 'Pasal 1 · Maksud dan Tujuan',
            'hint' => 'Kewajiban KOL tidak menghapus konten selama periode tertentu.',
            'default' => true,
            'text' => 'Video promosi yang disebutkan dalam Ayat (3) Pasal ini akan diunggah pada akun yang sudah disebutkan sebagai Pihak Kedua dan tidak akan dihapus paling tidak selama 1 (satu) bulan sejak video promosi tersebut diunggah pada masing-masing akun.',
        ],
        'insight' => [
            'label' => 'Wajib Kirim Insight',
            'pasal' => 'Pasal 1 · Maksud dan Tujuan',
            'hint' => 'KOL wajib mengirim insight/analytics setelah posting.',
            'default' => true,
            'text' => 'PIHAK KEDUA wajib memberikan insight 14 Hari setelah postingan dari konten yang telah di unggah sebagaimana tanggung jawab sesuai kesepakatan oleh PIHAK PERTAMA.',
        ],
        'eksklusivitas' => [
            'label' => 'Eksklusivitas',
            'pasal' => 'Pasal 2 · Jangka Waktu Perjanjian',
            'hint' => 'KOL tidak boleh promosi kompetitor selama masa perjanjian.',
            'default' => true,
            'text' => 'PIHAK KEDUA sepakat tidak akan bekerja sama dan mempromosikan kompetitor dari PIHAK PERTAMA pada seluruh media sosial selama Jangka Waktu Perjanjian ini berlangsung.',
        ],
        'pajak' => [
            'label' => 'Perlakuan Pajak',
            'pasal' => 'Pasal 3 · Pembayaran',
            'hint' => 'Menambahkan frasa "di luar pajak" pada nominal dan paragraf perpajakan.',
            'default' => true,
            'text' => 'Menyetujui segala bentuk dan aspek perpajakan yang timbul akibat dari pelaksanaan campaign ini dilaksanakan sesuai dengan peraturan perpajakan yang berlaku di Indonesia.',
        ],
        'napza' => [
            'label' => 'Larangan NAPZA',
            'pasal' => 'Pasal 5 · Sanksi & Pengakhiran',
            'hint' => 'Ganti rugi penuh bila KOL terbukti menyalahgunakan NAPZA.',
            'default' => true,
            'text' => 'Apabila selama berlakunya Perjanjian ini, PIHAK KEDUA terbukti secara sah menggunakan, mengedarkan dan/atau menyalahgunakan NAPZA dikuatkan dengan adanya putusan pengadilan, maka PIHAK KEDUA diwajibkan membayar ganti rugi dengan mengembalikan secara penuh seluruh uang yang telah diterima oleh PIHAK KEDUA kepada PIHAK PERTAMA termasuk biaya pelaksanaan pekerjaannya.',
        ],
        'denda' => [
            'label' => 'Denda Keterlambatan',
            'pasal' => 'Pasal 5 · Sanksi & Pengakhiran',
            'hint' => 'Denda 1‰ per hari bila KOL wanprestasi dan tidak beritikad baik.',
            'default' => true,
            'text' => 'Apabila PIHAK KEDUA tidak melaksanakan Pekerjaan pada Pasal 1 Perjanjian ini sesuai dengan yang telah disepakati oleh PARA PIHAK yang diakibatkan oleh kelalaian dan/atau kesalahan dari PIHAK KEDUA, maka PIHAK PERTAMA berhak memberikan sanksi berupa teguran secara tertulis kepada PIHAK KEDUA, dan apabila paling lambat 7 (tujuh) hari kalender setelah teguran tersebut tidak ada itikad baik dari PIHAK KEDUA, maka PIHAK KEDUA wajib mengembalikan kepada PIHAK PERTAMA sejumlah uang yang telah diterima paling lambat dalam 14 (empat belas) hari kalender dan dikenakan denda sebesar 1‰ (satu permil) perhari dari total uang yang telah diterima oleh PIHAK KEDUA. Apabila setelah 30 (tiga puluh) hari kalender terhitung sejak surat teguran diterima dan tidak ditindaklanjuti dengan itikad baik dari PIHAK KEDUA, maka PIHAK PERTAMA berhak untuk melakukan pengakhiran Perjanjian ini dengan menerima sejumlah uang yang telah dibayarkan kepada PIHAK KEDUA dan denda sebagaimana dimaksud pada Ayat ini.',
        ],
    ];

    protected $fillable = [
        'spk_number',
        'tanggal_perjanjian',
        'client_id',
        'form_brief_id',
        'internal_budget_id',
        'media_plan_kol_id',
        'data_kol_id',
        'pihak_kedua_nama_lengkap',
        'pihak_kedua_nama_akun',
        'pihak_kedua_nik',
        'pihak_kedua_alamat',
        'nama_campaign',
        'sow_disepakati',
        'timeline_kerja_sama',
        'nominal_kesepakatan',
        'nominal_terbilang',
        'atas_nama_rekening',
        'nomor_rekening',
        'nama_bank',
        'kantor_cabang_bank',
        'termin_pembayaran_1',
        'termin_pembayaran_2',
        'status',
        'notes',
        'clauses',
        'addons',
        'public_token',
        'signature_path',
        'signed_at',
        'signed_ip',
    ];

    protected $casts = [
        'tanggal_perjanjian' => 'date',
        'nominal_kesepakatan' => 'decimal:2',
        'signed_at' => 'datetime',
        'clauses' => 'array',
        'addons' => 'array',
    ];

    /**
     * Kolom yang BUKAN isi perjanjian — boleh berubah setelah SPK ditandatangani
     * (pembatalan, catatan internal, jejak tanda tangan itu sendiri).
     */
    const MUTABLE_AFTER_SIGN = [
        'status', 'notes', 'public_token',
        'signature_path', 'signed_at', 'signed_ip',
        'created_at', 'updated_at',
    ];

    /**
     * Kunci isi perjanjian begitu KOL tanda tangan. Tanpa ini, nominal/SOW bisa
     * diubah di panel dan PDF "bertanda tangan" ikut berubah — tanda tangannya
     * jadi melekat pada dokumen yang bukan yang disetujui KOL.
     * Ditegakkan di model, bukan cuma di form, supaya tidak bisa dilewati.
     */
    protected static function booted(): void
    {
        static::updating(function (self $spk) {
            if (! $spk->getOriginal('signed_at')) {
                return;
            }

            $terlarang = array_diff(array_keys($spk->getDirty()), self::MUTABLE_AFTER_SIGN);

            if ($terlarang !== []) {
                throw new \RuntimeException(
                    'SPK ' . $spk->getOriginal('spk_number') . ' sudah ditandatangani KOL — '
                    . 'isi perjanjian tidak bisa diubah (' . implode(', ', $terlarang) . '). '
                    . 'Batalkan SPK ini lalu terbitkan yang baru bila ada perubahan.'
                );
            }
        });
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(DataClient::class, 'client_id');
    }

    public function formBrief(): BelongsTo
    {
        return $this->belongsTo(FormBrief::class, 'form_brief_id');
    }

    public function internalBudget(): BelongsTo
    {
        return $this->belongsTo(InternalBudget::class);
    }

    public function mediaPlanKol(): BelongsTo
    {
        return $this->belongsTo(MediaPlanKol::class);
    }

    public function dataKol(): BelongsTo
    {
        return $this->belongsTo(DataKol::class);
    }

    public function getFormattedNominalAttribute(): string
    {
        return 'Rp ' . number_format((float) $this->nominal_kesepakatan, 0, ',', '.');
    }

    /**
     * Nomor SPK: BVN/SPK/2026/07/011 — urut per bulan.
     */
    public static function generateNumber(?\DateTimeInterface $date = null): string
    {
        $date = $date ? \Carbon\Carbon::instance($date) : now();
        $prefix = sprintf('BVN/SPK/%s/%s/', $date->format('Y'), $date->format('m'));

        $last = static::query()
            ->where('spk_number', 'like', $prefix . '%')
            ->orderByDesc('spk_number')
            ->value('spk_number');

        $next = $last ? ((int) substr($last, strlen($prefix))) + 1 : 1;

        return $prefix . str_pad((string) $next, 3, '0', STR_PAD_LEFT);
    }

    /**
     * Terbilang rupiah: 516000 → "Lima ratus enam belas ribu rupiah".
     */
    public static function terbilang(float $amount): string
    {
        $int = (int) floor(abs($amount));

        if ($int === 0) {
            return 'Nol rupiah';
        }

        return ucfirst(preg_replace('/\s+/', ' ', static::words($int))) . ' rupiah';
    }

    protected static function words(int $n): string
    {
        $satuan = ['', 'satu', 'dua', 'tiga', 'empat', 'lima', 'enam', 'tujuh',
            'delapan', 'sembilan', 'sepuluh', 'sebelas'];

        return trim(match (true) {
            $n < 12 => $satuan[$n],
            $n < 20 => $satuan[$n - 10] . ' belas',
            $n < 100 => $satuan[intdiv($n, 10)] . ' puluh ' . static::words($n % 10),
            $n < 200 => 'seratus ' . static::words($n - 100),
            $n < 1000 => $satuan[intdiv($n, 100)] . ' ratus ' . static::words($n % 100),
            $n < 2000 => 'seribu ' . static::words($n - 1000),
            $n < 1_000_000 => static::words(intdiv($n, 1000)) . ' ribu ' . static::words($n % 1000),
            $n < 1_000_000_000 => static::words(intdiv($n, 1_000_000)) . ' juta ' . static::words($n % 1_000_000),
            default => static::words(intdiv($n, 1_000_000_000)) . ' miliar ' . static::words($n % 1_000_000_000),
        });
    }

    /**
     * Terbitkan SPK untuk setiap KOL yang punya budget item ber-status `approved`.
     * Idempoten: KOL yang SPK-nya sudah ada dilewati, jadi tombol boleh diklik
     * ulang saat ada KOL yang baru di-approve client.
     *
     * Nominal memakai SUM(rate_base) — basis yang sama dengan
     * CampaignKolPayment::real_cost, supaya nilai di kontrak == yang dibayarkan.
     *
     * @return Collection<int, static> SPK yang baru dibuat
     */
    public static function createFromBudget(InternalBudget $budget): Collection
    {
        $groups = static::approvedItemsQuery($budget)
            ->get()
            ->groupBy('media_plan_kol_id');

        $konteks = static::budgetContext($budget);
        $created = new Collection();

        foreach ($groups as $mediaPlanKolId => $items) {
            $created->push(static::buildForKol($budget, (int) $mediaPlanKolId, $items, $konteks));
        }

        return $created;
    }

    // ─────────────────────── KLAUSUL OPSIONAL ───────────────────────

    /** Semua klausul aktif secara default (SPK lama yang `clauses`-nya null ikut ini). */
    public static function defaultClauses(): array
    {
        return collect(self::CLAUSES)
            ->map(fn(array $c) => ['enabled' => $c['default'], 'text' => $c['text']])
            ->all();
    }

    /**
     * Bentuk map (disimpan di DB) → list untuk Repeater Filament.
     * Repeater bekerja dengan list bernomor, sedangkan penyimpanan pakai map
     * ber-kunci supaya klausul bisa dibaca langsung: clauses.eksklusivitas.enabled.
     * Urutan mengikuti CLAUSES, jadi baris di form selalu urut & lengkap
     * walau SPK lama hanya menyimpan sebagian kunci.
     *
     * @return array<int, array{key: string, enabled: bool, text: string}>
     */
    public static function clausesToForm(?array $map): array
    {
        return collect(self::CLAUSES)
            ->map(fn(array $c, string $key) => [
                'key' => $key,
                'enabled' => (bool) data_get($map, "{$key}.enabled", $c['default']),
                'text' => (string) (data_get($map, "{$key}.text") ?: $c['text']),
            ])
            ->values()
            ->all();
    }

    /**
     * List dari Repeater → map untuk disimpan. Baris tanpa `key` yang dikenal
     * dibuang: Repeater bisa menyisakan baris kosong, dan kunci asing tidak
     * boleh mengendap di kolom JSON.
     */
    public static function clausesFromForm(?array $rows): array
    {
        return collect($rows ?? [])
            ->values()
            ->filter(fn($row) => isset(self::CLAUSES[$row['key'] ?? '']))
            ->mapWithKeys(fn($row) => [$row['key'] => [
                'enabled' => (bool) ($row['enabled'] ?? false),
                'text' => trim((string) ($row['text'] ?? '')),
            ]])
            ->all();
    }

    public function clauseEnabled(string $key): bool
    {
        return (bool) data_get(
            $this->clauses,
            "{$key}.enabled",
            self::CLAUSES[$key]['default'] ?? false
        );
    }

    /** Teks klausul; jatuh ke teks bawaan bila BV mengosongkannya. */
    public function clauseText(string $key): string
    {
        $text = trim((string) data_get($this->clauses, "{$key}.text"));

        return $text !== '' ? $text : (self::CLAUSES[$key]['text'] ?? '');
    }

    /**
     * Add-ons yang benar-benar terisi. Repeater Filament gampang meninggalkan
     * baris kosong; baris tanpa isi tidak boleh sampai tercetak jadi ayat hampa.
     *
     * @return array<int, array{title: string, text: string}>
     */
    public function activeAddons(): array
    {
        return collect($this->addons ?? [])
            ->map(fn($a) => [
                'title' => trim((string) ($a['title'] ?? '')),
                'text' => trim((string) ($a['text'] ?? '')),
            ])
            ->filter(fn($a) => $a['text'] !== '')
            ->values()
            ->all();
    }

    // ───────────────────────── E-SIGN ─────────────────────────

    /**
     * Terbitkan (atau pakai ulang) link tanda tangan publik.
     * Token dipakai ulang supaya link yang sudah dikirim ke KOL lewat WhatsApp
     * tidak mati begitu tombolnya diklik dua kali.
     */
    public function generatePublicToken(): string
    {
        if (blank($this->public_token)) {
            $this->public_token = \Illuminate\Support\Str::random(48);
        }

        // Draft → active: link sudah terbit, SPK menunggu tanda tangan KOL.
        if ($this->status === 'draft') {
            $this->status = 'active';
        }

        $this->save();

        return $this->public_token;
    }

    public function revokePublicToken(): void
    {
        $this->update(['public_token' => null]);
    }

    public function getPublicUrlAttribute(): ?string
    {
        return $this->public_token
            ? route('spk.public', ['token' => $this->public_token])
            : null;
    }

    public function isSigned(): bool
    {
        return filled($this->signed_at);
    }

    /**
     * Cocokkan data yang diisi KOL di langkah verifikasi.
     * Perbandingan case-insensitive & abai spasi ganda — ini gerbang "orang yang
     * benar", bukan uji ketelitian mengetik.
     */
    public function matchesVerification(string $spkNumber, string $name, ?string $platform): bool
    {
        $norm = fn(?string $v) => preg_replace('/\s+/', ' ', trim(mb_strtolower((string) $v)));

        if ($norm($spkNumber) !== $norm($this->spk_number)) {
            return false;
        }

        if ($norm($name) !== $norm($this->pihak_kedua_nama_lengkap)) {
            return false;
        }

        // Channel bisa kosong pada SPK yang dibuat manual — jangan menolak
        // karena data yang memang tidak kita punya.
        $channel = $this->dataKol?->channel ?: $this->mediaPlanKol?->channel;

        return blank($channel) || $norm($platform) === $norm($channel);
    }

    /**
     * Simpan tanda tangan KOL. $dataUrl = PNG data URL dari canvas.
     * Idempoten-aman: pemanggil wajib cek isSigned() dulu (lihat controller),
     * supaya tanda tangan yang sudah sah tidak bisa ditimpa lewat replay POST.
     */
    public function signByKol(string $dataUrl, ?string $ip = null): void
    {
        $prefix = 'data:image/png;base64,';
        $binary = base64_decode(substr($dataUrl, strlen($prefix)), true);

        if ($binary === false) {
            throw new \InvalidArgumentException('Gambar tanda tangan tidak valid.');
        }

        $path = "signatures/spk-{$this->id}-kol.png";
        \Illuminate\Support\Facades\Storage::disk('public')->put($path, $binary);

        $this->update([
            'signature_path' => $path,
            'signed_at' => now(),
            'signed_ip' => $ip,
            'status' => 'signed',
        ]);
    }

    /** Nomor WhatsApp KOL dalam format internasional tanpa tanda plus. */
    public function whatsappNumber(): ?string
    {
        $raw = preg_replace('/\D/', '', (string) $this->dataKol?->wa_number);

        if (blank($raw)) {
            return null;
        }

        return match (true) {
            str_starts_with($raw, '62') => $raw,
            str_starts_with($raw, '0') => '62' . substr($raw, 1),
            default => $raw,
        };
    }

    public function whatsappMessage(): string
    {
        return implode("\n", array_filter([
            'Hello Kak, berikut e-SPK nya yaa.',
            '',
            'Campaign: ' . ($this->nama_campaign ?: '-'),
            'No. SPK: ' . $this->spk_number,
            'Nominal: ' . $this->formatted_nominal . ' (di luar pajak)',
            '',
            'Silakan tanda tangani di link berikut:',
            $this->public_url,
            '',
            'Untuk verifikasi, siapkan No. SPK & nama lengkap sesuai KTP ya.',
        ]));
    }

    public function whatsappUrl(): ?string
    {
        $number = $this->whatsappNumber();

        return $number
            ? 'https://wa.me/' . $number . '?text=' . rawurlencode($this->whatsappMessage())
            : null;
    }

    /**
     * Terbitkan SPK untuk SATU KOL saja pada budget ini.
     * Gerbangnya sama dengan createFromBudget: hanya item ber-status `approved`,
     * dan semua SOW milik KOL itu tetap digabung jadi satu SPK.
     *
     * @return static|null null bila KOL sudah punya SPK atau tidak punya item approved
     */
    public static function createForKol(InternalBudget $budget, int $mediaPlanKolId): ?static
    {
        $items = static::approvedItemsQuery($budget)
            ->where('media_plan_kol_id', $mediaPlanKolId)
            ->get();

        if ($items->isEmpty()) {
            return null;
        }

        return static::buildForKol($budget, $mediaPlanKolId, $items, static::budgetContext($budget));
    }

    /** Sudah ada SPK untuk KOL ini di budget ini? Dipakai untuk menyembunyikan tombol. */
    public static function existsForKol(InternalBudget $budget, int $mediaPlanKolId): bool
    {
        return static::where('internal_budget_id', $budget->id)
            ->where('media_plan_kol_id', $mediaPlanKolId)
            ->exists();
    }

    /**
     * Item approved yang KOL-nya BELUM punya SPK di budget ini.
     * Penyaringan dilakukan di level query — Eloquent Collection::except()
     * memanggil getKey() pada tiap item, jadi tidak bisa dipakai ke hasil groupBy().
     */
    protected static function approvedItemsQuery(InternalBudget $budget)
    {
        $sudahAda = static::where('internal_budget_id', $budget->id)
            ->pluck('media_plan_kol_id')
            ->filter()
            ->all();

        return $budget->items()
            ->where('status', 'approved')
            ->whereNotNull('media_plan_kol_id')
            ->whereNotIn('media_plan_kol_id', $sudahAda)
            ->with('mediaPlanKol.dataKol');
    }

    /** Data yang sama untuk semua SPK dari satu budget — diambil sekali, bukan per KOL. */
    protected static function budgetContext(InternalBudget $budget): array
    {
        $mediaPlan = $budget->mediaPlan;

        return [
            'mediaPlan' => $mediaPlan,
            'clientId' => $mediaPlan?->bvSales?->client?->id,
            'formBriefId' => $mediaPlan
                ? FormBrief::where('bv_sales_id', $mediaPlan->bv_sales_id)->latest('id')->value('id')
                : null,
        ];
    }

    /** Satu-satunya tempat SPK dirakit — dipakai jalur batch maupun per-KOL. */
    protected static function buildForKol(
        InternalBudget $budget,
        int $mediaPlanKolId,
        iterable $items,
        array $konteks,
    ): static {
        $items = collect($items);
        $kol = $items->first()->mediaPlanKol;
        $dataKol = $kol?->dataKol;
        $mediaPlan = $konteks['mediaPlan'];
        $nominal = (float) $items->sum(fn($i) => (float) ($i->rate_base ?? 0));

        return static::create([
            'spk_number' => static::generateNumber(),
            'tanggal_perjanjian' => now()->toDateString(),
            'client_id' => $konteks['clientId'],
            'form_brief_id' => $konteks['formBriefId'],
            'internal_budget_id' => $budget->id,
            'media_plan_kol_id' => $mediaPlanKolId,
            'data_kol_id' => $kol?->data_kol_id,

            'pihak_kedua_nama_lengkap' => $dataKol?->full_name ?: $kol?->name,
            'pihak_kedua_nama_akun' => static::accountLabel($kol, $dataKol),
            'pihak_kedua_nik' => $dataKol?->nik,
            'pihak_kedua_alamat' => $dataKol?->address ?: $kol?->domisili,

            'nama_campaign' => $mediaPlan?->campaign_name,
            'sow_disepakati' => static::sowLines($items),
            'timeline_kerja_sama' => static::timeline($mediaPlan),

            'nominal_kesepakatan' => $nominal,
            'nominal_terbilang' => static::terbilang($nominal),
            'atas_nama_rekening' => $dataKol?->bank_account_name ?: $dataKol?->full_name,
            'nomor_rekening' => $dataKol?->bank_account_number,
            'nama_bank' => $dataKol?->bank_name,
            'kantor_cabang_bank' => $dataKol?->bank_branch,
            'termin_pembayaran_1' => static::TERMIN_1_DEFAULT,
            'clauses' => static::defaultClauses(),

            'status' => 'draft',
        ]);
    }

    /** "justeenff (TikTok)" */
    protected static function accountLabel(?MediaPlanKol $kol, ?DataKol $dataKol): ?string
    {
        $username = $dataKol?->username ?: $kol?->name;
        $channel = $dataKol?->channel ?: $kol?->channel;

        if (blank($username)) {
            return null;
        }

        return $channel ? "{$username} ({$channel})" : $username;
    }

    /** Rincian SOW per item: "1x TikTok Video" */
    protected static function sowLines(iterable $items): string
    {
        $lines = [];

        foreach ($items as $item) {
            $qty = (int) ($item->qty ?: 1);
            $lines[] = "{$qty}x " . trim((string) $item->scope_item);
        }

        return implode("\n", $lines);
    }

    /** "Juli 2026" dari periode campaign; fallback timeline Form Brief. */
    protected static function timeline(?MediaPlan $mediaPlan): ?string
    {
        $start = $mediaPlan?->campaign_period_start;

        if (! $start) {
            return null;
        }

        $start = \Carbon\Carbon::parse($start);

        return static::MONTHS_ID[$start->month] . ' ' . $start->year;
    }
}
