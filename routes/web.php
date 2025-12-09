<?php

use App\Http\Controllers\MediaPlanPdfController;
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
});
