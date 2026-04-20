<?php

namespace App\Http\Controllers;

use App\Exports\MediaPlanExport;
use App\Models\MediaPlan;
use App\Service\GoogleSheetsService;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Facades\Excel;
use Exception;

class MediaPlanExcelController extends Controller
{
    /**
     * Export Media Plan to Excel (XLSX)
     */
    public function export(MediaPlan $mediaPlan)
    {
        // Check if internal budget is approved
        $internalBudget = $mediaPlan->internalBudget;

        if (!$internalBudget || $internalBudget->status !== 'approved') {
            abort(403, 'Internal Budget harus di-approve terlebih dahulu sebelum export Excel.');
        }

        // Generate safe filename
        $safeQuotationNumber = Str::slug($mediaPlan->quotation_number ?? 'media-plan');
        $filename = 'MediaPlan_' . $safeQuotationNumber . '.xlsx';

        return Excel::download(new MediaPlanExport($mediaPlan), $filename);
    }

    /**
     * Export Media Plan to CSV
     */
    public function exportCsv(MediaPlan $mediaPlan)
    {
        // Check if internal budget is approved
        $internalBudget = $mediaPlan->internalBudget;

        if (!$internalBudget || $internalBudget->status !== 'approved') {
            abort(403, 'Internal Budget harus di-approve terlebih dahulu sebelum export CSV.');
        }

        // Generate safe filename
        $safeQuotationNumber = Str::slug($mediaPlan->quotation_number ?? 'media-plan');
        $filename = 'MediaPlan_' . $safeQuotationNumber . '.csv';

        return Excel::download(new MediaPlanExport($mediaPlan), $filename, \Maatwebsite\Excel\Excel::CSV);
    }

    /**
     * Export Media Plan to Google Sheets (Step 1: Redirect to Google Auth)
     */
    public function exportToGoogleSheets(MediaPlan $mediaPlan)
    {
        // Check if internal budget is approved
        $internalBudget = $mediaPlan->internalBudget;

        if (!$internalBudget || $internalBudget->status !== 'approved') {
            abort(403, 'Internal Budget harus di-approve terlebih dahulu.');
        }

        try {
            $sheetsService = new GoogleSheetsService();
            $client = $sheetsService->getClient();

            // Store target Media Plan ID in session
            session(['google_export_media_plan_id' => $mediaPlan->id]);

            // Redirect to Google Consent Screen
            return redirect()->away($client->createAuthUrl());

        } catch (Exception $e) {
            abort(500, 'Gagal inisialisasi Google Auth: ' . $e->getMessage());
        }
    }

    /**
     * Handle Google OAuth Callback (Step 2: Create Spreadsheet)
     */
    public function handleGoogleCallback(\Illuminate\Http\Request $request)
    {
        $code = $request->get('code');
        $mediaPlanId = session('google_export_media_plan_id');

        if (!$code || !$mediaPlanId) {
            return redirect()->route('home')->with('error', 'Google Auth failed or invalid session.');
        }

        try {
            $mediaPlan = MediaPlan::findOrFail($mediaPlanId);

            $sheetsService = new GoogleSheetsService();
            $client = $sheetsService->getClient();

            // Exchange code for access token
            $accessToken = $client->fetchAccessTokenWithAuthCode($code);

            // Create Spreadsheet
            $result = $sheetsService->createMediaPlanSpreadsheet($mediaPlan, $accessToken);

            // Clear session matches
            session()->forget('google_export_media_plan_id');

            // Redirect to the new spreadsheet
            return redirect()->away($result['url']);

        } catch (Exception $e) {
            return redirect()->route('filament.office.resources.media-plan-internal.edit', ['record' => $mediaPlanId])
                ->with('error', 'Gagal membuat spreadsheet: ' . $e->getMessage());
        }
    }
}

