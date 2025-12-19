<?php

namespace App\Http\Controllers;

use App\Models\MediaPlan;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class MediaPlanPdfController extends Controller
{
    /**
     * Generate PDF for Media Plan
     */
    public function generate(MediaPlan $mediaPlan)
    {
        // Check if internal budget is approved
        $internalBudget = $mediaPlan->internalBudget;

        if (!$internalBudget || $internalBudget->status !== 'approved') {
            abort(403, 'Internal Budget harus di-approve terlebih dahulu sebelum generate PDF.');
        }

        // Load relationships - selected KOLs with their DataKol relation and budget items
        $mediaPlan->load([
            'kols' => function ($query) {
                $query->where('is_selected', true)
                    ->orderBy('row_number')
                    ->with(['dataKol', 'internalBudgetItems']); // Load DataKol for category and budget items for scope
            },
            'internalBudget.items'
        ]);

        // Calculate total budget from internal budget
        $totalBudget = $internalBudget->total_rounded ?? 0;

        // Encode logo as base64 for reliable embedding in PDF
        $logoPath = public_path('images/logo_bv.png');
        $logoBase64 = null;
        if (file_exists($logoPath)) {
            $logoData = file_get_contents($logoPath);
            $logoBase64 = 'data:image/png;base64,' . base64_encode($logoData);
        }

        // Generate PDF
        $pdf = Pdf::loadView('pdf.media-plan', [
            'mediaPlan' => $mediaPlan,
            'totalBudget' => $totalBudget,
            'logoBase64' => $logoBase64,
        ]);

        // Set paper to landscape A4
        $pdf->setPaper('a4', 'landscape');

        // Set options for better rendering
        $pdf->setOptions([
            'isHtml5ParserEnabled' => true,
            'isRemoteEnabled' => true,
            'defaultFont' => 'Arial',
            'dpi' => 150,
            'debugCss' => false,
            'debugLayout' => false,
        ]);

        // Generate filename - replace invalid characters
        $safeQuotationNumber = Str::slug($mediaPlan->quotation_number ?? 'media-plan');
        $filename = 'MediaPlan_' . $safeQuotationNumber . '.pdf';

        // Return PDF for download
        return $pdf->download($filename);
    }

    /**
     * Preview PDF in browser
     */
    public function preview(MediaPlan $mediaPlan)
    {
        // Check if internal budget is approved
        $internalBudget = $mediaPlan->internalBudget;

        if (!$internalBudget || $internalBudget->status !== 'approved') {
            abort(403, 'Internal Budget harus di-approve terlebih dahulu sebelum preview PDF.');
        }

        // Load relationships - selected KOLs with their DataKol relation and budget items
        $mediaPlan->load([
            'kols' => function ($query) {
                $query->where('is_selected', true)
                    ->orderBy('row_number')
                    ->with(['dataKol', 'internalBudgetItems']); // Load DataKol for category and budget items for scope
            },
            'internalBudget.items'
        ]);

        // Calculate total budget
        $totalBudget = $internalBudget->total_rounded ?? 0;

        // Encode logo as base64 for reliable embedding in PDF
        $logoPath = public_path('images/logo_bv.png');
        $logoBase64 = null;
        if (file_exists($logoPath)) {
            $logoData = file_get_contents($logoPath);
            $logoBase64 = 'data:image/png;base64,' . base64_encode($logoData);
        }

        // Generate PDF
        $pdf = Pdf::loadView('pdf.media-plan', [
            'mediaPlan' => $mediaPlan,
            'totalBudget' => $totalBudget,
            'logoBase64' => $logoBase64,
        ]);

        // Set paper to landscape A4
        $pdf->setPaper('a4', 'landscape');

        // Set options for better rendering
        $pdf->setOptions([
            'isHtml5ParserEnabled' => true,
            'isRemoteEnabled' => true,
            'defaultFont' => 'Arial',
            'dpi' => 150,
        ]);

        // Generate safe filename
        $safeQuotationNumber = Str::slug($mediaPlan->quotation_number ?? 'media-plan');

        // Return PDF for inline viewing
        return $pdf->stream('MediaPlan_' . $safeQuotationNumber . '.pdf');
    }

    /**
     * Preview as HTML (for debugging/development)
     */
    public function previewHtml(MediaPlan $mediaPlan)
    {
        // Check if internal budget is approved
        $internalBudget = $mediaPlan->internalBudget;

        if (!$internalBudget || $internalBudget->status !== 'approved') {
            abort(403, 'Internal Budget harus di-approve terlebih dahulu sebelum preview.');
        }

        // Load relationships - selected KOLs with their DataKol relation and budget items
        $mediaPlan->load([
            'kols' => function ($query) {
                $query->where('is_selected', true)
                    ->orderBy('row_number')
                    ->with(['dataKol', 'internalBudgetItems']); // Load DataKol for category and budget items for scope
            },
            'internalBudget.items'
        ]);

        // Calculate total budget
        $totalBudget = $internalBudget->total_rounded ?? 0;

        // Encode logo as base64 for reliable embedding in PDF
        $logoPath = public_path('images/logo_bv.png');
        $logoBase64 = null;
        if (file_exists($logoPath)) {
            $logoData = file_get_contents($logoPath);
            $logoBase64 = 'data:image/png;base64,' . base64_encode($logoData);
        }

        // Return HTML view directly (for development/debugging)
        return view('pdf.media-plan', [
            'mediaPlan' => $mediaPlan,
            'totalBudget' => $totalBudget,
            'logoBase64' => $logoBase64,
        ]);
    }
}
