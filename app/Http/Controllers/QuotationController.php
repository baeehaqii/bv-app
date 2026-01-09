<?php

namespace App\Http\Controllers;

use App\Models\MediaPlan;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Storage;

class QuotationController extends Controller
{
    /**
     * Get logo as base64 string for PDF
     */
    private function getLogoBase64(): ?string
    {
        $logoPath = public_path('images/logo-bv.png');
        if (file_exists($logoPath)) {
            $logoContent = file_get_contents($logoPath);
            $logoBase64 = 'data:image/png;base64,' . base64_encode($logoContent);
            return $logoBase64;
        }
        return null;
    }

    /**
     * Generate quotation PDF for a media plan
     */
    public function generatePdf(MediaPlan $mediaPlan)
    {
        // Get selected KOLs only
        $selectedKols = $mediaPlan->selectedKols()->get();

        // Calculate totals
        $subTotal = $selectedKols->sum('rate');
        $ppnPercent = 11; // Default PPN 11%
        $ppnAmount = $subTotal * ($ppnPercent / 100);
        $grandTotal = $subTotal + $ppnAmount;

        // Prepare data for the view
        $data = [
            'mediaPlan' => $mediaPlan,
            'selectedKols' => $selectedKols,
            'subTotal' => $subTotal,
            'ppnPercent' => $ppnPercent,
            'ppnAmount' => $ppnAmount,
            'grandTotal' => $grandTotal,
            'quotationDate' => Carbon::now()->format('d M Y'),
            'preparedBy' => auth()->user()->name ?? 'Beyond Viral Team',
            'logoBase64' => $this->getLogoBase64(),
        ];

        // Generate PDF
        $pdf = Pdf::loadView('pdf.quotation', $data);
        $pdf->setPaper('a4', 'landscape');

        // Generate filename
        $filename = 'Quotation_' . str_replace(' ', '_', $mediaPlan->campaign_name ?? 'Campaign') . '_' . Carbon::now()->format('Ymd') . '.pdf';

        return $pdf->download($filename);
    }

    /**
     * Preview quotation in browser
     */
    public function preview(MediaPlan $mediaPlan)
    {
        // Get selected KOLs only
        $selectedKols = $mediaPlan->selectedKols()->get();

        // Calculate totals
        $subTotal = $selectedKols->sum('rate');
        $ppnPercent = 11; // Default PPN 11%
        $ppnAmount = $subTotal * ($ppnPercent / 100);
        $grandTotal = $subTotal + $ppnAmount;

        // Prepare data for the view
        $data = [
            'mediaPlan' => $mediaPlan,
            'selectedKols' => $selectedKols,
            'subTotal' => $subTotal,
            'ppnPercent' => $ppnPercent,
            'ppnAmount' => $ppnAmount,
            'grandTotal' => $grandTotal,
            'quotationDate' => Carbon::now()->format('d M Y'),
            'preparedBy' => auth()->user()->name ?? 'Beyond Viral Team',
            'logoBase64' => $this->getLogoBase64(),
        ];

        // Generate PDF and stream to browser
        $pdf = Pdf::loadView('pdf.quotation', $data);
        $pdf->setPaper('a4', 'landscape');

        return $pdf->stream('quotation_preview.pdf');
    }

    /**
     * View quotation as HTML (for testing/debugging)
     */
    public function html(MediaPlan $mediaPlan)
    {
        // Get selected KOLs only
        $selectedKols = $mediaPlan->selectedKols()->get();

        // Calculate totals
        $subTotal = $selectedKols->sum('rate');
        $ppnPercent = 11;
        $ppnAmount = $subTotal * ($ppnPercent / 100);
        $grandTotal = $subTotal + $ppnAmount;

        return view('pdf.quotation', [
            'mediaPlan' => $mediaPlan,
            'selectedKols' => $selectedKols,
            'subTotal' => $subTotal,
            'ppnPercent' => $ppnPercent,
            'ppnAmount' => $ppnAmount,
            'grandTotal' => $grandTotal,
            'quotationDate' => Carbon::now()->format('d M Y'),
            'preparedBy' => auth()->user()->name ?? 'Beyond Viral Team',
            'logoBase64' => $this->getLogoBase64(),
        ]);
    }
}

