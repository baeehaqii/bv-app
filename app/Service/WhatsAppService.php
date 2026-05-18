<?php

namespace App\Service;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class WhatsAppService
{
    protected string $webhookUrl;

    public function __construct()
    {
        $this->webhookUrl = config('services.webhook.n8n', '');

        if (empty($this->webhookUrl)) {
            Log::stack(['single', 'whatsapp'])->error('[WA] N8N_WEBHOOK_URL belum di-set di .env');
        }
    }

    /**
     * Kirim pesan WA ke satu nomor via n8n webhook.
     */
    public function send(string $phone, string $message): bool
    {
        $phone = $this->normalizePhone($phone);

        return $this->dispatch($phone, $message);
    }

    /**
     * Kirim pesan WA ke banyak nomor.
     */
    public function sendBulk(array $phones, string $message): void
    {
        foreach ($phones as $phone) {
            $this->send($phone, $message);
        }
    }

    private function dispatch(string $phone, string $message): bool
    {
        if (empty($this->webhookUrl)) {
            return false;
        }

        Log::stack(['single', 'whatsapp'])->info('[WA] Kirim notifikasi', [
            'phone'      => $phone,
            'webhook'    => $this->webhookUrl,
            'message'    => $message,
        ]);

        try {
            $response = Http::timeout(15)->post($this->webhookUrl, [
                'phoneNumber' => $phone,
                'message'     => $message,
            ]);

            Log::stack(['single', 'whatsapp'])->info('[WA] Response n8n', [
                'phone'  => $phone,
                'status' => $response->status(),
                'body'   => $response->body(),
            ]);

            return $response->successful();
        } catch (\Throwable $e) {
            Log::stack(['single', 'whatsapp'])->error('[WA] Gagal kirim ke n8n', [
                'phone'   => $phone,
                'error'   => $e->getMessage(),
            ]);

            return false;
        }
    }

    private function normalizePhone(string $phone): string
    {
        $cleaned = preg_replace('/\D/', '', $phone);

        if (str_starts_with($cleaned, '62')) {
            return $cleaned;
        }

        if (str_starts_with($cleaned, '0')) {
            return '62' . substr($cleaned, 1);
        }

        return '62' . $cleaned;
    }
}
