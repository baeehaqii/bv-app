<?php

namespace App\Service;

use App\Models\BvSales;
use App\Models\DataKol;
use App\Models\InternalBudget;
use App\Models\KolRateCard;
use App\Models\MasterPph;
use App\Models\MediaPlan;
use App\Models\MediaPlanKol;
use Illuminate\Support\Facades\DB;

/**
 * Migrasi sheet "KOL List" → daftar KOL sebuah Media Plan Internal.
 *
 * Bentuk sheet-nya BERBEDA dari profil lain: satu KOL menempati beberapa baris.
 * Baris pertama berisi identitas KOL (username, channel, followers, …) plus
 * scope of work pertamanya; baris-baris di bawahnya hanya berisi SOW tambahan
 * dengan kolom identitas kosong. Jadi baris tanpa username BUKAN baris kosong —
 * ia milik KOL di atasnya, dan digabung jadi satu item.
 *
 * Acuan kolomnya berkas `[INT] Bir Kawan Senja - KOL List.xlsx`; tiap tier
 * (Nano/Micro/Macro/Homeless Media) satu tab dengan susunan kolom yang sama.
 *
 * Satu baris sheet menghasilkan EMPAT hal, bukan satu:
 *   1. DataKol      — username, channel, followers, link, tier, ER, avg views.
 *   2. KolRateCard  — satu per scope of work, berisi rate dari sheet. Bukan
 *      dipakai migrasi ini (rate_base langsung dari sheet), tapi supaya Media
 *      Plan BERIKUTNYA untuk KOL yang sama sudah punya rate card.
 *   3. MediaPlanKol — barisnya di KOL List, ditautkan ke DataKol di atas.
 *   4. InternalBudgetItem — satu per SOW, lalu dihitung.
 *
 * Kolom Subtotal Rate, Gross Up PPH Coefficient, Tax, MU PPh*, MU**, Published
 * Rate***, Rounded, dan Margin % di sheet TIDAK diambil angkanya. Semua itu
 * turunan yang aplikasi hitung sendiri dari rate card + tipe pajak lewat
 * MediaPlanForm::computeBudgetFigures() — rumus yang sama dipakai halaman Media
 * Plan. Mengimpor angka sheet berarti menanam ulang hasil koefisien PPh lama
 * yang sudah terbukti salah.
 */
class MediaPlanSheetMigration extends SheetMigration
{
    /**
     * Deal di Sales Activity Tracker yang jadi tujuan — WAJIB dipilih user.
     *
     * Yang dipilih deal-nya, bukan Media Plan-nya langsung: Media Plan memang
     * turunan dari deal (BvSales::ensureMediaPlanExists()), jadi memilih deal
     * berarti KOL-nya pasti mendarat di Media Plan yang benar — dan kalau Media
     * Plan-nya belum ada, dibuatkan dengan cara yang sama seperti alur normal.
     */
    private ?int $bvSalesId = null;

    public function untukSales(?int $id): static
    {
        $this->bvSalesId = $id;

        return $this;
    }

    public function label(): string
    {
        return 'KOL List → Media Plan Internal';
    }

    public function defaultSheetName(): ?string
    {
        // Tab per tier; tidak ada yang lebih benar dari yang lain, jadi user pilih.
        return null;
    }

    public function aliases(): array
    {
        return [
            'name' => ['username', 'nama kol', 'kol'],
            'pic' => ['pic'],
            'status' => ['status'],
            'links' => ['link', 'link profil'],
            'channel' => ['channel', 'platform'],
            'categories' => ['categories', 'kategori'],
            'followers' => ['followers'],
            'tier' => ['tier'],
            'er_percent' => ['er persen', 'er'],
            'impression' => ['avg views', 'impression'],
            'engagement' => ['engagement'],
            'domisili' => ['dom', 'domisili'],
            'notes' => ['notes', 'catatan'],
            // Blok Scope of Work sebelah kanan: qty + item + rate per baris.
            // Qty/Item/Rate SENGAJA tidak pakai alias: sheet punya DUA blok scope
            // of work berdampingan ("Request Client" dan rencana internal) dengan
            // judul yang sama persis. Yang benar ditentukan posisinya di
            // mapHeaders(), bukan namanya.
            // Dua kolom ini yang menentukan tipe pajak KOL-nya.
            'pph_coefficient' => ['gross up pph coefficient', 'coefficient'],
            'tax' => ['tax'],
        ];
    }

    public function previewColumns(): array
    {
        return ['name', 'channel', 'pic', 'status', 'tier', 'followers', 'sow_ringkas', 'rate'];
    }

    protected function requiredField(): string
    {
        return 'name';
    }

    public function ignoredHeaders(): array
    {
        return [
            'no',
            // Blok "Request Client" di sebelah kiri — rencana dari client, bukan
            // rencana internal yang dipakai Media Plan.
            'scope of work', 'qty', 'item', 'rate',
            'top',
            // Semuanya turunan yang InternalBudgetItem::recalculate() hitung
            // sendiri dari rate + tipe pajak.
            'subtotal rate', 'mu pph', 'mu', 'published rate', 'rounded', 'margin persen',
        ];
    }

    /**
     * Judul kolom sheet ini menempati DUA baris: baris utama berisi "Scope of
     * Work" dan "Rate", baris di bawahnya berisi "Qty" dan "Item" untuk tiap
     * blok. Keduanya digabung supaya kolom Qty/Item ikut terbaca.
     *
     * @param  array<int, array<int, mixed>>  $rows
     */
    public function headerRow(array $rows): array
    {
        $i = $this->headerRowIndex($rows);
        $utama = $rows[$i] ?? [];
        $bawah = $rows[$i + 1] ?? [];

        foreach ($bawah as $kolom => $judul) {
            if (blank($utama[$kolom] ?? null)) {
                $utama[$kolom] = $judul;
            }
        }

        return $utama;
    }

    /** Judulnya dua baris: label utama + sub-label Qty/Item. */
    public function headerRowSpan(): int
    {
        return 2;
    }

    /**
     * Sheet punya DUA blok scope of work berdampingan dengan judul kolom yang
     * sama persis: "Request Client" (rencana dari client) dan rencana internal.
     * Yang dipakai Media Plan adalah blok internal — dikenali dari posisinya,
     * yaitu tiga kolom tepat SEBELUM "Subtotal Rate". Kalau dicocokkan lewat
     * nama, blok client yang menang karena letaknya lebih kiri, dan rate yang
     * terbaca jadi harga ke client, bukan cost KOL.
     *
     * @param  array<int, mixed>  $headerRow
     * @return array<int, string>
     */
    public function mapHeaders(array $headerRow): array
    {
        $peta = parent::mapHeaders($headerRow);

        $subtotal = collect($headerRow)
            ->search(fn($judul) => self::normalize((string) $judul) === 'subtotal rate');

        if ($subtotal === false) {
            return $peta;
        }

        // Buang sisa pemetaan ke tiga field ini, lalu pasang yang benar.
        $peta = array_filter($peta, fn(string $f) => ! in_array($f, ['sow_qty', 'sow_item', 'rate'], true));

        $peta[$subtotal - 3] = 'sow_qty';
        $peta[$subtotal - 2] = 'sow_item';
        $peta[$subtotal - 1] = 'rate';

        ksort($peta);

        return $peta;
    }

    /**
     * Gabungkan baris-baris SOW ke KOL di atasnya.
     *
     * @param  array<int, array<int, mixed>>  $rows
     * @return array<int, array<string, mixed>>
     */
    public function parseRows(array $rows): array
    {
        $baris = parent::parseRows($rows);
        $items = [];

        foreach ($baris as $b) {
            $sow = $this->sowDari($b);

            if (blank($b['name'] ?? null)) {
                // Lanjutan KOL sebelumnya. Tanpa induk, baris ini memang yatim.
                if ($items !== [] && $sow) {
                    $items[count($items) - 1]['scope_items'][] = $sow;
                }

                continue;
            }

            $b['scope_items'] = $sow ? [$sow] : [];
            unset($b['sow_qty'], $b['sow_item']);

            $items[] = $b;
        }

        // Ringkasan SOW baru bisa dibuat setelah semua barisnya terkumpul.
        foreach ($items as $i => $item) {
            $items[$i]['sow_ringkas'] = collect($item['scope_items'])->pluck('item')->implode(', ');
            $items[$i]['_note'] = $item['_note'] ?? $this->catatanBaris($item);
        }

        return $items;
    }

    /**
     * Peringatan isi baris yang perlu dilihat manusia — ditampilkan di preview,
     * tidak menghalangi migrasi.
     */
    private function catatanBaris(array $item): ?string
    {
        if ($item['scope_items'] === []) {
            return 'KOL ini tidak punya scope of work.';
        }

        // Beberapa baris sheet salah kolom: followers-nya ditulis di kolom Tier.
        // Jangan ditebak-tebak sendiri — cukup ditandai supaya dirapikan di sheet.
        if (blank($item['followers'] ?? null) || (int) $item['followers'] === 0) {
            if (is_numeric($item['tier'] ?? null)) {
                return 'Followers kosong, tapi kolom Tier berisi angka (' . $item['tier']
                    . ') — kemungkinan tertukar kolom di sheet.';
            }

            return 'Followers kosong di sheet.';
        }

        return null;
    }

    /** @return array{qty: int, item: string, rate: float}|null */
    private function sowDari(array $b): ?array
    {
        $item = trim((string) ($b['sow_item'] ?? ''));

        if ($item === '') {
            return null;
        }

        return [
            'qty' => (int) (self::toNumber($b['sow_qty'] ?? null) ?: 1),
            'item' => $item,
            'rate' => (float) (self::toNumber($b['rate'] ?? null) ?: 0),
        ];
    }

    protected function refine(array $item): array
    {
        $item['followers'] = (int) (self::toNumber($item['followers'] ?? null) ?: 0);
        $item['impression'] = (int) (self::toNumber($item['impression'] ?? null) ?: 0);
        $item['engagement'] = (int) (self::toNumber($item['engagement'] ?? null) ?: 0);
        $item['er_percent'] = self::toNumber($item['er_percent'] ?? null);
        $item['pph_coefficient'] = self::toNumber($item['pph_coefficient'] ?? null);
        $item['tax'] = self::toNumber($item['tax'] ?? null);

        return $item;
    }

    public function persist(array $items): array
    {
        $hasil = ['success' => 0, 'skipped' => 0, 'failed' => 0, 'notes' => []];

        $mediaPlan = $this->mediaPlanTujuan($hasil);

        if (! $mediaPlan) {
            $hasil['skipped'] = count($items);

            return $hasil;
        }

        DB::transaction(function () use ($items, $mediaPlan, &$hasil) {
            foreach ($items as $item) {
                if (blank($item['name'] ?? null)) {
                    $hasil['skipped']++;

                    continue;
                }

                try {
                    $this->simpanSatu($item, $mediaPlan, $hasil);
                    $hasil['success']++;
                } catch (\Throwable $e) {
                    $hasil['failed']++;
                    $hasil['notes'][] = "Baris {$item['_row']} ({$item['name']}): {$e->getMessage()}";
                }
            }
        });

        return $hasil;
    }

    /** @param array{notes: array<int, string>} $hasil */
    private function mediaPlanTujuan(array &$hasil): ?MediaPlan
    {
        if (! $this->bvSalesId) {
            $hasil['notes'][] = 'Deal di Sales Activity Tracker belum dipilih — tidak ada yang disimpan.';

            return null;
        }

        $sales = BvSales::find($this->bvSalesId);

        if (! $sales) {
            $hasil['notes'][] = 'Deal yang dipilih sudah tidak ada.';

            return null;
        }

        // Deal yang belum sampai tahap briefing belum punya Media Plan; dibuatkan
        // lewat method milik model itu sendiri supaya isinya sama dengan alur normal.
        $sales->ensureMediaPlanExists();

        $mediaPlan = $sales->mediaPlan()->first();

        if (! $mediaPlan) {
            $hasil['notes'][] = 'Media Plan untuk deal ini gagal disiapkan.';
        }

        return $mediaPlan;
    }

    private function simpanSatu(array $item, MediaPlan $mediaPlan, array &$hasil): void
    {
        $dataKol = $this->dataKolUntuk($item, $hasil);
        $this->simpanRateCards($dataKol, $item);

        // Satu KOL bisa punya beberapa channel, jadi kuncinya nama + channel.
        $kol = MediaPlanKol::firstOrNew([
            'media_plan_id' => $mediaPlan->id,
            'name' => $item['name'],
            'channel' => $item['channel'] ?: '',
        ]);

        foreach (['pic', 'status', 'categories', 'followers', 'tier', 'er_percent',
            'impression', 'engagement', 'domisili', 'notes'] as $field) {
            if (($item[$field] ?? null) !== null && $item[$field] !== '') {
                $kol->{$field} = $item[$field];
            }
        }

        [$vendorTaxType, $masterPphId] = $this->tipePajakUntuk($item, $hasil);

        $kol->data_kol_id = $dataKol->id;
        $kol->tipe_pajak_kol = $masterPphId;

        if (filled($item['links'] ?? null)) {
            $kol->links = [$item['links']];
        }

        if ($item['scope_items'] ?? []) {
            $kol->scope_items = collect($item['scope_items'])->pluck('item')->all();
            // Satu qty berlaku untuk semua budget item KOL ini — begitu cara
            // EditMediaPlan menyimpannya, jadi diikuti supaya tidak beda sendiri.
            $kol->qty = max(1, (int) collect($item['scope_items'])->max('qty'));
        }

        $kol->row_number ??= (int) ($item['_row'] ?? 0);
        // Penanda asal-usul: baris hasil migrasi boleh ber-rate 0 dan dilengkapi
        // manual belakangan, sedangkan baris yang diinput lewat form tetap wajib
        // punya rate card.
        $kol->imported_at = now();
        $kol->save();

        $this->generateBudgetItems($mediaPlan, $kol, $item, $vendorTaxType);

        // rate & CPI/CPV/CPE baris KOL diturunkan dari budget item, bukan diisi
        // tangan — method milik model itu sendiri yang menghitungnya.
        $kol->syncRateFromBudget();
    }

    /**
     * Baris KOL Data untuk KOL ini. Nama, channel, followers, dan link dari sheet
     * memang seharusnya mendarat di KOL Data juga — dari situlah rate card,
     * analyzer, dan SPK mengambil datanya.
     */
    private function dataKolUntuk(array $item, array &$hasil): DataKol
    {
        $kol = DataKol::firstOrNew([
            'username' => $item['name'],
            'channel' => $item['channel'] ?: '-',
        ]);

        if (! $kol->exists) {
            $hasil['notes'][] = "KOL baru masuk KOL Data: \"{$item['name']}\" ({$item['channel']}).";
        }

        // link_userprofile NOT NULL; kalau sheet tidak mengisinya, bentuk kanonik
        // dari username dipakai supaya barisnya tetap bisa dibuka & di-scrape.
        $kol->link_userprofile = $item['links']
            ?: ($kol->link_userprofile
                ?: (\App\Service\KolProfileImporter::canonicalUrl((string) $item['channel'], (string) $item['name']) ?? '-'));

        foreach ([
            'followers' => 'followers',
            'tier' => 'tier',
            'er_percent' => 'engagement_rate',
            'impression' => 'average_views',
            'engagement' => 'engagements',
        ] as $dari => $ke) {
            if (filled($item[$dari] ?? null)) {
                $kol->{$ke} = $item[$dari];
            }
        }

        $kol->save();

        return $kol;
    }

    /**
     * Rate tiap SOW dari sheet disimpan sebagai rate card KOL — bukan ditulis
     * langsung ke baris Media Plan. Di app ini rate card yang jadi sumber:
     * MediaPlanForm::computeRateFromSow() membaca dari sana saat budget item
     * dibuat, dan halaman Media Plan menolak menyimpan KOL yang rate card-nya 0.
     */
    private function simpanRateCards(DataKol $dataKol, array $item): void
    {
        foreach ($item['scope_items'] ?? [] as $sow) {
            if ($sow['rate'] <= 0) {
                continue;
            }

            KolRateCard::updateOrCreate(
                [
                    'data_kol_id' => $dataKol->id,
                    'channel' => $dataKol->channel,
                    'sow' => $sow['item'],
                ],
                [
                    'rate' => $sow['rate'],
                    'valid_from' => now()->toDateString(),
                ],
            );
        }
    }

    /**
     * Tipe pajak dari kolom Coefficient + Tax di sheet.
     *
     * Sheet menulis 0,98 dan 0,11 — itu "PT PKP", yang di
     * InternalBudgetItem::calculateMuPph() rumusnya (Base/0,98)+(Base×0,11):
     * sama persis dengan kolom Z di sheet. Jadi mengikuti sheet dan memakai
     * perhitungan aplikasi bukan dua hal berbeda.
     *
     * @return array{0: string, 1: ?int} vendor_tax_type + master_pph_id
     */
    private function tipePajakUntuk(array $item, array &$hasil): array
    {
        $coef = (float) ($item['pph_coefficient'] ?? 0);
        // Tax di sheet pecahan (0,11); master menyimpan persen.
        $ppn = (float) ($item['tax'] ?? 0);
        $ppn = $ppn > 0 && $ppn <= 1 ? $ppn * 100 : $ppn;

        $jenis = match (true) {
            $coef === 0.0 => null,
            abs($coef - 0.98) < 0.0001 && $ppn > 0 => 'PT PKP',
            abs($coef - 0.98) < 0.0001 => 'PT Non PKP',
            abs($coef - 0.975) < 0.0001 => 'Pribadi',
            abs($coef - 0.995) < 0.0001 => 'CV',
            default => null,
        };

        if ($coef > 0 && ! $jenis) {
            $hasil['notes'][] = "Baris {$item['_row']}: koefisien {$coef}"
                . ($ppn ? " + PPN {$ppn}%" : '') . ' tidak dikenali, dipakai Pribadi.';
        }

        $jenis ??= 'Pribadi';

        return [$jenis, MasterPph::where('name', $jenis)->value('id')];
    }

    /**
     * Budget item per SOW. Angkanya TIDAK dihitung di sini.
     *
     * `rate_base` diambil LANGSUNG dari sheet: saat migrasi, KOL-nya memang
     * belum punya rate card, dan mengambil rate lewat rate card yang baru saja
     * kita tulis sendiri cuma menambah satu tahap pencocokan nama SOW yang bisa
     * meleset diam-diam jadi rate 0.
     *
     * Subtotal, gross up coefficient, tax, MU PPh, MU target, published rate,
     * rounded, dan margin diisi InternalBudgetItem::recalculate() lewat hook
     * `saving` miliknya sendiri — digerakkan `vendor_tax_type`. Menghitungnya
     * di sini percuma: hook itu menimpanya sedetik kemudian.
     */
    private function generateBudgetItems(MediaPlan $mediaPlan, MediaPlanKol $kol, array $item, string $vendorTaxType): void
    {
        // firstOrCreate lewat relasi, bukan $mediaPlan->internalBudget: properti
        // relasi ter-cache sejak KOL pertama, jadi KOL berikutnya masih melihat
        // null dan mencoba membuat budget kedua — yang ditolak unique index.
        $budget = $mediaPlan->internalBudget()->firstOrCreate([], ['status' => 'draft']);

        // Dibuat ulang, bukan ditambah: migrasi ulang tidak boleh menggandakan item.
        $kol->internalBudgetItems()->delete();

        $sortOrder = $budget->items()->max('sort_order') ?? 0;

        foreach ($item['scope_items'] ?? [] as $sow) {
            $budget->items()->create([
                'media_plan_kol_id' => $kol->id,
                'scope_item' => $sow['item'],
                'qty' => max(1, (int) $sow['qty']),
                'rate_base' => $sow['rate'],
                'vendor_tax_type' => $vendorTaxType,
                'master_pph_id' => $kol->tipe_pajak_kol,
                'sort_order' => ++$sortOrder,
            ]);
        }
    }
}
