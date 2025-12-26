<?php

namespace App\Http\Controllers;

use App\Models\InternalBudget;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class InternalBudgetPdfController extends Controller
{
    /**
     * Generate PDF for Internal Budget
     */
    public function generate(InternalBudget $internalBudget)
    {
        // Load relationships
        $internalBudget->load([
            'mediaPlan',
            'items' => function ($query) {
                $query->orderBy('sort_order')
                    ->with(['mediaPlanKol', 'masterPph']);
            }
        ]);

        // Encode logo as base64 for reliable embedding in PDF
        $logoPath = public_path('images/logo_bv.png');
        $logoBase64 = null;
        if (file_exists($logoPath)) {
            $logoData = file_get_contents($logoPath);
            $logoBase64 = 'data:image/png;base64,' . base64_encode($logoData);
        }

        // Generate PDF
        $pdf = Pdf::loadView('pdf.internal-budget', [
            'internalBudget' => $internalBudget,
            'logoBase64' => $logoBase64,
        ]);

        // Set paper to landscape A4
        $pdf->setPaper('a4', 'landscape');

        // Set options for better rendering
        $pdf->setOptions([
            'isHtml5ParserEnabled' => true,
            'isRemoteEnabled' => true,
            'defaultFont' => 'DejaVu Sans',
            'dpi' => 150,
            'debugCss' => false,
            'debugLayout' => false,
        ]);

        // Generate filename - replace invalid characters
        $campaignName = $internalBudget->mediaPlan->campaign_name ?? 'internal-budget';
        $safeCampaignName = Str::slug($campaignName);
        $filename = 'InternalBudget_' . $safeCampaignName . '_' . now()->format('Ymd') . '.pdf';

        // Return PDF for download
        return $pdf->download($filename);
    }

    /**
     * Preview PDF in browser
     */
    public function preview(InternalBudget $internalBudget)
    {
        // Load relationships
        $internalBudget->load([
            'mediaPlan',
            'items' => function ($query) {
                $query->orderBy('sort_order')
                    ->with(['mediaPlanKol', 'masterPph']);
            }
        ]);

        // Encode logo as base64 for reliable embedding in PDF
        $logoPath = public_path('images/logo_bv.png');
        $logoBase64 = null;
        if (file_exists($logoPath)) {
            $logoData = file_get_contents($logoPath);
            $logoBase64 = 'data:image/png;base64,' . base64_encode($logoData);
        }

        // Generate PDF
        $pdf = Pdf::loadView('pdf.internal-budget', [
            'internalBudget' => $internalBudget,
            'logoBase64' => $logoBase64,
        ]);

        // Set paper to landscape A4
        $pdf->setPaper('a4', 'landscape');

        // Set options for better rendering
        $pdf->setOptions([
            'isHtml5ParserEnabled' => true,
            'isRemoteEnabled' => true,
            'defaultFont' => 'DejaVu Sans',
            'dpi' => 150,
            'debugCss' => false,
            'debugLayout' => false,
        ]);

        // Return PDF for inline display
        return $pdf->stream();
    }
}
