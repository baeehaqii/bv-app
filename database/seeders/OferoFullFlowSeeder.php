<?php

namespace Database\Seeders;

use App\Enums\SalesStatus;
use App\Models\BvSales;
use App\Models\BvSalesList;
use App\Models\CampaignKolRevision;
use App\Models\CampaignStoryline;
use App\Models\DataClient;
use App\Models\DataKol;
use App\Models\InternalBudgetItem;
use App\Models\MasterPph;
use App\Models\MediaPlan;
use App\Models\MediaPlanKol;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Auth;

/**
 * Skema campaign OFERO END-TO-END (digerakkan lewat transisi produksi, bukan insert manual):
 *   Media Plan Internal → Media Plan External (Review ke Client) → Quotation
 *   → Campaign On Going (brief konten BV+client, revisi 3x) → KOL Performa (metrik posting).
 *
 * Re-runnable: transisi hanya dijalankan bila belum melewati tahapnya.
 * Jalankan: php artisan db:seed --class=OferoFullFlowSeeder
 */
class OferoFullFlowSeeder extends Seeder
{
    /** KOL + rate + target metrik performa (1 SOW per KOL → 1 baris campaign). */
    private const KOLS = [
        [
            'username' => 'radenrauf', 'name' => 'Raden Rauf', 'channel' => 'Instagram',
            'scope' => 'IG Reels', 'rate' => 20_000_000,
            'followers' => 3_500_000, 'tier' => 'Macro', 'er' => 3.2, 'impression' => 1_250_000,
            'perf' => ['views' => 1_250_000, 'likes' => 92_000, 'comments' => 3_400, 'shares' => 1_800, 'saves' => 5_200],
            'paid' => true,
        ],
        [
            'username' => 'awkarin', 'name' => 'Awkarin', 'channel' => 'Instagram',
            'scope' => 'IG Reels', 'rate' => 12_000_000,
            'followers' => 2_100_000, 'tier' => 'Macro', 'er' => 2.8, 'impression' => 640_000,
            'perf' => ['views' => 640_000, 'likes' => 48_000, 'comments' => 1_500, 'shares' => 700, 'saves' => 2_100],
            'paid' => false,
        ],
        [
            'username' => 'windahbasudara', 'name' => 'Windah Basudara', 'channel' => 'Tiktok',
            'scope' => 'TikTok Video', 'rate' => 15_000_000,
            'followers' => 5_000_000, 'tier' => 'Mega', 'er' => 6.5, 'impression' => 3_200_000,
            'perf' => ['views' => 3_200_000, 'likes' => 410_000, 'comments' => 22_000, 'shares' => 15_000, 'saves' => 9_000],
            'paid' => false,
        ],
    ];

    public function run(): void
    {
        if (MasterPph::count() === 0) {
            $this->call([MasterPphSeeder::class, MasterMarginSeeder::class, MasterSowSeeder::class]);
        }
        $pphId = MasterPph::query()->min('id') ?? 1;

        // Login user agar hook yang pakai auth() (generateQuotation user_id, pic_internal) tidak null.
        if ($admin = User::first()) {
            Auth::login($admin);
        }

        // ── 1. Master: DataClient Ofero + DataKol ──────────────────────────
        $client = DataClient::updateOrCreate(
            ['nama_brand' => 'Ofero'],
            ['type' => 'direct', 'account_owner' => 'Gerry', 'status' => 'not_started', 'status_client' => 'aktif'],
        );

        foreach (self::KOLS as $k) {
            DataKol::updateOrCreate(
                ['username' => $k['username'], 'channel' => $k['channel']],
                [
                    'full_name' => $k['name'],
                    'link_userprofile' => "https://www.{$this->host($k['channel'])}/{$k['username']}",
                    'followers' => $k['followers'], 'tier' => $k['tier'],
                    'engagement_rate' => $k['er'], 'impressions' => $k['impression'],
                    'status' => 'active', 'tipe_pajak_kol' => $pphId,
                ],
            );
        }

        // ── 2. BvSales → BRIEFING (boot hook buat FormBrief + MediaPlan) ───
        $gerry = BvSalesList::firstOrCreate(['nama_sales' => 'Gerry']);
        $sales = BvSales::updateOrCreate(
            ['company_name' => 'Ofero', 'event_name' => 'Ofero Full Flow Demo 2026'],
            [
                'bv_sales_list_id' => $gerry->id,
                'status' => SalesStatus::PITCHING,
                'budget_propose' => 60_000_000,
                'start_date' => '2026-06-01', 'end_date' => '2026-07-31',
                'campaign_month' => 6, 'campaign_date' => '2026-06-01',
                'pic_media_plan' => 'Gerry',
            ],
        );

        if (! $sales->mediaPlan()->exists()) {
            $sales->update(['status' => SalesStatus::BRIEFING]); // → FormBrief + MediaPlan
        }
        $sales->refresh();

        // ── 3. Isi FormBrief (SOW) ─────────────────────────────────────────
        $sales->formBrief?->update([
            'sow' => "- IG Reels (1x)\n- TikTok Video (1x)",
            'campaign_name' => 'Ofero Full Flow Demo 2026',
            'brand' => 'Ofero',
            'campaign_objective' => 'Awareness produk Ofero via KOL Instagram & TikTok.',
            'criteria_of_kol' => 'Lifestyle & gaming, audiens Indonesia.',
            'status' => 'submitted',
            'submitted_at' => now(),
        ]);
        $sales->update(['brief_submit_date' => '2026-05-20']); // → maju ke Proposal Building

        // ── 4. MediaPlan detail + MediaPlanKol terpilih ────────────────────
        $mediaPlan = $sales->mediaPlan;
        $mediaPlan->update([
            'campaign_name' => 'Ofero Full Flow Demo 2026', 'brand' => 'Ofero',
            'campaign_type' => 'regular',
            'campaign_period_start' => '2026-06-01', 'campaign_period_end' => '2026-07-31',
            'platform' => 'Instagram', 'pic_sales_bd_id' => $gerry->id,
            'margin_type' => 'percent', 'margin_percent' => 30, 'use_global_margin' => true,
        ]);

        $mpKols = [];
        $row = 1;
        foreach (self::KOLS as $k) {
            $dataKol = DataKol::where(['username' => $k['username'], 'channel' => $k['channel']])->first();
            $mpKols[$k['name']] = MediaPlanKol::updateOrCreate(
                ['media_plan_id' => $mediaPlan->id, 'data_kol_id' => $dataKol->id],
                [
                    'row_number' => $row++, 'is_selected' => true, 'name' => $k['name'],
                    'channel' => $k['channel'], 'links' => [$dataKol->link_userprofile],
                    'followers' => $k['followers'], 'tier' => $k['tier'], 'er_percent' => $k['er'],
                    'impression' => $k['impression'], 'scope_items' => [$k['scope']],
                    'tipe_pajak_kol' => $pphId, 'status' => 'Locked', 'rate' => $k['rate'],
                    'margin_percent' => 30,
                ],
            );
        }

        // ── 5. MediaPlan → To Client (auto-generate InternalBudget) ────────
        if (! $mediaPlan->internalBudget()->exists()) {
            $mediaPlan->update(['status' => 'To Client']);
        }
        $budget = $mediaPlan->fresh()->internalBudget;

        // ── 6. Budget items: 1 item per KOL/SOW, status approved ───────────
        if ($budget->status !== 'approve_am') {
            $budget->items()->delete();
            $sort = 1;
            foreach (self::KOLS as $k) {
                $item = InternalBudgetItem::create([
                    'internal_budget_id' => $budget->id,
                    'media_plan_kol_id' => $mpKols[$k['name']]->id,
                    'master_pph_id' => $pphId,
                    'scope_item' => $k['scope'], 'qty' => 1, 'rate_base' => $k['rate'],
                    'sort_order' => $sort++, 'status' => 'approved',
                    'client_choice' => 'approved',
                    'client_feedback' => 'Disetujui client saat Review ke Client.',
                ]);
                $item->recalculate();
                $item->save();
            }
            $budget->recalculateTotals();

            // ── 7. Tahap "Review ke Client" (Media Plan External) ──────────
            $budget->generateReviewToken();
            $budget->update(['status' => 'review_client', 'review_submitted_at' => now()]);

            // ── 8. Quotation dari budget final ─────────────────────────────
            $budget->update(['status' => 'approve_client']);
            $quotation = $budget->generateQuotation();
            $quotation->update(['media_plan_id' => $mediaPlan->id, 'status' => 'accepted']);

            // ── 9. Approve AM (status final) ───────────────────────────────
            $budget->approve(); // → approve_am, sync deal_value ke BvSales
        }
        $budget->refresh();

        // ── 10. Quotation signed + MediaPlan Ongoing + Sales Campaign Live ─
        $mediaPlan->update([
            'quotation_signed_path' => 'quotation-signed/ofero-demo-signed.pdf',
            'quotation_signed_at' => now(),
        ]);
        if ($mediaPlan->status !== 'Ongoing') {
            $mediaPlan->update(['status' => 'Ongoing']);
        }
        if ($sales->refresh()->status !== SalesStatus::CAMPAIGN_LIVE) {
            // ensureCampaignOngoingExists → buat campaign + sync KOL/storyline/payment
            $sales->update(['status' => SalesStatus::CAMPAIGN_LIVE]);
        }

        $campaign = $sales->refresh()->campaign;
        if (! $campaign) {
            $this->command->error('Campaign tidak terbentuk — cek prasyarat approve_am. Abort.');
            return;
        }

        // ── 11. Brief konten (storyline) diisi BV + di-approve client ──────
        foreach (self::KOLS as $k) {
            CampaignStoryline::updateOrCreate(
                ['bv_campaign_id' => $campaign->id, 'kol_name' => $k['name'], 'sow' => $k['scope']],
                [
                    'platform' => $this->platform($k['channel']),
                    'content_angle' => "Unboxing & first impression Ofero ala {$k['name']}",
                    'key_message' => 'Ofero — kualitas terbaik untuk daily lifestyle.',
                    'caption_draft' => "Cobain Ofero dan langsung jatuh cinta! 🔥 #Ofero #OferoXKOL",
                    'posting_deadline' => '2026-06-25',
                    'status' => 'approved',
                    'client_choice' => 'approved',
                    'client_feedback' => 'Storyline oke, lanjut produksi video.',
                ],
            );
        }

        // ── 12. Revisi konten 3x per KOL (video) + storyline approved ──────
        $campaign->revisions()->delete();
        foreach (self::KOLS as $k) {
            $kolRow = $campaign->kols()->where('creator_name', $k['name'])->first();
            $kolId = $kolRow?->id;

            $campaign->revisions()->create([
                'bv_campaign_kol_id' => $kolId, 'kol_name' => $k['name'],
                'stage' => 'storyline', 'round' => 1, 'status' => 'approved',
                'asset_text' => 'Storyline disetujui — lanjut ke produksi video.',
            ]);

            $videoRevisions = [
                [1, 'revision', 'Logo Ofero kurang terlihat di 3 detik pertama.', false],
                [2, 'revision', 'Tambahkan CTA "Beli sekarang" di akhir video.', false],
                [3, 'approved', 'Mantap, approved! Siap posting.', true],
            ];
            foreach ($videoRevisions as [$round, $status, $feedback, $final]) {
                $campaign->revisions()->create([
                    'bv_campaign_kol_id' => $kolId, 'kol_name' => $k['name'],
                    'stage' => 'video', 'round' => $round, 'status' => $status,
                    'asset_link' => "https://drive.google.com/ofero-{$k['username']}-v{$round}",
                    'client_feedback' => $feedback, 'is_final' => $final,
                ]);
            }
        }

        // ── 13. Approve brief → KOL masuk KOL Performa + isi metrik posting ─
        foreach (self::KOLS as $k) {
            $kolRow = $campaign->kols()->where('creator_name', $k['name'])->first();
            if (! $kolRow) {
                continue;
            }
            $p = $k['perf'];
            $er = $p['views'] > 0 ? round((($p['likes'] + $p['comments']) / $p['views']) * 100, 4) : 0;

            $kolRow->update([
                'username' => $k['username'],
                'kol_profile_url' => "https://www.{$this->host($k['channel'])}/{$k['username']}",
                'tier' => strtolower($k['tier']),
                'followers_count' => $k['followers'],
                'brief_status' => 'approved', // → muncul di tab KOL Performa
                'status' => 'posted',
                'post_url' => "https://www.{$this->host($k['channel'])}/{$k['username']}/p/ofero-demo",
                'posted_at' => '2026-06-26 10:00:00',
                'views' => $p['views'], 'likes' => $p['likes'], 'comments' => $p['comments'],
                'shares' => $p['shares'], 'saves' => $p['saves'],
                'reach' => (int) round($p['views'] * 0.85),
                'impressions' => $p['views'],
                'engagement_rate' => $er,
            ]);

            // ── 14. Pembayaran KOL (OFERO): tandai paid bila ditentukan ────
            if ($k['paid']) {
                $campaign->payments()->where('kol_name', $k['name'])
                    ->update(['payment_status' => 'paid', 'invoice_date_received' => '2026-06-27']);
            }
        }

        $this->summary($sales, $mediaPlan, $budget, $campaign);
    }

    private function host(string $channel): string
    {
        return $channel === 'Tiktok' ? 'tiktok.com' : 'instagram.com';
    }

    private function platform(string $channel): string
    {
        return $channel === 'Tiktok' ? 'tiktok' : 'instagram';
    }

    private function summary($sales, $mediaPlan, $budget, $campaign): void
    {
        $this->command->info('');
        $this->command->info('=== OFERO FULL FLOW READY ===');
        $this->command->info("BvSales        : #{$sales->id} [{$sales->fresh()->status->value}]");
        $this->command->info("MediaPlan      : #{$mediaPlan->id} [{$mediaPlan->fresh()->status}]");
        $this->command->info("InternalBudget : #{$budget->id} [{$budget->status}] · Quotation #" . ($budget->quotation?->quotation_number ?? '-'));
        $this->command->info("Campaign       : #{$campaign->id} [{$campaign->status}] · {$campaign->kols()->count()} KOL");
        $this->command->info("Storyline      : {$campaign->storylines()->count()} · Revisi: {$campaign->revisions()->count()} · Bayar: {$campaign->payments()->count()}");
        $this->command->info("KOL Performa   : {$campaign->kols()->where('brief_status', 'approved')->count()} KOL posted dengan metrik");
        $this->command->info('Cek: Campaign Ongoing Internal → tab KOL Performance.');
    }
}
