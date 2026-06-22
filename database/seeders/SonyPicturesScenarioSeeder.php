<?php

namespace Database\Seeders;

use App\Models\BvCampign;
use App\Models\BvSales;
use App\Models\BvSalesList;
use App\Models\DataClient;
use App\Models\InternalBudget;
use App\Models\MediaPlan;
use App\Models\MediaPlanKol;
use App\Support\MotuScenarioData as Motu;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Skenario END-TO-END "Masters of the Universe — Sony Pictures" (rebrand, BD Gerry, PIC Baehaqi).
 *
 * Membangun data PERSIS dari file acuan client melalui transisi status & boot hooks
 * PRODUKSI yang sama (bukan insert manual), sehingga UI bisa diklik dari Sales Tracker
 * sampai Campaign Ongoing seperti alur sebenarnya.
 *
 * Idempotent: menghapus skenario lama (jika ada) lalu membangun ulang.
 * Tidak didaftarkan di DatabaseSeeder — jalankan manual:
 *   php artisan db:seed --class=SonyPicturesScenarioSeeder
 */
class SonyPicturesScenarioSeeder extends Seeder
{
    public function run(): void
    {
        DB::transaction(function () {
            $this->cleanup();

            $gerry = BvSalesList::firstOrCreate(['nama_sales' => Motu::BD_NAME]);
            DataClient::firstOrCreate(
                ['nama_brand' => Motu::CLIENT_BRAND],
                ['type' => 'direct'],
            );

            // ── Stage 1: Sales Tracker — deal masuk ──
            $sales = BvSales::create([
                'bv_sales_list_id' => $gerry->id,
                'event_name' => Motu::CAMPAIGN_NAME,
                'company_name' => Motu::CLIENT_BRAND,
                'pic_media_plan' => Motu::PIC_INTERNAL,
                'budget_propose' => 20_000_000,
                'campaign_month' => 6,
                'campaign_date' => '2026-06-01',
                'start_date' => '2026-06-01',
                'end_date' => '2026-06-30',
                'status' => 'not_started',
            ]);
            $sales->update(['status' => 'briefing']); // → FormBrief + MediaPlan

            // ── Stage 2: Form Brief terisi ──
            $sales->refresh();
            $sales->formBrief->update([
                'brand' => Motu::CLIENT_BRAND,
                'client_status' => 'Direct',
                'pic' => Motu::PIC_INTERNAL,
                'campaign_name' => Motu::CAMPAIGN_NAME,
                'timeline' => Motu::TIMELINE,
                'campaign_objective' => 'Awareness film Masters of the Universe (rebrand Sony Pictures) via KOL TikTok review & visit ke The Breeze, BSD City.',
                'criteria_of_kol' => 'Movie reviewer / komedi / cosplayer, TikTok, audiens Indonesia.',
                'sow' => '1x TikTok Video + Visit',
                'status' => 'submitted',
                'submitted_at' => now(),
            ]);
            $sales->update(['brief_submit_date' => '2026-05-20']); // → Proposal Building

            // ── Stage 3: Media Plan Internal — select KOL dari sheet ──
            $mediaPlan = $sales->mediaPlan;
            $row = 1;
            foreach (Motu::longlistOnly() as $k) {
                MediaPlanKol::create([
                    'media_plan_id' => $mediaPlan->id, 'row_number' => $row++,
                    'name' => $k['name'], 'channel' => $k['channel'], 'followers' => $k['followers'],
                    'tier' => $k['tier'], 'er_percent' => $k['er'], 'rate' => $k['rate'],
                    'status' => $k['status'], 'is_selected' => false, 'scope_items' => [],
                ]);
            }
            foreach (Motu::shortlist() as $k) {
                MediaPlanKol::create([
                    'media_plan_id' => $mediaPlan->id, 'row_number' => $row++,
                    'name' => $k['name'], 'channel' => $k['channel'], 'followers' => $k['followers'],
                    'tier' => $k['tier'], 'er_percent' => $k['er'], 'rate' => $k['rate'],
                    'status' => 'Locked', 'is_selected' => true, 'scope_items' => ['TT Video'],
                    'links' => [$k['link']],
                ]);
            }
            $mediaPlan->update(['status' => 'To Client']); // → InternalBudget + items

            // ── Stage 4: Media Plan External — approve item + AM ──
            $budget = $mediaPlan->internalBudget->refresh();
            foreach ($budget->items as $item) {
                $item->approve();
            }
            $budget->refresh()->approve(); // → approve_am
            $mediaPlan->update([
                'quotation_signed_path' => 'quotations/motu-sony-signed.pdf',
                'quotation_signed_at' => now(),
            ]);
            $mediaPlan->update(['status' => 'Ongoing']);

            // ── Stage 5: Campaign Live → Campaign Ongoing + KOL + Storyline ──
            $sales->update(['status' => 'campaign_live']);
            $campaign = $sales->refresh()->campaign;

            // ── Tracker: revisi bertingkat, caption, posting, event, cancel ──
            $this->applyTracker($campaign);

            $this->command?->info(sprintf(
                'Sony Pictures scenario: campaign #%d, %d KOL, %d storyline, %d revisi, budget cost Rp %s.',
                $campaign->id,
                $campaign->kols()->count(),
                $campaign->storylines()->count(),
                $campaign->revisions()->count(),
                number_format((float) $budget->fresh()->total_rate, 0, ',', '.'),
            ));
        });
    }

    private function applyTracker(BvCampign $campaign): void
    {
        foreach (Motu::tracker() as $t) {
            $kol = $campaign->kols()->where('creator_name', $t['name'])->first();

            // KOL yang tidak lolos approval budget (mis. kadin5s) tetap muncul di Tracker
            // sebagai baris dengan status canceled.
            if (! $kol) {
                $kol = $campaign->kols()->create([
                    'creator_name' => $t['name'], 'platform' => 'tiktok',
                    'content_type' => 'video', 'status' => 'pending',
                ]);
            }

            $kol->update([
                'status' => $t['kol_status'],
                'event_attendance' => $t['event'],
                'post_url' => $t['posting_link'],
                'posted_at' => $t['posting_date'],
            ]);

            foreach ($t['revisions'] as $rev) {
                $campaign->revisions()->create([
                    'bv_campaign_kol_id' => $kol->id,
                    'kol_name' => $t['name'],
                    'stage' => $rev['stage'],
                    'round' => $rev['round'],
                    'asset_link' => $rev['asset_link'],
                    'client_feedback' => $rev['feedback'],
                    'status' => $rev['status'],
                    'is_final' => $rev['final'],
                ]);
            }

            // Sinkronkan caption & status ke draft storyline (TT Video) yang sudah ter-seed.
            if ($t['caption']) {
                $campaign->storylines()
                    ->where('kol_name', $t['name'])
                    ->update([
                        'caption_draft' => $t['caption'],
                        'status' => $t['kol_status'] === 'posted' ? 'posted' : 'draft',
                    ]);
            }
        }
    }

    /** Hapus skenario lama agar seeder bisa dijalankan ulang dengan bersih. */
    private function cleanup(): void
    {
        $salesRows = BvSales::where('event_name', Motu::CAMPAIGN_NAME)
            ->where('company_name', Motu::CLIENT_BRAND)
            ->get();

        foreach ($salesRows as $sales) {
            // Campaign (cascade: kols, storylines, revisions via FK)
            BvCampign::where('bv_sales_id', $sales->id)->each(fn ($c) => $c->delete());

            // Media plan + internal budget (cascade: items, media_plan_kols)
            MediaPlan::where('bv_sales_id', $sales->id)->each(function (MediaPlan $mp) {
                InternalBudget::where('media_plan_id', $mp->id)->each(fn ($b) => $b->delete());
                $mp->delete();
            });

            $sales->formBrief()->delete();
            $sales->delete();
        }
    }
}
