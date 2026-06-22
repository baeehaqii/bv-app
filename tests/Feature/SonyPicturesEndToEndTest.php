<?php

use App\Models\BvCampign;
use App\Models\BvSales;
use App\Models\BvSalesList;
use App\Models\DataClient;
use App\Models\FormBrief;
use App\Models\InternalBudget;
use App\Models\MediaPlan;
use App\Models\MediaPlanKol;
use App\Support\MotuScenarioData as Motu;

/**
 * Verifikasi alur sistem END-TO-END memakai data nyata sheet Excel
 * "[EXT] Masters of the Universe - Sony Pictures - KOL List - BV Network".
 *
 * Test ini SENGAJA menggerakkan alur lewat transisi status & boot hooks PRODUKSI
 * (bukan membuat record manual), agar gap/bug rantai antar-modul ketahuan sebelum
 * masuk server produksi.
 *
 * Rantai: Sales Tracker → Form Brief → Media Plan Internal → Media Plan External
 *         → Campaign Ongoing (KOL + Storyline) → Tracker (revisi/posting).
 */

/** Bangun master data minimal (client Sony Pictures + BD Gerry). */
function motuMasters(): array
{
    $gerry = BvSalesList::create(['nama_sales' => Motu::BD_NAME]);
    $client = DataClient::create(['nama_brand' => Motu::CLIENT_BRAND, 'type' => 'direct']);

    return [$gerry, $client];
}

/** Jalankan SELURUH rantai produksi sampai campaign ongoing aktif. Mengembalikan semua entitas. */
function runMotuFlow(): array
{
    [$gerry, $client] = motuMasters();

    // ── Stage 1: Sales Tracker — deal masuk, BD Gerry, PIC Baehaqi ──
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

    // Pindah ke Briefing → auto-buat FormBrief + MediaPlan Internal
    $sales->update(['status' => 'briefing']);

    // ── Stage 2: Form Brief terisi (client submit) → Sales maju ke Proposal Building ──
    $sales->refresh();
    $brief = $sales->formBrief;
    $brief->update([
        'brand' => Motu::CLIENT_BRAND,
        'client_status' => 'Direct',
        'pic' => Motu::PIC_INTERNAL,
        'campaign_name' => Motu::CAMPAIGN_NAME,
        'timeline' => Motu::TIMELINE,
        'campaign_objective' => 'Awareness film Masters of the Universe (rebrand Sony Pictures) via KOL TikTok review & visit.',
        'criteria_of_kol' => 'Movie reviewer / komedi / cosplayer, TikTok, audiens Indonesia.',
        'sow' => '1x TikTok Video + Visit',
        'status' => 'submitted',
        'submitted_at' => now(),
    ]);
    // Brief sudah terisi → trigger sync status sales
    $sales->update(['brief_submit_date' => '2026-05-20']);

    // ── Stage 3: Media Plan Internal — select KOL dari sheet MacroMicro/Approval ──
    $mediaPlan = $sales->mediaPlan;

    $row = 1;
    // Longlist (tidak dipilih)
    foreach (Motu::longlistOnly() as $k) {
        MediaPlanKol::create([
            'media_plan_id' => $mediaPlan->id,
            'row_number' => $row++,
            'name' => $k['name'],
            'channel' => $k['channel'],
            'followers' => $k['followers'],
            'tier' => $k['tier'],
            'er_percent' => $k['er'],
            'rate' => $k['rate'],
            'status' => $k['status'],
            'is_selected' => false,
            'scope_items' => [],
        ]);
    }
    // Shortlist (di-approve client → selected, scope TT Video)
    foreach (Motu::shortlist() as $k) {
        MediaPlanKol::create([
            'media_plan_id' => $mediaPlan->id,
            'row_number' => $row++,
            'name' => $k['name'],
            'channel' => $k['channel'],
            'followers' => $k['followers'],
            'tier' => $k['tier'],
            'er_percent' => $k['er'],
            'rate' => $k['rate'],
            'status' => 'Locked',
            'is_selected' => true,
            'scope_items' => ['TT Video'],
            'links' => [$k['link']],
        ]);
    }

    // Kirim ke client → auto-generate InternalBudget + items (Media Plan External)
    $mediaPlan->update(['status' => 'To Client']);

    // ── Stage 4: Media Plan External — approve tiap item lalu approve budget (AM) ──
    $budget = $mediaPlan->internalBudget->refresh();
    foreach ($budget->items as $item) {
        $item->approve();
    }
    $budget->refresh()->approve(); // status → approve_am

    // Quotation bertanda tangan + Media Plan Internal → Ongoing
    $mediaPlan->update([
        'quotation_signed_path' => 'quotations/motu-signed.pdf',
        'quotation_signed_at' => now(),
    ]);
    $mediaPlan->update(['status' => 'Ongoing']);

    // ── Stage 5: Sales → Campaign Live → auto-buat Campaign Ongoing + sync KOL + seed storyline ──
    $sales->update(['status' => 'campaign_live']);

    return [
        'sales' => $sales->refresh(),
        'brief' => $brief->refresh(),
        'mediaPlan' => $mediaPlan->refresh(),
        'budget' => $budget->refresh(),
        'campaign' => $sales->campaign?->refresh(),
        'client' => $client,
    ];
}

it('Stage 1-2: Briefing membuat Form Brief + Media Plan, lalu brief terisi memajukan Sales ke Proposal Building', function () {
    [$gerry] = motuMasters();

    $sales = BvSales::create([
        'bv_sales_list_id' => $gerry->id,
        'event_name' => Motu::CAMPAIGN_NAME,
        'company_name' => Motu::CLIENT_BRAND,
        'pic_media_plan' => Motu::PIC_INTERNAL,
        'start_date' => '2026-06-01',
        'end_date' => '2026-06-30',
        'status' => 'not_started',
    ]);

    $sales->update(['status' => 'briefing']);
    $sales->refresh();

    expect($sales->formBrief)->not->toBeNull();
    expect($sales->mediaPlan)->not->toBeNull();
    expect($sales->mediaPlan->quotation_number)->toStartWith('BV-');
    expect($sales->mediaPlan->pic_sales_bd_id)->toBe($gerry->id);

    // Brief terisi → sales maju otomatis
    $sales->formBrief->update(['status' => 'submitted', 'submitted_at' => now()]);
    $sales->update(['brief_submit_date' => '2026-05-20']);

    expect($sales->refresh()->status->value)->toBe('proposal_building');
});

it('Stage 3-4: To Client membuat InternalBudget; Sub Total Cost = 15.100.000 (jumlah rate 7 KOL approved)', function () {
    $r = runMotuFlow();

    $budget = $r['budget'];
    expect($budget)->not->toBeNull();
    // 7 KOL selected × 1 scope (TT Video) = 7 item budget
    expect($budget->items()->count())->toBe(7);
    // total_rate (cost-side) harus persis Sub Total Cost di sheet Approval
    expect((float) $budget->total_rate)->toEqual((float) Motu::SUBTOTAL_COST);
    expect($budget->status)->toBe('approve_am');
});

it('Stage 5: Campaign Ongoing terbentuk dengan 7 KOL TikTok + 7 draft Storyline ter-seed dari approved budget', function () {
    $r = runMotuFlow();

    $campaign = $r['campaign'];
    expect($campaign)->not->toBeNull();
    expect($campaign->campaign_type)->toBe(BvCampign::TYPE_INTERNAL);
    expect($campaign->status)->toBeIn(['ongoing', 'upcoming', 'completed']);

    // KOL ter-sync dari approved budget items (7 KOL, semua tiktok/video)
    expect($campaign->kols()->count())->toBe(7);
    expect($campaign->kols()->where('platform', 'tiktok')->count())->toBe(7);

    // Storyline draft ter-seed untuk tiap KOL/SOW
    expect($campaign->storylines()->count())->toBe(7);
    expect($campaign->storylines()->where('status', 'draft')->count())->toBe(7);

    // media_platforms terisi
    expect($campaign->media_platforms)->toContain('tiktok');
});

it('Tracker: revisi bertingkat, status canceled, event_attendance & posting tersimpan sesuai sheet', function () {
    $r = runMotuFlow();
    $campaign = $r['campaign'];

    // Terapkan layer Tracker (eksekusi) di atas KOL/storyline yang sudah ter-seed.
    foreach (Motu::tracker() as $t) {
        $kol = $campaign->kols()->where('creator_name', $t['name'])->first();

        // kadin5s tidak ter-approve di budget → tidak ada baris KOL otomatis; buat sebagai canceled.
        if (! $kol) {
            $kol = $campaign->kols()->create([
                'creator_name' => $t['name'],
                'platform' => 'tiktok',
                'content_type' => 'video',
                'status' => 'pending',
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
    }

    // adifi_ : video revisi ronde 1 menyimpan feedback client persis dari sheet
    $adifi = $campaign->kols()->where('creator_name', 'adifi_')->first();
    expect($adifi->event_attendance)->toBeTrue();
    expect($adifi->revisions()->where('stage', 'video')->where('round', 1)->value('client_feedback'))
        ->toBe('masters of the universe ya, bukan master');
    expect($adifi->revisions()->where('is_final', true)->where('stage', 'video')->exists())->toBeTrue();

    // Felix : storyline 2 ronde + video 2 ronde (revisi bertingkat melebihi 2 ronde lama)
    $felix = $campaign->kols()->where('creator_name', 'Felix Sudjiman')->first();
    expect($felix->revisions()->where('stage', 'storyline')->count())->toBe(2);
    expect($felix->revisions()->where('stage', 'video')->count())->toBe(2);

    // kadin5s : KOL Cancel
    $kadin = $campaign->kols()->where('creator_name', 'kadin5s')->first();
    expect($kadin->status)->toBe('canceled');

    // KOL Done Posting (adifi_, Felix, winnerizky, lindafebrianaaa, ombwokreviewer) = 5
    $posted = $campaign->kols()->where('status', 'posted')->get();
    expect($posted)->toHaveCount(5);
    $posted->each(fn ($k) => expect($k->post_url)->not->toBeNull());
});

it('Regression: BvSales start_date/end_date ter-cast Carbon (Media Plan edit tak crash di ->format())', function () {
    $r = runMotuFlow();
    $sales = $r['sales'];

    // Persis ekspektasi MediaPlanForm::afterStateHydrated (sebelumnya string → format() error)
    expect($sales->start_date)->toBeInstanceOf(\Illuminate\Support\Carbon::class);
    expect($sales->end_date)->toBeInstanceOf(\Illuminate\Support\Carbon::class);
    expect($sales->start_date->format('Y-m-d'))->toBe('2026-06-01');
    expect($sales->end_date->format('Y-m-d'))->toBe('2026-06-30');
});

it('Konsistensi: deal_value campaign = total_rounded budget (harga client setelah markup)', function () {
    $r = runMotuFlow();

    expect((float) $r['campaign']->deal_value)->toBe((float) $r['budget']->total_rounded);
    expect((float) $r['campaign']->deal_value)->toBeGreaterThan((float) $r['budget']->total_rate);
});
