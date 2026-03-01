<?php

namespace App\Service;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * WhatsApp Notification Service
 *
 * Service dasar untuk mengirim pesan WhatsApp.
 * Saat ini menggunakan log sebagai placeholder.
 * Ke depan bisa diintegrasikan dengan provider WA API
 * seperti Fonnte, Wablas, WooWA, dll.
 */
class WhatsAppService
{
    /**
     * Kirim pesan WhatsApp ke satu nomor.
     */
    public static function send(string $phone, string $message): bool
    {
        // Normalisasi nomor telepon (pastikan format internasional)
        $phone = static::normalizePhone($phone);

        // TODO: Ganti dengan integrasi provider WA API
        // Contoh implementasi Fonnte:
        // $response = Http::withHeaders([
        //     'Authorization' => config('services.whatsapp.token'),
        // ])->post(config('services.whatsapp.url', 'https://api.fonnte.com/send'), [
        //     'target'  => $phone,
        //     'message' => $message,
        // ]);

        Log::channel('daily')->info('[WhatsApp] Pesan terkirim', [
            'phone' => $phone,
            'message' => $message,
        ]);

        return true;
    }

    /**
     * Kirim pesan ke banyak nomor sekaligus.
     */
    public static function sendBulk(array $phones, string $message): void
    {
        foreach ($phones as $phone) {
            static::send($phone, $message);
        }
    }

    /**
     * Normalisasi format nomor telepon ke format internasional (62xxx).
     */
    protected static function normalizePhone(string $phone): string
    {
        // Hapus karakter non-digit
        $phone = preg_replace('/[^0-9]/', '', $phone);

        // Ganti awalan 0 menjadi 62
        if (str_starts_with($phone, '0')) {
            $phone = '62' . substr($phone, 1);
        }

        // Pastikan ada awalan 62
        if (!str_starts_with($phone, '62')) {
            $phone = '62' . $phone;
        }

        return $phone;
    }
}
