<?php

use App\Filament\Resources\MediaPlans\Schemas\MediaPlanForm;

/**
 * Margin aktual yang ditampilkan di kolom "Margin %" KOL List mengikuti rumus
 * kolom Margin % pada sheet KOL List client: (Client Price - Cost) / Client Price.
 * Beda dari margin target yang diinput karena Client Price dibulatkan ke atas per 100rb.
 */
it('menghitung margin aktual dari client price yang sudah dibulatkan', function () {
    $figs = MediaPlanForm::computeBudgetFigures(1_000_000, 0.975, 30);

    expect($figs['mu_pph'])->toBe(1_025_641.0)
        ->and($figs['rounded'])->toBe(1_500_000.0)
        ->and($figs['actual_margin'])->toBe(31.62)
        ->and($figs['actual_margin'])
        ->toBe(round((($figs['rounded'] - 1_000_000 / 0.975) / $figs['rounded']) * 100, 2));
});

it('tidak menghasilkan margin saat rate belum diisi', function () {
    expect(MediaPlanForm::computeBudgetFigures(0, 0.975, 30)['actual_margin'])->toBe(0);
});
