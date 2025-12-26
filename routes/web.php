<?php

use App\Http\Controllers\MediaPlanPdfController;
use App\Http\Controllers\InternalBudgetPdfController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
})->name('home');

// Media Plan PDF Routes (require auth)
Route::middleware(['auth'])->group(function () {
    Route::get('/media-plan/{mediaPlan}/pdf', [MediaPlanPdfController::class, 'generate'])
        ->name('media-plan.pdf');
    Route::get('/media-plan/{mediaPlan}/pdf/preview', [MediaPlanPdfController::class, 'preview'])
        ->name('media-plan.pdf.preview');
    Route::get('/media-plan/{mediaPlan}/pdf/html', [MediaPlanPdfController::class, 'previewHtml'])
        ->name('media-plan.pdf.html');

    // Internal Budget PDF Routes
    Route::get('/internal-budget/{internalBudget}/pdf', [InternalBudgetPdfController::class, 'generate'])
        ->name('internal-budget.pdf');
    Route::get('/internal-budget/{internalBudget}/pdf/preview', [InternalBudgetPdfController::class, 'preview'])
        ->name('internal-budget.pdf.preview');
});
