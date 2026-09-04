<?php

use App\Models\{InternalBudget, MasterPph, MediaPlan, MediaPlanCalcSetting};
use PhpOffice\PhpSpreadsheet\IOFactory;

/**
 * Uji kesetaraan langsung terhadap file acuan yang ada di repo — bukan angka
 * yang disalin ke dalam test. Kalau rumus di aplikasi bergeser, atau master
 * data-nya diubah jauh dari sheet, test ini yang jatuh duluan.
 *
 * Kolom sheet: W subtotal, Z MU PPh (cost), AA MU**, AC Rounded, AD Margin %.
 */
const SHEET_BKS = 'docs/berkas-referensi/[INT] Bir Kawan Senja - KOL List (1).xlsx';

/** @return list<array{sheet:string,row:int,qty:int,rate:float,W:float,Z:float,AA:float,AC:float,AD:float}> */
function barisSheetBks(): array
{
    $reader = IOFactory::createReader('Xlsx');
    $reader->setReadDataOnly(true);
    $wb = $reader->load(base_path(SHEET_BKS));

    // Sel berformula menyimpan hasilnya di getOldCalculatedValue() — nilai yang
    // benar-benar dilihat orang saat membuka file. getValue() cuma teks rumusnya,
    // dan menghitung ulang IFERROR/ROUNDUP di PHP justru menguji PhpSpreadsheet,
    // bukan aplikasi kita.
    $angka = function ($cell) {
        $v = $cell->getValue();
        $v = is_string($v) && str_starts_with($v, '=') ? $cell->getOldCalculatedValue() : $v;

        return is_numeric($v) ? (float) $v : null;
    };
    $baris = [];

    foreach (['Nano', 'Micro', 'Macro', 'Homeless Media'] as $nama) {
        $ws = $wb->getSheetByName($nama);

        for ($r = 4; $r <= $ws->getHighestDataRow(); $r++) {
            $sel = [];
            foreach (['qty' => 'T', 'rate' => 'V', 'W' => 'W', 'Z' => 'Z', 'AA' => 'AA', 'AC' => 'AC', 'AD' => 'AD'] as $key => $kolom) {
                $sel[$key] = $angka($ws->getCell($kolom.$r));
            }

            if (in_array(null, $sel, true) || $sel['W'] <= 0) {
                continue;
            }

            $baris[] = $sel + ['sheet' => $nama, 'row' => $r];
        }
    }

    return $baris;
}

it('setiap baris berisi di sheet Bir Kawan Senja direproduksi persis', function () {
    (new Database\Seeders\MasterPphSeeder)->run();
    (new Database\Seeders\MasterMarginSeeder)->run();
    (new Database\Seeders\MediaPlanCalcSettingSeeder)->run();
    MasterPph::forgetCachedDefault();
    MediaPlanCalcSetting::forgetCached();

    $baris = barisSheetBks();
    expect($baris)->toHaveCount(197);

    $plan = MediaPlan::create(['brand' => 'Bir Kawan Senja', 'campaign_name' => 'BKS', 'quotation_number' => 'Q-1']);
    $budget = InternalBudget::create(['media_plan_id' => $plan->id]);
    $pphId = MasterPph::defaultId();

    $meleset = [];

    foreach ($baris as $i => $b) {
        $item = $budget->items()->create([
            'scope_item' => 'SOW',
            'qty' => (int) $b['qty'],
            'rate_base' => $b['rate'],
            'master_pph_id' => $pphId,
            'sort_order' => $i,
        ])->refresh();

        $bandingkan = [
            'W subtotal' => [(float) $item->subtotal, $b['W'], 0.01],
            'Z cost' => [(float) $item->mu_pph, $b['Z'], 1.0],
            'AA MU**' => [(float) $item->mu_target, $b['AA'], 1.0],
            'AC rounded' => [(float) $item->rounded, $b['AC'], 0.01],
            'AD margin%' => [(float) $item->actual_margin_percent, $b['AD'] * 100, 0.01],
        ];

        foreach ($bandingkan as $kolom => [$app, $sheet, $toleransi]) {
            if (abs($app - $sheet) > $toleransi) {
                $meleset[] = "{$b['sheet']}!{$b['row']} {$kolom}: app={$app} sheet={$sheet}";
            }
        }
    }

    expect($meleset)->toBe([]);
});
