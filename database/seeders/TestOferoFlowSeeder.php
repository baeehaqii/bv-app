<?php

namespace Database\Seeders;

use App\Models\BvSales;
use App\Models\DataClient;
use App\Models\DataKol;
use App\Models\FormBrief;
use App\Models\InternalBudget;
use App\Models\InternalBudgetItem;
use App\Models\MediaPlan;
use App\Models\MediaPlanKol;
use App\Models\BvSalesList;
use App\Models\User;
use App\Enums\SalesStatus;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Test flow: DataClient → BvSales → FormBrief → MediaPlan → MediaPlanKol → InternalBudget → Items
 * Campaign  : Ofero x PRJ 2025 & Ride & Fest
 * KOL       : @radenrauf (Instagram, Macro)
 *
 * Jalankan: php artisan db:seed --class=TestOferoFlowSeeder
 */
class TestOferoFlowSeeder extends Seeder
{
    public function run(): void
    {
        // ── 1. Pastikan DataClient Ofero ada ──────────────────────────────
        $client = DataClient::updateOrCreate(
            ['nama_brand' => 'Ofero'],
            [
                'type'           => 'direct',
                'has_agency'     => false,
                'agency_name'    => [],
                'account_owner'  => 'Gerry',
                'status'         => 'not_started',
                'status_client'  => 'aktif',
            ]
        );
        $this->command->info("DataClient: #{$client->id} {$client->nama_brand}");

        // ── 2. DataKol @radenrauf ─────────────────────────────────────────
        $kol = DataKol::updateOrCreate(
            ['username' => 'radenrauf'],
            [
                'full_name'         => 'Raden Rauf',
                'channel'           => 'Instagram',
                'link_userprofile'  => 'https://www.instagram.com/radenrauf/',
                'followers'         => 3_500_000,
                'tier'              => 'Macro',
                'engagement_rate'   => 3.2,
                'engagements'       => 112_000,
                'status'            => 'active',
                'tipe_pajak_kol'    => 1, // Pribadi
            ]
        );
        $this->command->info("DataKol: #{$kol->id} @{$kol->username}");

        // ── 3. BvSales — reset ke PITCHING dulu, lalu naik ke BRIEFING ───
        $gerry = BvSalesList::firstOrCreate(['nama_sales' => 'Gerry']);
        $admin = User::first();

        // Pakai updateOrCreate — hindari duplikat saat re-run
        $sales = BvSales::updateOrCreate(
            ['company_name' => 'Ofero', 'event_name' => 'Ofero x PRJ 2025 & Ride & Fest'],
            [
                'bv_sales_list_id' => $gerry->id,
                'status'           => SalesStatus::PITCHING,
                'budget_propose'   => 500_000_000,
                'deal_value'       => 508_000_000,
                'start_date'       => '2025-07-01',
                'end_date'         => '2025-07-31',
            ]
        );
        $this->command->info("BvSales: #{$sales->id} status={$sales->status->value}");

        // ── 4. Naik ke BRIEFING → boot hook buat FormBrief + MediaPlan ───
        // Gunakan update() langsung agar tidak dobel trigger jika sudah ada
        if (! $sales->mediaPlan) {
            $sales->status = SalesStatus::BRIEFING;
            $sales->save(); // trigger booted() → ensureFormBriefExists + ensureMediaPlanExists
            $sales->refresh();
            $this->command->info("Status naik ke BRIEFING — FormBrief & MediaPlan dibuat");
        } else {
            $this->command->info("MediaPlan sudah ada, skip trigger BRIEFING");
        }

        // ── 5. Isi SOW di FormBrief ───────────────────────────────────────
        $brief = $sales->formBrief;
        if ($brief) {
            $brief->update([
                'sow'               => "- IG Reels (1x)\n- IG Story (3x)",
                'campaign_name'     => 'Ofero x PRJ 2025 & Ride & Fest',
                'brand'             => 'Ofero',
                'background'        => 'Peluncuran produk Ofero dalam rangkaian event PRJ 2025 dan Ride & Fest.',
                'key_messages'      => 'Ofero hadir untuk para rider dengan kualitas terbaik.',
                'target_audience'   => 'Pria 18-35 tahun, hobi otomotif & lifestyle',
                'kpi'               => 'Views >= 100K, ER >= 3%',
                'budget'            => 500_000_000,
                'deadline'          => '2025-07-31',
            ]);
            $this->command->info("FormBrief: #{$brief->id} SOW diisi");
        }

        // ── 6. Update MediaPlan detail ────────────────────────────────────
        $mediaPlan = $sales->mediaPlan;
        $mediaPlan->update([
            'campaign_name'          => 'Ofero x PRJ 2025 & Ride & Fest',
            'brand'                  => 'Ofero',
            'campaign_type'          => 'regular',
            'campaign_period_start'  => '2025-07-01',
            'campaign_period_end'    => '2025-07-31',
            'platform'               => 'Instagram',
            'pic_sales_bd_id'        => $gerry->id,
            'margin_type'            => 'percent',
            'margin_percent'         => 30,
            'use_global_margin'      => true,
        ]);
        $this->command->info("MediaPlan: #{$mediaPlan->id} detail updated");

        // ── 7. MediaPlanKol → @radenrauf ─────────────────────────────────
        $mpKol = MediaPlanKol::updateOrCreate(
            ['media_plan_id' => $mediaPlan->id, 'data_kol_id' => $kol->id],
            [
                'row_number'     => 1,
                'is_selected'    => true,
                'name'           => 'Raden Rauf',
                'channel'        => 'Instagram',
                'links'          => ['https://www.instagram.com/radenrauf/'],
                'followers'      => 3_500_000,
                'tier'           => 'Macro',
                'er_percent'     => 3.2,
                'impression'     => 112_000,
                'scope_items'    => ['IG Reels', 'IG Story'],
                'tipe_pajak_kol' => 1,
                'status'         => 'New List',
                'rate'           => 0, // akan diisi setelah InternalBudgetItem dibuat
            ]
        );
        $this->command->info("MediaPlanKol: #{$mpKol->id} @radenrauf added (is_selected=true)");

        // ── 8. Set MediaPlan → To Client → trigger autoGenerateInternalBudget ──
        if (! $mediaPlan->internalBudget) {
            $mediaPlan->status = 'To Client';
            $mediaPlan->save(); // trigger: autoGenerateInternalBudget()
            $mediaPlan->refresh();
            $this->command->info("MediaPlan → 'To Client' — InternalBudget dibuat");
        } else {
            $this->command->info("InternalBudget sudah ada, skip trigger");
        }

        // ── 9. Isi / update InternalBudgetItems ──────────────────────────
        $budget = $mediaPlan->internalBudget ?? $mediaPlan->fresh()->internalBudget;
        if (! $budget) {
            $budget = InternalBudget::create([
                'media_plan_id' => $mediaPlan->id,
                'status'        => 'draft',
            ]);
            $this->command->info("InternalBudget dibuat manual: #{$budget->id}");
        }

        // Hapus item lama (jika ada dari auto-generate) dan buat ulang yang clean
        $budget->items()->delete();

        $items = [
            [
                'scope_item'  => 'IG Reels',
                'qty'         => 1,
                'rate_base'   => 20_000_000,
                'sort_order'  => 1,
                'status'      => 'approved',
            ],
            [
                'scope_item'  => 'IG Story',
                'qty'         => 3,
                'rate_base'   => 5_000_000,
                'sort_order'  => 2,
                'status'      => 'approved',
            ],
        ];

        foreach ($items as $item) {
            $budgetItem = InternalBudgetItem::create(array_merge($item, [
                'internal_budget_id' => $budget->id,
                'media_plan_kol_id'  => $mpKol->id,
                'master_pph_id'      => 1, // Pribadi, coeff=0.975
                'qty'                => $item['qty'],
            ]));
            $budgetItem->recalculate();
            $budgetItem->save();
        }

        $budget->recalculateTotals();
        $budget->refresh();

        $this->command->info(sprintf(
            "InternalBudget: #%d | total_rounded=Rp %s | total_mu_pph=Rp %s",
            $budget->id,
            number_format($budget->total_rounded, 0, ',', '.'),
            number_format($budget->total_mu_pph, 0, ',', '.'),
        ));

        // ── 10. Summary ──────────────────────────────────────────────────
        $this->command->info('');
        $this->command->info('=== TEST FLOW READY ===');
        $this->command->info("DataClient  : #{$client->id} {$client->nama_brand}");
        $this->command->info("BvSales     : #{$sales->id} [{$sales->fresh()->status->value}]");
        $this->command->info("FormBrief   : #{$brief?->id}");
        $this->command->info("MediaPlan   : #{$mediaPlan->id} [{$mediaPlan->fresh()->status}]");
        $this->command->info("KOL         : #{$mpKol->id} @radenrauf (Macro, 3.5M)");
        $this->command->info("InternalBudget: #{$budget->id} [{$budget->status}]");
        $this->command->info('');
        $this->command->info('Next steps manual di UI:');
        $this->command->info('  1. Buka Media Plan Internal → Edit → tab Brief → lihat SOW');
        $this->command->info('  2. Tab Select KOL → @radenrauf sudah tercentang');
        $this->command->info('  3. Buka Media Plan External → review item → approve item');
        $this->command->info('  4. Generate Quotation (saat status approve_client)');
        $this->command->info('  5. Approve AM → Campaign Ongoing terbuat otomatis');
    }
}
