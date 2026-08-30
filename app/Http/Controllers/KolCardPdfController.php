<?php

namespace App\Http\Controllers;

use App\Models\DataKol;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Str;

class KolCardPdfController extends Controller
{
    /** Kartu profil KOL satu halaman, berisi angka gabungan + tulisan AI. */
    public function generate(DataKol $dataKol)
    {
        abort_unless(filled($dataKol->ai_insight), 404, 'Kartu AI untuk KOL ini belum pernah dibuat.');

        $logo = public_path('images/logo_bv.png');

        $pdf = Pdf::loadView('pdf.kol-card', [
            'kol' => $dataKol,
            'gabungan' => $dataKol->crossChannelSummary(),
            'channels' => $dataKol->channelSiblings(),
            'logoBase64' => file_exists($logo)
                ? 'data:image/png;base64,' . base64_encode(file_get_contents($logo))
                : null,
        ]);

        $pdf->setPaper('a4', 'portrait');
        $pdf->setOptions([
            'isHtml5ParserEnabled' => true,
            // Foto profil KOL di-host di CDN Instagram/TikTok, jadi harus boleh remote.
            'isRemoteEnabled' => true,
            'defaultFont' => 'DejaVu Sans',
            'isFontSubsettingEnabled' => true,
            'dpi' => 150,
        ]);

        return $pdf->download('KartuKOL_' . Str::slug($dataKol->username) . '_' . now()->format('Ymd') . '.pdf');
    }
}
