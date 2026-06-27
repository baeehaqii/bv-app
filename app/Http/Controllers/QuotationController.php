<?php

namespace App\Http\Controllers;

use App\Models\BvQuotation;
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
     * Generate PDF from BvQuotation (format baru — data dari InternalBudgetItem)
     */
    public function generateFromBvQuotation(BvQuotation $bvQuotation)
    {
        $data = $this->buildBvQuotationData($bvQuotation);
        $pdf  = Pdf::loadView('pdf.quotation-new', $data)->setPaper('a4', 'landscape');

        $slug     = str_replace([' ', '/'], '_', $bvQuotation->quotation_number ?? 'QUO');
        $filename = "Quotation_{$slug}.pdf";

        return $pdf->download($filename);
    }

    /**
     * Stream preview dari BvQuotation
     */
    public function previewBvQuotation(BvQuotation $bvQuotation)
    {
        $data = $this->buildBvQuotationData($bvQuotation);
        $pdf  = Pdf::loadView('pdf.quotation-new', $data)->setPaper('a4', 'landscape');

        return $pdf->stream('quotation_preview.pdf');
    }

    private function buildBvQuotationData(BvQuotation $bvQuotation): array
    {
        $budget    = $bvQuotation->internalBudget;
        $mediaPlan = $budget?->mediaPlan;

        $items = $budget
            ? $budget->items()
                ->where('status', 'approved')
                ->with('mediaPlanKol')
                ->orderBy('sort_order')
                ->get()
            : collect();

        // Group by KOL; fallback key = scope_item jika KOL null
        $kolGroups = $items->groupBy(fn($i) => $i->mediaPlanKol?->id ?? ('_' . $i->scope_item));

        $subTotal  = $items->sum('rounded');
        $pphFinal  = $items->sum('mu_pph');
        $grandTotal = $subTotal + $pphFinal;

        // Konversi signature file → base64 agar bisa ditampilkan di DomPDF
        $signatories = collect($bvQuotation->signatories ?? [])->map(function ($sig) {
            $sig['signature_base64'] = null;
            if (!empty($sig['signature'])) {
                $path = Storage::disk('public')->path($sig['signature']);
                if (file_exists($path)) {
                    $ext  = strtolower(pathinfo($path, PATHINFO_EXTENSION));
                    $mime = in_array($ext, ['jpg', 'jpeg']) ? 'image/jpeg' : 'image/png';
                    $sig['signature_base64'] = "data:{$mime};base64," . base64_encode(file_get_contents($path));
                }
            }
            return $sig;
        })->values()->all();

        return [
            'quotation'   => $bvQuotation,
            'mediaPlan'   => $mediaPlan,
            'kolGroups'   => $kolGroups,
            'subTotal'    => $subTotal,
            'pphFinal'    => $pphFinal,
            'grandTotal'  => $grandTotal,
            'signatories' => $signatories,
            'logoBase64'  => $this->getLogoBase64(),
        ];
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

