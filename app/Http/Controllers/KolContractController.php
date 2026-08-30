<?php

namespace App\Http\Controllers;

use App\Models\BvSPK;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Http\Request;

class KolContractController extends Controller
{
    private static function getLogoBase64(): ?string
    {
        $logoPath = public_path('images/logo_bv.png');
        if (file_exists($logoPath)) {
            return 'data:image/png;base64,' . base64_encode(file_get_contents($logoPath));
        }
        return null;
    }

    /** Public & static supaya SpkPublicController (halaman e-sign) memakai data yang sama. */
    public static function prepareData(BvSPK $spk): array
    {
        $tanggal = $spk->tanggal_perjanjian
            ? Carbon::parse($spk->tanggal_perjanjian)
            : Carbon::now();

        return [
            'spk'        => $spk,
            'client'     => $spk->client,
            'tanggalId'  => $tanggal->day . ' ' . BvSPK::MONTHS_ID[$tanggal->month] . ' ' . $tanggal->year,
            'logoBase64' => self::getLogoBase64(),
            // dompdf tidak bisa memuat URL storage; gambar TTD & materai harus di-inline base64.
            'signatureBase64' => self::inlineImage($spk->signature_path),
            'materaiBase64' => self::inlineImage($spk->materai_path),
        ];
    }

    private static function inlineImage(?string $path): ?string
    {
        if (blank($path)) {
            return null;
        }

        $disk = \Illuminate\Support\Facades\Storage::disk('public');

        return $disk->exists($path)
            ? 'data:image/png;base64,' . base64_encode($disk->get($path))
            : null;
    }

    /**
     * Download KOL contract as PDF
     */
    public function download(BvSPK $spk)
    {
        $data = self::prepareData($spk);

        $pdf = Pdf::loadView('pdf.kol-contract', $data);
        $pdf->setPaper('a4', 'portrait');

        $filename = 'Kontrak_KOL_' . $spk->spk_number . '_' . Carbon::now()->format('Ymd') . '.pdf';
        $filename = str_replace(['/', ' '], ['_', '_'], $filename);

        return $pdf->download($filename);
    }

    /**
     * Stream KOL contract PDF in browser
     */
    public function preview(BvSPK $spk)
    {
        $data = self::prepareData($spk);

        $pdf = Pdf::loadView('pdf.kol-contract', $data);
        $pdf->setPaper('a4', 'portrait');

        return $pdf->stream('kol_contract_preview.pdf');
    }

    /**
     * Render HTML version (for debugging / Filament iframe preview)
     */
    public function html(BvSPK $spk)
    {
        $data = self::prepareData($spk);
        return view('pdf.kol-contract', $data);
    }
}
