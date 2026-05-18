<?php

namespace App\Filament\Resources\FormBriefs\Pages;

use App\Filament\Resources\FormBriefs\FormBriefResource;
use App\Mail\FormBriefSubmittedMailable;
use App\Service\BvNotificationService;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Mail;

class CreateFormBrief extends CreateRecord
{
    protected static string $resource = FormBriefResource::class;

    protected function afterCreate(): void
    {
        $brief = $this->record;

        // Kirim email hanya jika ada email client yang bisa dituju
        $email = $brief->submitted_by_email
            ?? $brief->bvSales?->client?->email
            ?? $brief->client?->email;

        if ($email) {
            try {
                Mail::to($email)->send(new FormBriefSubmittedMailable($brief));
            } catch (\Throwable $e) {
                Notification::make()
                    ->title('Brief tersimpan, namun email gagal dikirim')
                    ->body($e->getMessage())
                    ->warning()
                    ->send();
            }
        }

        // Notifikasi WA ke grup utama
        try {
            app(BvNotificationService::class)->briefSubmitted($brief);
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::warning('[CreateFormBrief] Notifikasi WA gagal: ' . $e->getMessage());
        }
    }
}
