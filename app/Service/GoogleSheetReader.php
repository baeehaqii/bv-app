<?php

namespace App\Service;

use Google\Client as GoogleClient;
use Google\Service\Sheets as GoogleSheets;
use RuntimeException;

/**
 * Pembaca Google Spreadsheet privat lewat service account.
 *
 * Pull, bukan push: Laravel yang membaca sheet-nya sendiri, jadi tidak perlu
 * Apps Script yang menempel di tiap file. Diporting dari service migrasi SOP
 * Siproper (docs/sync/migrasi-sop-service.md di project itu).
 */
class GoogleSheetReader
{
    private ?GoogleClient $client = null;

    /**
     * spreadsheetId dari URL Google Sheets, atau dikembalikan apa adanya bila
     * yang diberikan sudah berupa ID mentah.
     */
    public static function extractId(string $urlOrId): ?string
    {
        $urlOrId = trim($urlOrId);

        if ($urlOrId === '') {
            return null;
        }

        if (preg_match('#/spreadsheets/d/([a-zA-Z0-9-_]+)#', $urlOrId, $m)) {
            return $m[1];
        }

        if (preg_match('#[?&]id=([a-zA-Z0-9-_]+)#', $urlOrId, $m)) {
            return $m[1];
        }

        return preg_match('#^[a-zA-Z0-9-_]{20,}$#', $urlOrId) ? $urlOrId : null;
    }

    /** Kredensial sudah terpasang? Dipakai untuk menyembunyikan menu migrasi. */
    public static function configured(): bool
    {
        $path = (string) config('services.google.credentials');

        return $path !== '' && is_file($path);
    }

    private function client(): GoogleClient
    {
        if ($this->client) {
            return $this->client;
        }

        $credentials = (string) config('services.google.credentials');

        if (! is_file($credentials)) {
            throw new RuntimeException(
                "Kredensial Google service account tidak ada di: {$credentials}. "
                . 'Letakkan berkas JSON-nya di sana, atau set GOOGLE_SERVICE_ACCOUNT ke path lain.'
            );
        }

        $client = new GoogleClient();
        $client->setAuthConfig($credentials);
        $client->setScopes([GoogleSheets::SPREADSHEETS_READONLY]);

        if ($impersonate = config('services.google.impersonate')) {
            $client->setSubject($impersonate);
        }

        return $this->client = $client;
    }

    /**
     * Judul tab pertama bila $sheetName tidak diberikan atau tidak ketemu.
     */
    private function resolveSheetTitle(GoogleSheets $service, string $spreadsheetId, ?string $sheetName): string
    {
        $titles = collect($service->spreadsheets->get($spreadsheetId)->getSheets())
            ->map(fn($s) => $s->getProperties()->getTitle());

        if ($sheetName) {
            $cocok = $titles->first(fn(string $t) => strcasecmp($t, $sheetName) === 0);

            if ($cocok) {
                return $cocok;
            }
        }

        return $titles->first() ?? 'Sheet1';
    }

    /**
     * Judul tab jadi range A1 yang aman. Judul bertanda spasi, garis miring, atau
     * kutip WAJIB dikutip tunggal; kutip di dalamnya digandakan.
     * Contoh: Tambah/Kurang → 'Tambah/Kurang' ; O'Brien → 'O''Brien'.
     */
    private function quoteSheetTitle(string $title): string
    {
        return "'" . str_replace("'", "''", $title) . "'";
    }

    /**
     * Seluruh baris satu tab, 0-based (tiap baris array sel).
     *
     * Sengaja UNFORMATTED_VALUE + SERIAL_NUMBER: tanggal datang sebagai serial
     * Google (hari 0 = 1899-12-30) yang tidak ambigu, bukan teks "01/02/2026"
     * yang bisa terbaca 1 Februari atau 2 Januari tergantung locale.
     *
     * @return array<int, array<int, mixed>>
     */
    public function readRows(string $spreadsheetId, ?string $sheetName = null): array
    {
        $service = new GoogleSheets($this->client());

        return $service->spreadsheets_values->get(
            $spreadsheetId,
            $this->quoteSheetTitle($this->resolveSheetTitle($service, $spreadsheetId, $sheetName)),
            ['valueRenderOption' => 'UNFORMATTED_VALUE', 'dateTimeRenderOption' => 'SERIAL_NUMBER'],
        )->getValues() ?? [];
    }

    /** @return array<int, string> nama semua tab, untuk dropdown pemilihan. */
    public function sheetNames(string $spreadsheetId): array
    {
        return collect((new GoogleSheets($this->client()))->spreadsheets->get($spreadsheetId)->getSheets())
            ->map(fn($s) => $s->getProperties()->getTitle())
            ->values()
            ->all();
    }
}
