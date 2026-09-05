<?php

namespace App\Service;

use App\Models\BvCampaignKol;
use App\Models\BvCampign;
use App\Models\MediaPlanCalcSetting;

/**
 * Migrasi sheet "KOL Insights" → tab KOL Performance sebuah campaign.
 *
 * Tiap TAB = satu SOW (Visit / Stitch / Leasing / Main KOL) untuk satu platform,
 * isinya daftar KOL, link postingannya, dan angka performa saat sheet dibuat.
 *
 * Butuh grid (butuhGrid): kolom "LINK Post" isinya cuma tulisan "Link" — URL-nya
 * tersimpan sebagai HYPERLINK sel, yang tidak ikut lewat endpoint values biasa.
 *
 * Angka dari sheet dipakai sebagai NILAI AWAL. Begitu satu baris pernah ditarik
 * "Fetch All Performance" (last_fetched_at terisi), migrasi ulang tidak lagi
 * menyentuh angkanya — angka dari postingan aslinya selalu lebih baru.
 */
class KolInsightsSheetMigration extends SheetMigration
{
    private ?int $campaignId = null;

    private ?string $namaTab = null;

    private const PLATFORM_ALIAS = [
        'tiktok' => 'tiktok',
        'tik tok' => 'tiktok',
        'instagram' => 'instagram',
        'ig' => 'instagram',
        'youtube' => 'youtube',
        'yt' => 'youtube',
        'threads' => 'threads',
    ];

    /** Kolom angka bulat; sheet kadang menulisnya pecahan (Reach =70%*Views). */
    private const METRIK_BULAT = ['likes', 'saves', 'shares', 'reposts', 'comments', 'reach', 'views', 'total_engagement'];

    public function label(): string
    {
        return 'KOL Insights (KOL Performance Campaign)';
    }

    public function defaultSheetName(): ?string
    {
        return null; // SOW ditentukan tab yang dipilih, jadi tak ada bawaan
    }

    public function butuhGrid(): bool
    {
        return true;
    }

    public function untukCampaign(?int $id): static
    {
        $this->campaignId = $id ? (int) $id : null;

        return $this;
    }

    /** SOW & platform dibaca dari NAMA TAB, jadi profil perlu tahu tab mana. */
    public function untukTab(?string $nama): static
    {
        $this->namaTab = $nama;

        return $this;
    }

    public function aliases(): array
    {
        return [
            'row_no' => ['no'],
            'creator_name' => ['kol name'],
            'platform' => ['platform'],
            'post_url' => ['link post'],
            'followers' => ['followers'],
            'posting_date' => ['posting date'],
            'likes' => ['likes'],
            'saves' => ['saves'],
            'shares' => ['share', 'shares'],
            'comments' => ['comments'],
            'reach' => ['reach'],
            'views' => ['views'],
            'reposts' => ['repost', 'reposts'],
            'total_engagement' => ['engagement'],
            // Kolom "E.R" sheet TIDAK dipetakan — ER dihitung ulang, lihat parseRows().
            // "Repost" (khusus tab Instagram) tak punya padanan kolom di
            // bv_campaign_kols, jadi sengaja tidak dipetakan.
        ];
    }

    public function previewColumns(): array
    {
        return ['creator_name', 'sow', 'tier', 'platform', 'content_type', 'post_url', 'views', 'total_engagement', 'engagement_rate'];
    }

    protected function requiredField(): string
    {
        return 'creator_name';
    }

    /**
     * Satu tab bisa memuat BEBERAPA tabel bertumpuk — tab Visit berisi blok
     * "Micro KOL" lalu "Nano KOL", masing-masing dengan baris judul + header
     * sendiri. parseRows() bawaan hanya mengenal satu header, sehingga judul
     * blok berikutnya ikut terbaca sebagai nama KOL.
     *
     * @param  array<int, array<int, array{v: mixed, h: string|null}>>  $rows
     * @return array<int, array<string, mixed>>
     */
    public function parseRows(array $rows): array
    {
        [$sow, $platformTab] = $this->sowDanPlatform();
        $headers = $this->indeksHeader($rows);

        if ($headers === []) {
            return [];
        }

        $peta = $this->mapHeaders($this->nilaiBaris($rows[$headers[0]]));
        $kolom = array_flip($peta); // field => indeks kolom
        $items = [];

        foreach ($headers as $n => $header) {
            $akhir = $headers[$n + 1] ?? count($rows);
            $tier = $this->tierDariLabel((string) $this->sel($rows, $header - 1, 1));

            // Nama KOL sering ditulis sekali lalu selnya di-MERGE ke bawah untuk
            // beberapa postingan (tab "Raden Rauf": satu nama, tiga platform).
            // Sel hasil merge datang kosong dari Google, jadi tanpa ini dua
            // baris terakhirnya hilang tanpa jejak.
            $namaTerakhir = null;

            for ($i = $header + 1; $i < $akhir; $i++) {
                // Baris data selalu bernomor di kolom "No"; baris judul tidak.
                $bernomor = ! isset($kolom['row_no']) || is_numeric($this->sel($rows, $i, $kolom['row_no']));

                if (! $bernomor) {
                    continue;
                }

                $nama = trim((string) $this->sel($rows, $i, $kolom['creator_name'] ?? -1));

                if ($nama !== '') {
                    $namaTerakhir = $nama;
                } else {
                    $nama = (string) $namaTerakhir;
                }

                if ($nama === '') {
                    continue;
                }

                $url = $this->hyperlink($rows, $i, $kolom['post_url'] ?? -1);
                $platform = $platformTab
                    ?? $this->normalisasiPlatform((string) $this->sel($rows, $i, $kolom['platform'] ?? -1))
                    ?? $this->platformDariUrl($url);

                $item = [
                    'sow' => $sow,
                    'tier' => $tier,
                    'creator_name' => $nama,
                    'username' => $this->username($nama, $url),
                    'platform' => $platform,
                    'content_type' => $platform ? $this->contentType($platform, $url) : null,
                    'post_url' => $url,
                    'followers_count' => $this->parseFollowers($this->sel($rows, $i, $kolom['followers'] ?? -1)),
                    'posting_date' => self::toDate($this->sel($rows, $i, $kolom['posting_date'] ?? -1)),
                    'status' => $url ? 'posted' : 'pending',
                    '_row' => $i + 1,
                ];

                foreach (self::METRIK_BULAT as $field) {
                    $nilai = $this->sel($rows, $i, $kolom[$field] ?? -1);
                    $item[$field] = is_numeric($nilai) ? (int) round((float) $nilai) : 0;
                }

                // ER DIHITUNG ULANG, bukan disalin dari kolom "E.R" sheet.
                // Sheet-nya tidak seragam: tab TikTok memakai Engagement/Views,
                // tab Instagram & "Raden Rauf" memakai Engagement/Reach. Reach
                // tidak bisa di-fetch dari platform mana pun, jadi memakainya
                // membuat ER membeku selamanya. Satu rumus untuk semua baris
                // berarti angkanya tidak melompat begitu di-fetch.
                $item['total_engagement'] = $item['likes'] + $item['comments'] + $item['shares'] + $item['saves'] + $item['reposts'];
                $item['engagement_rate'] = $item['views'] > 0
                    ? round($item['total_engagement'] / $item['views'] * 100, 4)
                    : ($item['followers_count'] > 0
                        ? round($item['total_engagement'] / $item['followers_count'] * 100, 4)
                        : 0);
                $item['er_type'] = $item['views'] > 0 ? 'views' : 'followers';

                $item['_note'] = match (true) {
                    ! $platform => 'Platform tidak terbaca — baris dilewati.',
                    ! $url => 'Tanpa link postingan — performanya tidak bisa di-fetch.',
                    default => null,
                };

                $items[] = $item;
            }
        }

        return $items;
    }

    /**
     * @param  array<int, array<string, mixed>>  $items
     * @return array{success: int, skipped: int, failed: int, notes: array<int, string>}
     */
    public function persist(array $items): array
    {
        $hasil = ['success' => 0, 'skipped' => 0, 'failed' => 0, 'notes' => []];

        $campaign = $this->campaignId ? BvCampign::find($this->campaignId) : null;

        if (! $campaign) {
            $hasil['failed'] = count($items);
            $hasil['notes'][] = 'Campaign tujuan belum dipilih — tidak ada yang disimpan.';

            return $hasil;
        }

        foreach ($items as $item) {
            if (blank($item['creator_name'] ?? null) || blank($item['platform'] ?? null)) {
                $hasil['skipped']++;
                continue;
            }

            try {
                $this->simpanBaris($campaign, $item);
                $hasil['success']++;
            } catch (\Throwable $e) {
                $hasil['failed']++;
                $hasil['notes'][] = "Baris {$item['_row']} ({$item['creator_name']}): {$e->getMessage()}";
            }
        }

        return $hasil;
    }

    private function simpanBaris(BvCampign $campaign, array $item): void
    {
        // Satu KOL bisa punya beberapa postingan (IG + TikTok, atau beberapa
        // SOW), jadi yang unik adalah link-nya — bukan namanya.
        $kunci = ['campaign_id' => $campaign->id] + ($item['post_url']
            ? ['post_url' => $item['post_url']]
            : ['creator_name' => $item['creator_name'], 'platform' => $item['platform'], 'post_url' => null]);

        $kol = BvCampaignKol::firstOrNew($kunci);

        $kol->fill([
            'campaign_id' => $campaign->id,
            'creator_name' => $item['creator_name'],
            'platform' => $item['platform'],
            'content_type' => $item['content_type'],
            'post_url' => $item['post_url'],
            'status' => $item['status'],
            // Tab KOL Performance memfilter brief_status = 'approved' sementara
            // default kolomnya 'draft'. Tanpa ini barisnya tersimpan tapi TIDAK
            // PERNAH muncul di layar. Lagipula KOL yang postingannya sudah ada
            // memang sudah lewat tahap brief.
            'brief_status' => 'approved',
        ]);

        // Identitas yang sudah terisi (koreksi manual / hasil fetch) tidak
        // ditimpa tebakan dari sheet.
        foreach (['username', 'followers_count', 'posting_date', 'tier'] as $kolom) {
            if (filled($item[$kolom] ?? null) && blank($kol->{$kolom})) {
                $kol->{$kolom} = $item[$kolom];
            }
        }

        // Baris yang pernah di-fetch punya angka dari postingan aslinya —
        // selalu lebih baru daripada angka di sheet. Jangan dimundurkan.
        if (blank($kol->last_fetched_at)) {
            $kol->fill(array_intersect_key($item, array_flip([...self::METRIK_BULAT, 'engagement_rate', 'er_type'])));
        }

        $kol->save();
    }

    /* ---------------------------------------------------------------------
     | Pembacaan grid
     * ------------------------------------------------------------------- */

    /** @param array<int, array<int, array{v: mixed, h: string|null}>> $rows */
    private function sel(array $rows, int $baris, int $kolom): mixed
    {
        return $kolom < 0 ? null : ($rows[$baris][$kolom]['v'] ?? null);
    }

    private function hyperlink(array $rows, int $baris, int $kolom): ?string
    {
        if ($kolom < 0) {
            return null;
        }

        $url = $rows[$baris][$kolom]['h'] ?? null;

        // Sebagian baris menulis URL-nya langsung, bukan sebagai hyperlink.
        if (! $url) {
            $isi = trim((string) ($rows[$baris][$kolom]['v'] ?? ''));
            $url = str_starts_with(strtolower($isi), 'http') ? $isi : null;
        }

        return $url ?: null;
    }

    /** @return array<int, mixed> nilai saja, untuk mapHeaders() milik induk */
    private function nilaiBaris(array $row): array
    {
        return array_map(fn ($c) => $c['v'] ?? null, $row);
    }

    /** @return array<int, int> indeks tiap baris header di dalam tab */
    private function indeksHeader(array $rows): array
    {
        $hasil = [];

        foreach ($rows as $i => $row) {
            if (isset(array_flip($this->mapHeaders($this->nilaiBaris($row)))['creator_name'])) {
                $hasil[] = $i;
            }
        }

        return $hasil;
    }

    /* ---------------------------------------------------------------------
     | Penafsiran
     * ------------------------------------------------------------------- */

    /**
     * SOW & platform diambil dari NAMA TAB, bukan judul di dalam sheet: judul
     * itu sisa salin-tempel — tab "KOL Leasing TikTok" tertulis "Visit TikTok".
     *
     * @return array{0:string, 1:?string}
     */
    private function sowDanPlatform(): array
    {
        $judul = trim((string) $this->namaTab);
        $sisa = trim(preg_replace('/^KOL\s+/i', '', $judul));

        foreach (self::PLATFORM_ALIAS as $token => $slug) {
            if (preg_match('/(^|\s)'.preg_quote($token, '/').'(\s|$)/i', $sisa)) {
                return [trim(preg_replace('/(^|\s)'.preg_quote($token, '/').'(\s|$)/i', ' ', $sisa)) ?: $judul, $slug];
            }
        }

        // Tab tanpa nama platform (mis. "Raden Rauf") berarti platformnya
        // ditulis per baris.
        return [$sisa !== '' ? $sisa : $judul, null];
    }

    private function tierDariLabel(string $label): ?string
    {
        foreach (MediaPlanCalcSetting::current()->tiers() as $tier) {
            if (preg_match('/(^|\s)'.preg_quote((string) $tier['label'], '/').'(\s|$)/i', $label)) {
                return (string) $tier['label'];
            }
        }

        return null;
    }

    private function normalisasiPlatform(string $raw): ?string
    {
        $raw = strtolower(trim($raw));

        return $raw === '' ? null : (self::PLATFORM_ALIAS[$raw] ?? null);
    }

    private function platformDariUrl(?string $url): ?string
    {
        return match (true) {
            ! $url => null,
            str_contains($url, 'tiktok.') => 'tiktok',
            str_contains($url, 'instagram.') => 'instagram',
            str_contains($url, 'youtu') => 'youtube',
            str_contains($url, 'threads.') => 'threads',
            default => null,
        };
    }

    /** Sheet tidak menyebut jenis konten; bentuk URL-nya yang menyebutkan. */
    private function contentType(string $platform, ?string $url): string
    {
        $url = strtolower((string) $url);

        return match ($platform) {
            'instagram' => match (true) {
                str_contains($url, '/stories/') => 'story',
                str_contains($url, '/p/') => 'feed',
                default => 'reels',
            },
            'tiktok' => str_contains($url, '/photo/') ? 'photos' : 'video',
            'youtube' => str_contains($url, '/shorts/') ? 'short' : 'video',
            default => 'post',
        };
    }

    private function username(string $nama, ?string $url): ?string
    {
        if ($url && preg_match('#/@([^/?]+)#', $url, $m)) {
            return $m[1];
        }

        return str_starts_with($nama, '@') ? ltrim(trim($nama), '@') : null;
    }

    /**
     * Sheet menulis follower dalam macam-macam bentuk: 4033, "13K", "6,1K"
     * (desimal koma), "19.0K" (desimal titik), "2.1M".
     */
    public function parseFollowers(mixed $raw): ?int
    {
        if (is_numeric($raw)) {
            return (int) $raw;
        }

        $teks = strtoupper(trim((string) $raw));

        if ($teks === '' || ! preg_match('/^([\d.,]+)\s*([KM])?$/', $teks, $m)) {
            return null;
        }

        $pengali = ['' => 1, 'K' => 1_000, 'M' => 1_000_000][$m[2] ?? ''] ?? 1;

        // Satu pemisah diikuti 1-2 digit = desimal ("6,1K" / "19.0K");
        // selain itu pemisah ribuan ("1.317.470").
        $angka = preg_match('/^\d+[.,]\d{1,2}$/', $m[1])
            ? str_replace(',', '.', $m[1])
            : str_replace([',', '.'], '', $m[1]);

        return (int) round((float) $angka * $pengali);
    }
}
