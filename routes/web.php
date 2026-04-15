<?php

use App\Http\Controllers\FormBriefPublicController;
use App\Http\Controllers\KolContractController;
use App\Http\Controllers\KolImportTemplateController;
use App\Http\Controllers\MediaPlanPdfController;
use App\Http\Controllers\MediaPlanExcelController;
use App\Http\Controllers\InternalBudgetPdfController;
use App\Http\Controllers\QuotationController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
})->name('home');

// Form Brief Public (Client Portal) — no auth required
Route::get('/brief/{token}', [FormBriefPublicController::class, 'show'])->name('form-brief.public');
Route::post('/brief/{token}', [FormBriefPublicController::class, 'submit'])->name('form-brief.submit');

// Media Plan PDF Routes (require auth)
Route::middleware(['auth'])->group(function () {
    // KOL Import Template
    Route::get('/kol-import/template', [KolImportTemplateController::class, 'download'])
        ->name('kol-import.template');

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

    // Internal Budget PDF Routes
    Route::get('/internal-budget/{internalBudget}/pdf', [InternalBudgetPdfController::class, 'generate'])
        ->name('internal-budget.pdf');
    Route::get('/internal-budget/{internalBudget}/pdf/preview', [InternalBudgetPdfController::class, 'preview'])
        ->name('internal-budget.pdf.preview');

    // Quotation PDF Routes
    Route::get('/quotation/{mediaPlan}/download', [QuotationController::class, 'generatePdf'])
        ->name('quotation.download');
    Route::get('/quotation/{mediaPlan}/preview', [QuotationController::class, 'preview'])
        ->name('quotation.preview');
    Route::get('/quotation/{mediaPlan}/html', [QuotationController::class, 'html'])
        ->name('quotation.html');

    // KOL Contract (SPK) Routes
    Route::get('/kol-contract/{spk}/download', [KolContractController::class, 'download'])
        ->name('kol-contract.download');
    Route::get('/kol-contract/{spk}/preview', [KolContractController::class, 'preview'])
        ->name('kol-contract.preview');
    Route::get('/kol-contract/{spk}/html', [KolContractController::class, 'html'])
        ->name('kol-contract.html');
});

