<?php

namespace App\Service;

use App\Models\DataKol;
use PhpOffice\PhpSpreadsheet\Cell\DataValidation;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use RuntimeException;
use Throwable;

/**
 * Ambil profil dari service scraping lalu simpan sebagai baris DataKol
 * (ingat: 1 baris = 1 channel, KOL dikelompokkan lewat kolom `username`).
 *
 * Dipakai dua tempat — tombol "New Data KOL" di halaman daftar dan "Tambah Channel"
 * di halaman edit. Dulu logikanya digandakan di ListDataKols; sekarang satu tempat.
 */
class KolProfileImporter
{
    /**
     * Batas sekali impor massal. Ditahan di 10 karena scraping-nya berurutan dan
     * sinkron — 10 akun sudah puluhan detik, lebih dari itu request-nya berisiko
     * kena timeout PHP/nginx sebelum selesai.
     */
    public const MAX_BULK = 10;

    /**
     * Jatah waktu PHP untuk MENGERJAKAN SATU BARIS (detik), dipasang ulang tiap
     * iterasi lewat set_time_limit(). Harus lebih besar dari jumlah timeout HTTP
     * dua panggilan berurutan (profil + video) yang diatur di AppServiceProvider —
     * kalau tidak, yang menghentikan proses adalah max_execution_time (FatalError,
     * tak bisa ditangkap) alih-alih timeout HTTP yang rapi.
     */
    public const BATAS_WAKTU_PER_BARIS = 60;

    /** Channel yang punya service scraping. Sisanya (Threads, Facebook, X, Talent) manual. */
    public const SCRAPABLE = [
        'Instagram' => InstagramService::class,
        'Tiktok' => TiktokService::class,
        'Youtube Channels' => YoutubeChannelsService::class,
        'Youtube Shorts' => YoutubeShortsService::class,
    ];

    /**
     * Pola URL profil resmi tiap channel. Dipakai untuk merapikan link yang
     * di-paste user: "tiktok.com/windahbasudara" (tanpa @) tetap bisa di-scrape
     * karena username-nya diekstrak, tapi kalau disimpan apa adanya link itu
     * membuka halaman 404 saat diklik dari tabel.
     */
    private const POLA_URL_PROFIL = [
        'Instagram' => 'https://www.instagram.com/%s/',
        'Tiktok' => 'https://www.tiktok.com/@%s',
        'Youtube Channels' => 'https://www.youtube.com/@%s',
        'Youtube Shorts' => 'https://www.youtube.com/@%s',
    ];

    /** URL profil kanonik dari username hasil scraping; null bila channel tak dikenal. */
    public static function canonicalUrl(string $channel, string $username): ?string
    {
        $pola = self::POLA_URL_PROFIL[$channel] ?? null;
        $username = ltrim(trim($username), '@');

        return ($pola && $username !== '') ? sprintf($pola, $username) : null;
    }

    /** @return array<string, string> Untuk dipakai langsung sebagai options Select. */
    public static function channelOptions(): array
    {
        return array_combine(
            array_keys(self::SCRAPABLE),
            ['Instagram', 'TikTok', 'YouTube Channels', 'YouTube Shorts'],
        );
    }

    /**
     * Template .xlsx: kolom A channel (dropdown), kolom B link.
     *
     * Dropdown-nya data validation Excel, jadi user memilih dari daftar dan tidak
     * bisa salah ketik "YouTube" untuk "Youtube Channels". Daftarnya diambil dari
     * SCRAPABLE — channel baru otomatis ikut. Dibuat on-demand lewat route, bukan
     * disimpan di public/, supaya tidak pernah basi.
     */
    public static function templateXlsx(): string
    {
        $channels = array_keys(self::SCRAPABLE);

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Import KOL');

        $sheet->fromArray([['channel', 'link']], null, 'A1');
        $sheet->getStyle('A1:B1')->getFont()->setBold(true);
        $sheet->getColumnDimension('A')->setWidth(22);
        $sheet->getColumnDimension('B')->setWidth(55);

        // Satu baris contoh supaya format link-nya jelas.
        $sheet->fromArray([[$channels[0], 'https://www.instagram.com/username/']], null, 'A2');

        // Baris data = header + MAX_BULK. Dropdown dipasang ke seluruh rentang itu.
        for ($baris = 2; $baris <= self::MAX_BULK + 1; $baris++) {
            $validasi = $sheet->getCell("A{$baris}")->getDataValidation();
            $validasi->setType(DataValidation::TYPE_LIST)
                ->setErrorStyle(DataValidation::STYLE_STOP)
                ->setAllowBlank(true)
                ->setShowDropDown(true)
                ->setShowErrorMessage(true)
                ->setErrorTitle('Channel tidak valid')
                ->setError('Pilih channel dari daftar.')
                ->setPromptTitle('Channel')
                ->setPrompt('Pilih salah satu channel yang didukung sistem.')
                // Daftar inline harus diapit kutip ganda dan dipisah koma.
                ->setFormula1('"' . implode(',', $channels) . '"');
        }

        $writer = new Xlsx($spreadsheet);

        ob_start();
        $writer->save('php://output');
        $isi = (string) ob_get_clean();

        $spreadsheet->disconnectWorksheets();

        return $isi;
    }

    /**
     * Baca file upload (.xlsx atau .csv — IOFactory yang menentukan) jadi baris
     * siap impor.
     *
     * @return array{rows: list<array{channel: string, link_userprofile: string}>, errors: list<string>}
     */
    public static function parseFile(string $path): array
    {
        try {
            $sheet = IOFactory::load($path)->getActiveSheet();
        } catch (Throwable $e) {
            return ['rows' => [], 'errors' => ['File tidak terbaca: ' . $e->getMessage()]];
        }

        return self::parseRows($sheet->toArray(null, true, false, false));
    }

    /**
     * Validasi & normalisasi baris mentah. Baris yang channel-nya tidak dikenal
     * DIBUANG dan dilaporkan — lebih baik user tahu ada yang dilewati daripada
     * diam-diam mengimpor sebagian.
     *
     * @param  array<array<int, mixed>>  $baris  Kolom 0 = channel, kolom 1 = link.
     * @return array{rows: list<array{channel: string, link_userprofile: string}>, errors: list<string>}
     */
    public static function parseRows(array $baris): array
    {
        // Cocokkan channel tanpa peduli huruf besar-kecil, tapi simpan ejaan kanonik.
        $kanonik = [];
        foreach (array_keys(self::SCRAPABLE) as $channel) {
            $kanonik[mb_strtolower($channel)] = $channel;
        }

        $rows = [];
        $errors = [];

        foreach ($baris as $i => $kolom) {
            $channel = trim((string) ($kolom[0] ?? ''));
            $link = trim((string) ($kolom[1] ?? ''));

            // Baris header dilewati, bukan dianggap error.
            if (mb_strtolower($channel) === 'channel') {
                continue;
            }

            if ($link === '') {
                continue;
            }

            $cocok = $kanonik[mb_strtolower($channel)] ?? null;

            if (! $cocok) {
                $nomor = $i + 1;
                $errors[] = "Baris {$nomor}: channel \"{$channel}\" tidak dikenal.";

                continue;
            }

            $rows[] = ['channel' => $cocok, 'link_userprofile' => $link];
        }

        if (count($rows) > self::MAX_BULK) {
            $dibuang = count($rows) - self::MAX_BULK;
            $errors[] = "{$dibuang} baris terakhir dilewati — maksimal " . self::MAX_BULK . ' per impor.';
            $rows = array_slice($rows, 0, self::MAX_BULK);
        }

        return ['rows' => $rows, 'errors' => $errors];
    }

    /** @return array<string, mixed> */
    public function fetchProfile(string $channel, string $url): array
    {
        $service = self::SCRAPABLE[$channel] ?? null;

        if (! $service) {
            throw new RuntimeException("Channel {$channel} belum didukung auto-fetch.");
        }

        $profile = (new $service())->getProfile($url);

        if (! $profile) {
            throw new RuntimeException("Profil tidak ditemukan di {$channel}.");
        }

        return $profile;
    }

    /**
     * Simpan profil yang sudah di-fetch. `$username` diisi saat menambah channel ke
     * KOL yang SEDANG DIBUKA: username hasil scraping ditimpa supaya barisnya
     * mengelompok ke KOL itu (handle asli tetap tersimpan di `link_userprofile`).
     *
     * @param  array<string, mixed>  $profile
     */
    public function save(array $profile, string $channel, string $url, ?string $username = null): DataKol
    {
        $data = $this->toRow($profile, $channel, $url);

        if ($username !== null) {
            $data['username'] = $username;
        }

        // 1 username pada channel yang sama hanya boleh 1 baris.
        $existing = DataKol::where('username', $data['username'])
            ->where('channel', $channel)
            ->first();

        if ($existing) {
            $existing->update($data);

            return $existing;
        }

        return DataKol::create($data);
    }

    public function import(string $channel, string $url, ?string $username = null): DataKol
    {
        return $this->save($this->fetchProfile($channel, $url), $channel, $url, $username);
    }

    /**
     * @param  array<array{channel: string, link_userprofile: string}>  $rows
     * @param  (callable(int $selesai, int $total, string $channel, string $url): void)|null  $onProgress
     *         Dipanggil SEBELUM tiap baris di-fetch, untuk streaming progres ke UI.
     * @return array{created: int, updated: int, failed: list<string>, mismatched: list<string>, first: ?DataKol, rows: list<array{channel: string, url: string, ok: bool, username: ?string, followers: ?int, message: ?string}>}
     */
    public function importMany(array $rows, ?string $username = null, ?callable $onProgress = null): array
    {
        $hasil = ['created' => 0, 'updated' => 0, 'failed' => [], 'mismatched' => [], 'first' => null, 'rows' => []];

        $antre = array_values(array_filter(
            array_slice($rows, 0, self::MAX_BULK),
            fn($row) => ! empty($row['channel']) && trim((string) ($row['link_userprofile'] ?? '')) !== '',
        ));

        $total = count($antre);

        foreach ($antre as $i => $row) {
            $channel = $row['channel'];
            $url = trim((string) $row['link_userprofile']);

            if ($onProgress) {
                $onProgress($i + 1, $total, $channel, $url);
            }

            // Tiap baris memanggil API yang bisa makan puluhan detik. Tanpa reset,
            // max_execution_time PHP (30 dtk) menghitung seluruh batch dan mematikan
            // request di tengah jalan — fatal, tidak bisa ditangkap, hasil separuh
            // jalan hilang dari layar. Reset membuat batas berlaku per baris.
            if (function_exists('set_time_limit')) {
                @set_time_limit(self::BATAS_WAKTU_PER_BARIS);
            }

            try {
                $profile = $this->fetchProfile($channel, $url);
                $record = $this->save($profile, $channel, $url, $username);
            } catch (Throwable $e) {
                $hasil['failed'][] = "{$channel} {$url}: {$e->getMessage()}";
                $hasil['rows'][] = [
                    'channel' => $channel, 'url' => $url, 'ok' => false,
                    'username' => null, 'followers' => null, 'message' => $e->getMessage(),
                ];

                continue;
            }

            $record->wasRecentlyCreated ? $hasil['created']++ : $hasil['updated']++;
            $hasil['first'] ??= $record;

            // Handle di platform beda dengan username KOL — bukan error, tapi jangan diam-diam.
            $beda = $username !== null && $profile['username'] !== $username;
            if ($beda) {
                $hasil['mismatched'][] = "{$channel}: @{$profile['username']}";
            }

            $hasil['rows'][] = [
                'channel' => $channel,
                'url' => $url,
                'ok' => true,
                'username' => $profile['username'],
                'followers' => (int) $profile['followers_count'],
                'message' => $beda ? "Handle asli @{$profile['username']} digabung ke @{$username}" : null,
            ];
        }

        return $hasil;
    }

    /**
     * @param  array<string, mixed>  $profile
     * @return array<string, mixed>
     */
    private function toRow(array $profile, string $channel, string $url): array
    {
        $data = [
            // Simpan bentuk kanonik, bukan mentahan yang di-paste — kalau tidak,
            // ikon "buka profil" di tabel bisa mengarah ke halaman 404.
            'link_userprofile' => self::canonicalUrl($channel, (string) $profile['username']) ?? $url,
            'channel' => $channel,
            'username' => $profile['username'],
            'followers' => $profile['followers_count'],
            'tier' => $profile['tier'],
            'engagement_rate' => $profile['engagement_rate'],
            'engagements' => $profile['total_engagements'],
            'impressions' => $profile['average_impressions'],
            'status' => 'New List',
            'terakhir_update' => now()->format('Y-m-d'),
            'notes' => $this->buildNotes($profile, $channel),
        ];

        $categoryName = $profile['category_name']
            ?: (($profile['business_category_name'] ?? null) !== 'None' ? ($profile['business_category_name'] ?? null) : null);
        if (! empty($categoryName)) {
            $data['category'] = [$categoryName];
        }

        if (! empty($profile['full_name'])) {
            $data['full_name'] = $profile['full_name'];
        }
        if (! empty($profile['business_email'])) {
            $data['email'] = $profile['business_email'];
            $data['contact'] = $profile['business_email'];
        }
        if (! empty($profile['business_phone_number'])) {
            $data['wa_number'] = $profile['business_phone_number'];
            if (empty($profile['business_email'])) {
                $data['contact'] = $profile['business_phone_number'];
            }
        }

        return $data;
    }

    /** @param  array<string, mixed>  $profile */
    private function buildNotes(array $profile, string $channel): string
    {
        $notes = [];

        if (! empty($profile['biography'])) {
            $notes[] = "Bio: {$profile['biography']}";
        }
        if (! empty($profile['is_verified'])) {
            $notes[] = '✓ Verified Account';
        }
        if (! empty($profile['is_business_account'])) {
            $notes[] = 'Business Account';
        }

        $notes[] = "Tier: {$profile['tier']}";
        $notes[] = "Engagement Rate: {$profile['engagement_rate']}%";
        $notes[] = 'Avg Impressions: ' . number_format($profile['average_impressions']);
        $notes[] = 'Avg Likes: ' . number_format($profile['average_likes']);
        $notes[] = 'Avg Comments: ' . number_format($profile['average_comments']);
        $notes[] = 'Following: ' . number_format($profile['following_count']);

        $mediaLabel = match ($channel) {
            'Tiktok', 'Youtube Channels' => 'Videos',
            'Youtube Shorts' => 'Shorts',
            default => 'Posts',
        };
        $notes[] = "{$mediaLabel}: " . number_format($profile['media_count']);

        if (! empty($profile['external_url'])) {
            $notes[] = "Website: {$profile['external_url']}";
        }

        return implode("\n", $notes);
    }
}
