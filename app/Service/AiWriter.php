<?php

namespace App\Service;

use RuntimeException;

use function Laravel\Ai\agent;

/**
 * Satu pintu ke model AI (Gemini, lihat config/ai.php).
 *
 * Ada dua pemakai — ringkasan Campaign Summary dan kartu KOL — dan keduanya
 * butuh penjagaan yang sama: key kosong dan balasan kosong harus jadi pesan
 * yang bisa dibaca user, bukan exception mentah dari HTTP client.
 */
class AiWriter
{
    /** Key belum diisi = fitur AI-nya disembunyikan, bukan error saat diklik. */
    public static function configured(): bool
    {
        return filled(config('ai.providers.' . config('ai.default') . '.key'));
    }

    /**
     * @throws RuntimeException bila key belum diisi atau model tidak menjawab
     */
    public static function write(string $instructions, string $facts): string
    {
        if (! self::configured()) {
            throw new RuntimeException('GEMINI_API_KEY belum diisi di .env.');
        }

        $teks = trim((string) agent($instructions)->prompt($facts)->text);

        if ($teks === '') {
            throw new RuntimeException('Model tidak mengembalikan teks apa pun.');
        }

        return $teks;
    }
}
