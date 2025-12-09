<?php

namespace App\Http\Controllers;

use App\Models\MediaPlan;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;

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

        // Load relationships
        $mediaPlan->load([
            'kols' => function ($query) {
                $query->where('is_selected', true)->orderBy('row_number');
            },
            'internalBudget.items'
        ]);

        // Calculate total budget from internal budget
        $totalBudget = $internalBudget->total_rounded ?? 0;

        // Generate PDF
        $pdf = Pdf::loadView('pdf.media-plan', [
            'mediaPlan' => $mediaPlan,
            'totalBudget' => $totalBudget,
        ]);

        // Set paper to landscape A4
        $pdf->setPaper('a4', 'landscape');

        // Set options for better rendering
        $pdf->setOptions([
            'isHtml5ParserEnabled' => true,
            'isRemoteEnabled' => true,
            'defaultFont' => 'Arial',
        ]);

        // Generate filename - replace invalid characters
        $safeQuotationNumber = str_replace(['/', '\\', ' '], '_', $mediaPlan->quotation_number);
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

        // Load relationships
        $mediaPlan->load([
            'kols' => function ($query) {
                $query->where('is_selected', true)->orderBy('row_number');
            },
            'internalBudget.items'
        ]);

        // Calculate total budget
        $totalBudget = $internalBudget->total_rounded ?? 0;

        // Generate PDF
        $pdf = Pdf::loadView('pdf.media-plan', [
            'mediaPlan' => $mediaPlan,
            'totalBudget' => $totalBudget,
        ]);

        $pdf->setPaper('a4', 'landscape');

        // Generate safe filename
        $safeQuotationNumber = str_replace(['/', '\\', ' '], '_', $mediaPlan->quotation_number);

        // Return PDF for inline viewing
        return $pdf->stream('MediaPlan_' . $safeQuotationNumber . '.pdf');
    }
}
