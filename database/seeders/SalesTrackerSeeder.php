<?php

namespace Database\Seeders;

use App\Models\BvSales;
use App\Models\BvSalesList;
use Illuminate\Database\Seeder;

class SalesTrackerSeeder extends Seeder
{
    /**
     * Data Sales Tracker dari "Sales Pipeline & Dashboard - Beyond Viral.xlsx" (sheet Pipeline).
     * 46 baris deal historis → BvSales.
     *
     * Mapping Stage/Status → SalesStatus enum:
     *   Finish / Paid    → paid
     *   Lost             → close_lose
     *   Invoicing        → invoicing
     *   Won / On Going   → campaign_live
     *   Awaiting Feedback→ negotiation
     *   Revision         → proposal_building
     *   Media Plan       → proposal_building
     *
     * Catatan: BvSales::create() TIDAK memicu hook updated() (briefing/campaign_live),
     * jadi seeding ini murni data tracker — tidak auto-spawn MediaPlan/Campaign.
     * updateOrCreate by (company_name, event_name) → aman dijalankan ulang.
     */
    public function run(): void
    {
        // Resolusi PIC sales → bv_sales_list_id
        $salesIds = [];
        foreach (['Gerry', 'Wina'] as $name) {
            $salesIds[$name] = BvSalesList::firstOrCreate(['nama_sales' => $name])->id;
        }

        $rows = [
            ['pic' => 'Gerry', 'company_name' => 'Ofero', 'related_client_name' => 'Direct', 'event_name' => 'Ofero x PRJ 2025 & Ride & Fest', 'status' => 'paid', 'budget_propose' => 500000000.0, 'deal_value' => 508000000.0, 'start_date' => '2025-07-01', 'end_date' => '2025-07-01', 'campaign_periode' => 'July 2025', 'campaign_year' => 2025, 'comments' => null],
            ['pic' => 'Gerry', 'company_name' => 'Ofero', 'related_client_name' => 'Direct', 'event_name' => 'Ofere Design Stareer lit3', 'status' => 'paid', 'budget_propose' => 250000000.0, 'deal_value' => 101540816.0, 'start_date' => '2025-08-01', 'end_date' => '2025-08-05', 'campaign_periode' => 'July 2025', 'campaign_year' => 2025, 'comments' => null],
            ['pic' => 'Gerry', 'company_name' => 'Injourney', 'related_client_name' => 'ITDC - Injourney', 'event_name' => 'MotoGP Mandalika 2025', 'status' => 'paid', 'budget_propose' => 800000000.0, 'deal_value' => 355810000.0, 'start_date' => '2025-08-01', 'end_date' => '2025-08-05', 'campaign_periode' => 'July 2025', 'campaign_year' => 2025, 'comments' => null],
            ['pic' => 'Gerry', 'company_name' => 'XM', 'related_client_name' => 'Agam', 'event_name' => 'XM Trading', 'status' => 'paid', 'budget_propose' => 50000000.0, 'deal_value' => 23750000.0, 'start_date' => '2025-07-01', 'end_date' => '2025-07-01', 'campaign_periode' => 'July 2025', 'campaign_year' => 2025, 'comments' => null],
            ['pic' => 'Gerry', 'company_name' => 'Michelin', 'related_client_name' => 'Direct', 'event_name' => 'BA for michelin 3 Months', 'status' => 'close_lose', 'budget_propose' => 250000000.0, 'deal_value' => null, 'start_date' => '2025-08-01', 'end_date' => '2025-08-11', 'campaign_periode' => 'Aug 2025', 'campaign_year' => 2025, 'comments' => null],
            ['pic' => 'Gerry', 'company_name' => 'Mobil1', 'related_client_name' => 'TBWA', 'event_name' => 'Mobil1 Visit Care Car', 'status' => 'paid', 'budget_propose' => 6000000.0, 'deal_value' => 6000000.0, 'start_date' => '2025-09-01', 'end_date' => '2025-09-05', 'campaign_periode' => 'Sept 2025', 'campaign_year' => 2025, 'comments' => null],
            ['pic' => 'Gerry', 'company_name' => 'Mattel', 'related_client_name' => 'MBCS', 'event_name' => 'Mattel Content Production', 'status' => 'close_lose', 'budget_propose' => 15000000.0, 'deal_value' => null, 'start_date' => '2025-10-14', 'end_date' => '2025-10-27', 'campaign_periode' => 'Oct 2025', 'campaign_year' => 2025, 'comments' => null],
            ['pic' => 'Gerry', 'company_name' => 'Fisher Price - Mattel', 'related_client_name' => 'MBCS', 'event_name' => 'Reco campaign 2026 & Visit Playlab US', 'status' => 'close_lose', 'budget_propose' => 4500000000.0, 'deal_value' => null, 'start_date' => '2025-10-14', 'end_date' => '2025-10-22', 'campaign_periode' => 'Oct 2025', 'campaign_year' => 2025, 'comments' => null],
            ['pic' => 'Gerry', 'company_name' => 'Fisher Price - Mattel', 'related_client_name' => 'MBCS', 'event_name' => 'Affiliate Plan for 2026', 'status' => 'close_lose', 'budget_propose' => 150000000.0, 'deal_value' => null, 'start_date' => '2025-10-14', 'end_date' => '2025-10-27', 'campaign_periode' => 'Oct 2025', 'campaign_year' => 2025, 'comments' => null],
            ['pic' => 'Gerry', 'company_name' => 'Ofero', 'related_client_name' => 'Direct', 'event_name' => 'Ofero Stareer 3 Lit All Black', 'status' => 'paid', 'budget_propose' => 20000000.0, 'deal_value' => null, 'start_date' => '2025-10-28', 'end_date' => '2025-10-31', 'campaign_periode' => 'Oct 2025', 'campaign_year' => 2025, 'comments' => null],
            ['pic' => 'Gerry', 'company_name' => 'Michelin, BFGoodrich, Corsa', 'related_client_name' => 'Direct', 'event_name' => 'Social Media Retainer', 'status' => 'close_lose', 'budget_propose' => 1000000000.0, 'deal_value' => null, 'start_date' => '2025-10-30', 'end_date' => '2025-11-13', 'campaign_periode' => null, 'campaign_year' => 2025, 'comments' => null],
            ['pic' => 'Gerry', 'company_name' => 'Blue Band', 'related_client_name' => 'UM IPG', 'event_name' => 'Campaign Q1 - Q3 2026', 'status' => 'close_lose', 'budget_propose' => 500000000.0, 'deal_value' => null, 'start_date' => '2025-11-19', 'end_date' => '2025-11-21', 'campaign_periode' => null, 'campaign_year' => 2025, 'comments' => null],
            ['pic' => 'Gerry', 'company_name' => 'Ofero', 'related_client_name' => 'Direct', 'event_name' => 'Ofero Stareer 5 lit Launching', 'status' => 'invoicing', 'budget_propose' => 159000000.0, 'deal_value' => 178800000.0, 'start_date' => null, 'end_date' => null, 'campaign_periode' => null, 'campaign_year' => 2025, 'comments' => null],
            ['pic' => 'Gerry', 'company_name' => 'Blue Band', 'related_client_name' => 'UM IPG', 'event_name' => 'Blueband Dracin', 'status' => 'invoicing', 'budget_propose' => 42200000.0, 'deal_value' => 42200000.0, 'start_date' => null, 'end_date' => null, 'campaign_periode' => null, 'campaign_year' => 2026, 'comments' => null],
            ['pic' => 'Gerry', 'company_name' => 'Nyam Nyam', 'related_client_name' => 'Direct', 'event_name' => 'Nyam Nyam Ramadhan', 'status' => 'paid', 'budget_propose' => 100000000.0, 'deal_value' => 14000000.0, 'start_date' => null, 'end_date' => null, 'campaign_periode' => null, 'campaign_year' => 2026, 'comments' => null],
            ['pic' => 'Gerry', 'company_name' => 'Golden Rama Tours', 'related_client_name' => 'UM IPG', 'event_name' => 'Surabaya Campaign', 'status' => 'close_lose', 'budget_propose' => 50000000.0, 'deal_value' => null, 'start_date' => null, 'end_date' => null, 'campaign_periode' => null, 'campaign_year' => 2026, 'comments' => null],
            ['pic' => 'Gerry', 'company_name' => 'Le Minerale', 'related_client_name' => 'Direct', 'event_name' => 'KOC Le Minerale - LMGG Umroh', 'status' => 'campaign_live', 'budget_propose' => 100000000.0, 'deal_value' => null, 'start_date' => null, 'end_date' => null, 'campaign_periode' => null, 'campaign_year' => 2026, 'comments' => null],
            ['pic' => 'Gerry', 'company_name' => 'Ofero', 'related_client_name' => 'Direct', 'event_name' => 'Ofero Leasing', 'status' => 'invoicing', 'budget_propose' => 15000000.0, 'deal_value' => null, 'start_date' => null, 'end_date' => null, 'campaign_periode' => null, 'campaign_year' => 2026, 'comments' => null],
            ['pic' => 'Gerry', 'company_name' => 'Arummi', 'related_client_name' => 'Direct', 'event_name' => 'Social Media Content UGC', 'status' => 'campaign_live', 'budget_propose' => 15000000.0, 'deal_value' => 15000000.0, 'start_date' => null, 'end_date' => null, 'campaign_periode' => 'May 2026', 'campaign_year' => 2026, 'comments' => null],
            ['pic' => 'Gerry', 'company_name' => 'Gimbory', 'related_client_name' => null, 'event_name' => 'Gimbory (TBD)', 'status' => 'negotiation', 'budget_propose' => null, 'deal_value' => null, 'start_date' => null, 'end_date' => null, 'campaign_periode' => 'May 2026', 'campaign_year' => 2026, 'comments' => null],
            ['pic' => 'Gerry', 'company_name' => 'Shopee', 'related_client_name' => 'Direct', 'event_name' => 'PR Teams - Clippers', 'status' => 'negotiation', 'budget_propose' => 25000000.0, 'deal_value' => null, 'start_date' => null, 'end_date' => null, 'campaign_periode' => 'May 2026', 'campaign_year' => 2026, 'comments' => null],
            ['pic' => 'Gerry', 'company_name' => 'Golden Rama Tours', 'related_client_name' => 'UM', 'event_name' => 'SQ Travel Fair', 'status' => 'proposal_building', 'budget_propose' => 35000000.0, 'deal_value' => null, 'start_date' => '2026-05-19', 'end_date' => '2026-05-22', 'campaign_periode' => 'May 2026', 'campaign_year' => 2026, 'comments' => null],
            ['pic' => 'Gerry', 'company_name' => 'Cimory', 'related_client_name' => 'Growmint', 'event_name' => 'Cimory Yoghurt', 'status' => 'negotiation', 'budget_propose' => 500000000.0, 'deal_value' => null, 'start_date' => '2026-05-19', 'end_date' => null, 'campaign_periode' => 'May 2026', 'campaign_year' => 2026, 'comments' => null],
            ['pic' => 'Wina', 'company_name' => 'GoFood', 'related_client_name' => 'Curve', 'event_name' => 'Threads Campaign', 'status' => 'invoicing', 'budget_propose' => 35000000.0, 'deal_value' => 1000000.0, 'start_date' => '2026-05-21', 'end_date' => null, 'campaign_periode' => 'May 2026', 'campaign_year' => 2026, 'comments' => null],
            ['pic' => 'Wina', 'company_name' => 'GoPay', 'related_client_name' => 'Curve', 'event_name' => 'Gamers', 'status' => 'negotiation', 'budget_propose' => 50000000.0, 'deal_value' => null, 'start_date' => '2026-05-22', 'end_date' => null, 'campaign_periode' => 'May 2026', 'campaign_year' => 2026, 'comments' => null],
            ['pic' => 'Wina', 'company_name' => 'GoPay', 'related_client_name' => 'Curve', 'event_name' => 'FIFA Worldcup’26', 'status' => 'negotiation', 'budget_propose' => 100000000.0, 'deal_value' => null, 'start_date' => '2026-05-22', 'end_date' => null, 'campaign_periode' => 'May 2026', 'campaign_year' => 2026, 'comments' => null],
            ['pic' => 'Wina', 'company_name' => 'Sony Pictures', 'related_client_name' => 'Direct', 'event_name' => "Masters of the Universe Int'l Influencer", 'status' => 'invoicing', 'budget_propose' => 15000000.0, 'deal_value' => 15100000.0, 'start_date' => '2026-05-22', 'end_date' => null, 'campaign_periode' => 'May 2026', 'campaign_year' => 2026, 'comments' => null],
            ['pic' => 'Wina', 'company_name' => 'Manfa Indonesia', 'related_client_name' => 'Direct', 'event_name' => 'Manfa', 'status' => 'negotiation', 'budget_propose' => 50000000.0, 'deal_value' => null, 'start_date' => '2026-05-22', 'end_date' => null, 'campaign_periode' => 'May 2026', 'campaign_year' => 2026, 'comments' => null],
            ['pic' => 'Gerry', 'company_name' => 'Cedea', 'related_client_name' => 'WebTVAsia', 'event_name' => 'Cedea Seafood', 'status' => 'negotiation', 'budget_propose' => 50000000.0, 'deal_value' => null, 'start_date' => '2026-05-26', 'end_date' => null, 'campaign_periode' => 'May 2026', 'campaign_year' => 2026, 'comments' => null],
            ['pic' => 'Wina', 'company_name' => 'Page By Page', 'related_client_name' => 'We Thrive', 'event_name' => 'Page By Page Launching', 'status' => 'proposal_building', 'budget_propose' => 30000000.0, 'deal_value' => null, 'start_date' => '2026-05-29', 'end_date' => null, 'campaign_periode' => 'May 2026', 'campaign_year' => 2026, 'comments' => null],
            ['pic' => 'Gerry', 'company_name' => 'Vitalis', 'related_client_name' => 'WebTV Asia', 'event_name' => 'Vitalis ERP 100ml', 'status' => 'proposal_building', 'budget_propose' => 42500000.0, 'deal_value' => null, 'start_date' => '2026-05-29', 'end_date' => null, 'campaign_periode' => 'May 2026', 'campaign_year' => 2026, 'comments' => null],
            ['pic' => 'Wina', 'company_name' => 'Toys Kingdom', 'related_client_name' => 'Direct', 'event_name' => 'Toys Kingdom Visit Store', 'status' => 'negotiation', 'budget_propose' => null, 'deal_value' => null, 'start_date' => '2026-06-02', 'end_date' => null, 'campaign_periode' => null, 'campaign_year' => 2026, 'comments' => null],
            ['pic' => 'Wina', 'company_name' => 'Planet Ban', 'related_client_name' => 'Direct', 'event_name' => 'IG Collab', 'status' => 'campaign_live', 'budget_propose' => 35000000.0, 'deal_value' => null, 'start_date' => '2026-06-02', 'end_date' => null, 'campaign_periode' => null, 'campaign_year' => 2026, 'comments' => null],
            ['pic' => 'Wina', 'company_name' => 'Planet Ban', 'related_client_name' => 'Direct', 'event_name' => 'Grand Opening Store @Pekanbaru', 'status' => 'campaign_live', 'budget_propose' => 100000000.0, 'deal_value' => null, 'start_date' => '2026-06-02', 'end_date' => null, 'campaign_periode' => null, 'campaign_year' => 2026, 'comments' => null],
            ['pic' => 'Wina', 'company_name' => 'Planet Ban', 'related_client_name' => 'Direct', 'event_name' => 'Grand Opening Store @Banjarmasin', 'status' => 'campaign_live', 'budget_propose' => 50000000.0, 'deal_value' => null, 'start_date' => '2026-06-02', 'end_date' => null, 'campaign_periode' => null, 'campaign_year' => 2026, 'comments' => null],
            ['pic' => 'Wina', 'company_name' => 'Planet Ban', 'related_client_name' => 'Direct', 'event_name' => 'Youtube Podcast X ABG Siniar', 'status' => 'proposal_building', 'budget_propose' => null, 'deal_value' => null, 'start_date' => null, 'end_date' => null, 'campaign_periode' => null, 'campaign_year' => 2026, 'comments' => null],
            ['pic' => 'Wina', 'company_name' => 'Planet Ban', 'related_client_name' => 'Direct', 'event_name' => 'Talent Shoot', 'status' => 'campaign_live', 'budget_propose' => 4000000.0, 'deal_value' => null, 'start_date' => '2026-06-02', 'end_date' => null, 'campaign_periode' => null, 'campaign_year' => 2026, 'comments' => null],
            ['pic' => 'Wina', 'company_name' => 'UIP Studio', 'related_client_name' => 'Direct', 'event_name' => 'Minions Premiere', 'status' => 'proposal_building', 'budget_propose' => null, 'deal_value' => null, 'start_date' => '2026-06-03', 'end_date' => null, 'campaign_periode' => null, 'campaign_year' => 2026, 'comments' => null],
            ['pic' => 'Wina', 'company_name' => 'Heineken', 'related_client_name' => 'Direct', 'event_name' => "Fifa World Cup'26", 'status' => 'proposal_building', 'budget_propose' => null, 'deal_value' => null, 'start_date' => null, 'end_date' => null, 'campaign_periode' => null, 'campaign_year' => 2026, 'comments' => null],
            ['pic' => 'Wina', 'company_name' => 'Bintang', 'related_client_name' => 'Direct', 'event_name' => 'Bintang Legendary', 'status' => 'proposal_building', 'budget_propose' => null, 'deal_value' => null, 'start_date' => null, 'end_date' => null, 'campaign_periode' => null, 'campaign_year' => 2026, 'comments' => null],
            ['pic' => 'Wina', 'company_name' => 'GoCar', 'related_client_name' => 'Curve', 'event_name' => 'GoCar Hemat', 'status' => 'negotiation', 'budget_propose' => null, 'deal_value' => null, 'start_date' => null, 'end_date' => null, 'campaign_periode' => null, 'campaign_year' => 2026, 'comments' => null],
            ['pic' => 'Wina', 'company_name' => 'GoCar', 'related_client_name' => 'Curve', 'event_name' => 'GOWEEKEND', 'status' => 'negotiation', 'budget_propose' => null, 'deal_value' => null, 'start_date' => null, 'end_date' => null, 'campaign_periode' => null, 'campaign_year' => 2026, 'comments' => null],
            ['pic' => 'Wina', 'company_name' => 'GoPay', 'related_client_name' => 'Curve', 'event_name' => 'GoPay Merch', 'status' => 'negotiation', 'budget_propose' => null, 'deal_value' => null, 'start_date' => null, 'end_date' => null, 'campaign_periode' => null, 'campaign_year' => 2026, 'comments' => null],
            ['pic' => 'Wina', 'company_name' => 'Bintang Anggur Merah', 'related_client_name' => 'Direct', 'event_name' => 'BAM 12 Kota', 'status' => 'proposal_building', 'budget_propose' => null, 'deal_value' => null, 'start_date' => null, 'end_date' => null, 'campaign_periode' => null, 'campaign_year' => 2026, 'comments' => null],
            ['pic' => 'Wina', 'company_name' => 'GoPay', 'related_client_name' => 'Curve', 'event_name' => 'GoPay Cinema Mini App', 'status' => 'invoicing', 'budget_propose' => null, 'deal_value' => null, 'start_date' => '2026-06-19', 'end_date' => null, 'campaign_periode' => null, 'campaign_year' => 2026, 'comments' => null],
            ['pic' => 'Wina', 'company_name' => 'Pizza Hut Indonesia', 'related_client_name' => 'Direct', 'event_name' => 'New promo dan Review Menu', 'status' => 'proposal_building', 'budget_propose' => null, 'deal_value' => null, 'start_date' => '2026-06-22', 'end_date' => null, 'campaign_periode' => null, 'campaign_year' => 2026, 'comments' => null],
        ];

        foreach ($rows as $row) {
            $pic = $row['pic'];
            unset($row['pic']);
            $row['bv_sales_list_id'] = $salesIds[$pic] ?? null;
            // Kolom uang NOT NULL → default 0 bila kosong di sheet
            $row['budget_propose'] = $row['budget_propose'] ?? 0;
            $row['deal_value'] = $row['deal_value'] ?? 0;

            BvSales::updateOrCreate(
                ['company_name' => $row['company_name'], 'event_name' => $row['event_name']],
                $row
            );
        }
    }
}
