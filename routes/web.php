<?php

use App\Http\Controllers\CampaignContentReviewController;
use App\Http\Controllers\CampaignPublicController;
use App\Http\Controllers\FormBriefPublicController;
use App\Http\Controllers\QuotationPublicController;
use App\Http\Controllers\KolContractController;
use App\Http\Controllers\SpkPublicController;
use App\Http\Controllers\KolImportTemplateController;
use App\Http\Controllers\MediaPlanPdfController;
use App\Http\Controllers\MediaPlanExcelController;
use App\Http\Controllers\CampaignSummaryPdfController;
use App\Http\Controllers\KolCardPdfController;
use App\Http\Controllers\InternalBudgetPdfController;
use App\Http\Controllers\InternalBudgetReviewController;
use App\Http\Controllers\QuotationController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
})->name('home');

Route::get('/v2', function () {
    return view('welcome-v2');
})->name('home.v2');

// Form Brief Public (Client Portal) — no auth required
Route::get('/brief/{token}', [FormBriefPublicController::class, 'show'])->name('form-brief.public');
Route::post('/brief/{token}', [FormBriefPublicController::class, 'submit'])->name('form-brief.submit');

// Campaign Ongoing External — public tracking page for clients
Route::get('/campaign/{token}', [CampaignPublicController::class, 'show'])->name('campaign.public');

// Quotation Public Review — no auth required
Route::get('/quotation-review/{token}', [QuotationPublicController::class, 'show'])->name('quotation.public');
Route::post('/quotation-review/{token}/sign', [QuotationPublicController::class, 'sign'])->name('quotation.public.sign');

// Media Plan External — Link Review Client (public, no auth)
Route::get('/media-plan-review/{token}', [InternalBudgetReviewController::class, 'show'])->name('media-plan-external.review');
Route::post('/media-plan-review/{token}', [InternalBudgetReviewController::class, 'submit'])->name('media-plan-external.review.submit');

// Campaign On Going Internal — Link Approval Konten (public, no auth)
Route::get('/campaign-content-review/{token}', [CampaignContentReviewController::class, 'show'])->name('campaign-internal.content-review');
Route::post('/campaign-content-review/{token}', [CampaignContentReviewController::class, 'submit'])->name('campaign-internal.content-review.submit');

// SPK / PKS — e-sign KOL (public, no auth). Gerbangnya token + langkah verifikasi;
// verify di-throttle supaya link yang bocor tidak bisa dipakai brute-force data KOL.
Route::get('/spk-sign/{token}', [SpkPublicController::class, 'show'])->name('spk.public');
Route::post('/spk-sign/{token}/verify', [SpkPublicController::class, 'verify'])
    ->middleware('throttle:10,1')
    ->name('spk.public.verify');
Route::post('/spk-sign/{token}/sign', [SpkPublicController::class, 'sign'])->name('spk.public.sign');
Route::get('/spk-sign/{token}/document', [SpkPublicController::class, 'document'])->name('spk.public.document');
Route::get('/spk-sign/{token}/download', [SpkPublicController::class, 'download'])->name('spk.public.download');

// Media Plan PDF Routes (require auth)
Route::middleware(['auth'])->group(function () {
    // KOL Import Template
    Route::get('/kol-import/template', [KolImportTemplateController::class, 'download'])
        ->name('kol-import.template');

    // Brief file viewer (serves files from sales-briefs/ and form-briefs/ on local disk)
    Route::get('/brief-file', function (\Illuminate\Http\Request $request) {
        $path = $request->query('path');
        abort_unless($path, 404);

        $allowedPrefixes = ['sales-briefs/', 'form-briefs/'];
        $isAllowed = collect($allowedPrefixes)->contains(fn($p) => str_starts_with($path, $p));
        abort_unless($isAllowed, 403);
        abort_if(str_contains($path, '..'), 403);

        $disk = \Illuminate\Support\Facades\Storage::disk('local');
        abort_unless($disk->exists($path), 404);

        return response()->file($disk->path($path), [
            'Content-Disposition' => 'inline; filename="' . basename($path) . '"',
        ]);
    })->name('brief-file.view');

    Route::get('/media-plan/{mediaPlan}/pdf', [MediaPlanPdfController::class, 'generate'])
        ->name('media-plan.pdf');
    Route::get('/media-plan/{mediaPlan}/pdf/preview', [MediaPlanPdfController::class, 'preview'])
        ->name('media-plan.pdf.preview');
    Route::get('/media-plan/{mediaPlan}/pdf/html', [MediaPlanPdfController::class, 'previewHtml'])
        ->name('media-plan.pdf.html');

    // Media Plan Excel Export Routes
    Route::get('/media-plan/{mediaPlan}/excel', [MediaPlanExcelController::class, 'export'])
        ->name('media-plan.excel');
    Route::get('/media-plan/{mediaPlan}/csv', [MediaPlanExcelController::class, 'exportCsv'])
        ->name('media-plan.csv');
    Route::get('/media-plan/{mediaPlan}/google-sheets', [MediaPlanExcelController::class, 'exportToGoogleSheets'])
        ->name('media-plan.google-sheets');
    Route::get('/google/callback', [MediaPlanExcelController::class, 'handleGoogleCallback'])
        ->name('google.callback');

    // Campaign Summary PDF
    Route::get('/campaign/{bvCampign}/summary/pdf', [CampaignSummaryPdfController::class, 'generate'])
        ->name('campaign-summary.pdf');

    // Kartu profil KOL (hasil AI)
    Route::get('/kol/{dataKol}/kartu/pdf', [KolCardPdfController::class, 'generate'])
        ->name('kol-card.pdf');

    // Internal Budget PDF Routes
    Route::get('/internal-budget/{internalBudget}/pdf', [InternalBudgetPdfController::class, 'generate'])
        ->name('internal-budget.pdf');
    Route::get('/internal-budget/{internalBudget}/pdf/preview', [InternalBudgetPdfController::class, 'preview'])
        ->name('internal-budget.pdf.preview');

    // Quotation PDF Routes (lama — dari MediaPlan)
    Route::get('/quotation/{mediaPlan}/download', [QuotationController::class, 'generatePdf'])
        ->name('quotation.download');
    Route::get('/quotation/{mediaPlan}/preview', [QuotationController::class, 'preview'])
        ->name('quotation.preview');
    Route::get('/quotation/{mediaPlan}/html', [QuotationController::class, 'html'])
        ->name('quotation.html');

    // BvQuotation PDF Routes (baru — format updated, dari InternalBudgetItem)
    Route::get('/bv-quotation/{bvQuotation}/pdf', [QuotationController::class, 'generateFromBvQuotation'])
        ->name('bv-quotation.pdf');
    Route::get('/bv-quotation/{bvQuotation}/preview', [QuotationController::class, 'previewBvQuotation'])
        ->name('bv-quotation.preview');

    // Template impor massal KOL — dibuat on-demand supaya daftar channel di
    // dropdown-nya selalu ikut KolProfileImporter::SCRAPABLE.
    Route::get('/data-kol/template-import.xlsx', fn() => response()->streamDownload(
        fn() => print(\App\Service\KolProfileImporter::templateXlsx()),
        'template-import-kol.xlsx',
        ['Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'],
    ))->name('data-kol.import-template');

    // Proxy thumbnail KOL — CDN Instagram/TikTok menolak hotlink (403), jadi
    // gambarnya diambil server-side dengan Referer yang benar lalu di-cache.
    Route::get('/kol-image', \App\Http\Controllers\KolImageController::class)
        ->middleware('signed')
        ->name('kol-image');

    // KOL Contract (SPK) Routes
    Route::get('/kol-contract/{spk}/download', [KolContractController::class, 'download'])
        ->name('kol-contract.download');
    Route::get('/kol-contract/{spk}/preview', [KolContractController::class, 'preview'])
        ->name('kol-contract.preview');
    Route::get('/kol-contract/{spk}/html', [KolContractController::class, 'html'])
        ->name('kol-contract.html');
});

