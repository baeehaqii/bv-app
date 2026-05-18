<?php

namespace App\Service;

use App\Models\BvCampign;
use App\Models\BvQuotation;
use App\Models\FormBrief;
use App\Models\MediaPlan;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

/**
 * Pusat notifikasi seluruh event sistem BV.
 *
 * Setiap method mewakili 1 event bisnis.
 * WA = notifikasi utama, Email = backup.
 * Semua method tidak melempar exception — gagal diam-diam + log.
 */
class BvNotificationService
{
    public function __construct(protected WhatsAppService $wa) {}

    // -------------------------------------------------------------------------
    // Event: Brief baru masuk
    // -------------------------------------------------------------------------

    public function briefSubmitted(FormBrief $brief): void
    {
        $campaignName = $brief->campaign_name ?: $brief->title ?: '(tanpa nama)';
        $brand        = $brief->brand ?: '-';
        $budget       = $brief->budget ? 'Rp ' . number_format($brief->budget, 0, ',', '.') : '-';
        $deadline     = $brief->deadline_date?->translatedFormat('d M Y') ?? '-';
        $pic          = $brief->pic ?: $brief->submitted_by_name ?: '-';

        $message = "📋 *Brief Baru Masuk!*\n\n"
            . "📌 *Campaign:* {$campaignName}\n"
            . "🏢 *Brand:* {$brand}\n"
            . "💰 *Budget:* {$budget}\n"
            . "📅 *Deadline:* {$deadline}\n"
            . "👤 *PIC Client:* {$pic}\n"
            . "\nSilakan cek panel admin untuk detail.";

        $this->dispatchWa($message, 'briefSubmitted', ['brief_id' => $brief->id]);
    }

    // -------------------------------------------------------------------------
    // Event: Quotation link di-generate (siap dikirim ke client)
    // -------------------------------------------------------------------------

    public function quotationLinkGenerated(BvQuotation $quotation): void
    {
        $campaignName = $quotation->mediaPlan?->campaign_name
            ?? $quotation->internalBudget?->mediaPlan?->campaign_name
            ?? '-';
        $clientName   = $quotation->client_name ?: '-';
        $total        = $quotation->total_amount
            ? 'Rp ' . number_format($quotation->total_amount, 0, ',', '.')
            : '-';
        $url          = $quotation->public_url ?? '-';

        $message = "🔗 *Quotation Link Dibuat*\n\n"
            . "📌 *Campaign:* {$campaignName}\n"
            . "🏢 *Client:* {$clientName}\n"
            . "💰 *Total:* {$total}\n"
            . "🔗 *Link Review:* {$url}\n"
            . "\nLink sudah siap dikirim ke client.";

        $this->dispatchWa($message, 'quotationLinkGenerated', ['quotation_id' => $quotation->id]);
    }

    // -------------------------------------------------------------------------
    // Event: Budget/Quotation di-approve
    // -------------------------------------------------------------------------

    public function budgetApproved(MediaPlan $mediaPlan): void
    {
        $campaignName = $mediaPlan->campaign_name ?? '-';
        $brand        = $mediaPlan->brand ?? '-';
        $total        = $mediaPlan->internalBudget?->total_rounded
            ? 'Rp ' . number_format($mediaPlan->internalBudget->total_rounded, 0, ',', '.')
            : '-';

        $message = "✅ *Budget Disetujui!*\n\n"
            . "📌 *Campaign:* {$campaignName}\n"
            . "🏢 *Brand:* {$brand}\n"
            . "💰 *Total Approved:* {$total}\n"
            . "\nBudget sudah disetujui. Campaign siap dilanjutkan.";

        $this->dispatchWa($message, 'budgetApproved', ['media_plan_id' => $mediaPlan->id]);
    }

    // -------------------------------------------------------------------------
    // Event: Budget/Quotation di-reject
    // -------------------------------------------------------------------------

    public function budgetRejected(MediaPlan $mediaPlan, string $reason = ''): void
    {
        $campaignName = $mediaPlan->campaign_name ?? '-';
        $brand        = $mediaPlan->brand ?? '-';

        $message = "❌ *Budget Ditolak*\n\n"
            . "📌 *Campaign:* {$campaignName}\n"
            . "🏢 *Brand:* {$brand}\n"
            . ($reason ? "📝 *Alasan:* {$reason}\n" : '')
            . "\nSilakan revisi dan ajukan ulang.";

        $this->dispatchWa($message, 'budgetRejected', ['media_plan_id' => $mediaPlan->id]);
    }

    // -------------------------------------------------------------------------
    // Event: PIC di-assign ke campaign (Media Plan)
    // -------------------------------------------------------------------------

    public function picAssigned(MediaPlan $mediaPlan): void
    {
        $campaignName = $mediaPlan->campaign_name ?? '-';
        $brand        = $mediaPlan->brand ?? '-';

        $picSalesBd      = $mediaPlan->picSalesBd?->user?->name ?? '-';
        $picLeads        = $mediaPlan->picLeadsProject?->user?->name ?? '-';
        $picInternalIds  = $mediaPlan->pic_project_internal_ids ?? [];
        $picAm           = $mediaPlan->picAm?->user?->name ?? '-';

        $message = "👥 *PIC Campaign Di-assign*\n\n"
            . "📌 *Campaign:* {$campaignName}\n"
            . "🏢 *Brand:* {$brand}\n"
            . "👤 *PIC Sales/BD:* {$picSalesBd}\n"
            . "👤 *PIC Leads Project:* {$picLeads}\n"
            . "👤 *PIC AM:* {$picAm}\n"
            . (!empty($picInternalIds) ? "👥 *PIC Internal:* " . count($picInternalIds) . " orang\n" : '')
            . "\nSilakan cek detail di panel.";

        $this->dispatchWa($message, 'picAssigned', ['media_plan_id' => $mediaPlan->id]);

        // Notifikasi personal ke masing-masing PIC jika punya nomor WA
        $this->notifyPicPersonally($mediaPlan, $campaignName);
    }

    // -------------------------------------------------------------------------
    // Event: Campaign baru dibuat (Campaign Ongoing)
    // -------------------------------------------------------------------------

    public function campaignCreated(BvCampign $campaign, string $type = 'influencer'): void
    {
        $campaignName = $campaign->campaign_name ?? '-';
        $clientName   = $campaign->client?->nama_brand ?? '-';
        $dealValue    = $campaign->deal_value
            ? 'Rp ' . number_format($campaign->deal_value, 0, ',', '.')
            : '-';

        $period = '';
        if ($campaign->start_date && $campaign->end_date) {
            $period = $campaign->start_date->format('d M Y') . ' – ' . $campaign->end_date->format('d M Y');
        }

        $label = match ($type) {
            'influencer'   => 'Influencer / KOL',
            'social_media' => 'Social Media',
            default        => ucfirst($type),
        };

        $message = "🚀 *Campaign Baru Dibuat!*\n\n"
            . "📌 *Nama Campaign:* {$campaignName}\n"
            . "🏢 *Client:* {$clientName}\n"
            . "📋 *Tipe:* {$label}\n"
            . "💰 *Deal Value:* {$dealValue}\n"
            . ($period ? "📅 *Periode:* {$period}\n" : '')
            . "\nSilakan cek detail di panel admin.";

        $phones = $type === 'influencer'
            ? (array) config('services.notification.influencer_phones', [])
            : (array) config('services.notification.social_media_phones', []);

        $emails = $type === 'influencer'
            ? (array) config('services.notification.influencer_emails', [])
            : (array) config('services.notification.social_media_emails', []);

        // WA ke grup + phones spesifik
        $this->dispatchWa($message, 'campaignCreated', ['campaign_id' => $campaign->id]);
        $this->wa->sendBulk(array_filter($phones), $message);

        // Email backup
        $this->sendEmailBulk($emails, "🚀 Campaign Baru: {$campaignName}", $message);
    }

    // -------------------------------------------------------------------------
    // Internal helpers
    // -------------------------------------------------------------------------

    /**
     * Kirim WA ke grup utama (dari config NOTIFY_WA_GROUP_MAIN).
     */
    private function dispatchWa(string $message, string $event, array $context = []): void
    {
        $groupPhone = config('services.notification.wa_group_main', '');

        if (empty($groupPhone)) {
            Log::stack(['single', 'whatsapp'])->warning("[WA] NOTIFY_WA_GROUP_MAIN belum di-set, skip event: {$event}", $context);
            return;
        }

        try {
            $this->wa->send($groupPhone, $message);
        } catch (\Throwable $e) {
            Log::stack(['single', 'whatsapp'])->error("[WA] Gagal dispatch event: {$event}", array_merge($context, [
                'error' => $e->getMessage(),
            ]));
        }
    }

    /**
     * Kirim notifikasi personal ke setiap PIC yang punya nomor WA di profile mereka.
     */
    private function notifyPicPersonally(MediaPlan $mediaPlan, string $campaignName): void
    {
        $pics = collect([
            $mediaPlan->picSalesBd?->user,
            $mediaPlan->picLeadsProject?->user,
            $mediaPlan->picAm?->user,
        ])->filter();

        foreach ($pics as $user) {
            $phone = $user->phone ?? $user->whatsapp ?? null;
            if (!$phone) {
                continue;
            }

            $personalMsg = "👋 Halo *{$user->name}*,\n\n"
                . "Anda di-assign sebagai PIC pada campaign:\n"
                . "📌 *{$campaignName}*\n\n"
                . "Silakan cek detail di panel admin.";

            try {
                $this->wa->send($phone, $personalMsg);
            } catch (\Throwable $e) {
                Log::stack(['single', 'whatsapp'])->warning('[WA] Gagal notif personal PIC', [
                    'user_id' => $user->id,
                    'error'   => $e->getMessage(),
                ]);
            }
        }
    }

    private function sendEmailBulk(array $emails, string $subject, string $body): void
    {
        foreach (array_filter($emails) as $email) {
            try {
                Mail::raw($body, fn($mail) => $mail->to($email)->subject($subject));
            } catch (\Throwable $e) {
                Log::warning("[BvNotification] Gagal kirim email ke {$email}: " . $e->getMessage());
            }
        }
    }
}
