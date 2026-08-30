<?php

namespace App\Http\Controllers;

use App\Models\BvCampign;
use App\Service\CampaignSummary;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Str;

class CampaignSummaryPdfController extends Controller
{
    /** Cetak halaman Campaign Summary jadi PDF landscape. */
    public function generate(BvCampign $bvCampign)
    {
        $logoPath = public_path('images/logo_bv.png');
        $logoBase64 = file_exists($logoPath)
            ? 'data:image/png;base64,' . base64_encode(file_get_contents($logoPath))
            : null;

        $pdf = Pdf::loadView('pdf.campaign-summary', [
            'campaign' => $bvCampign->load('client'),
            'summary' => new CampaignSummary($bvCampign),
            'logoBase64' => $logoBase64,
        ]);

        $pdf->setPaper('a4', 'landscape');
        $pdf->setOptions([
            'isHtml5ParserEnabled' => true,
            'isRemoteEnabled' => false,
            'defaultFont' => 'DejaVu Sans',
            'isFontSubsettingEnabled' => true,
            'dpi' => 150,
        ]);

        return $pdf->download(
            'CampaignSummary_' . Str::slug($bvCampign->campaign_name ?: 'campaign') . '_' . now()->format('Ymd') . '.pdf'
        );
    }
}
