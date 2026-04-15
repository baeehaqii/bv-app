<?php

namespace App\Http\Controllers;

use App\Models\BvSPK;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Http\Request;

class KolContractController extends Controller
{
    private function getLogoBase64(): ?string
    {
        $logoPath = public_path('images/logo-bv.png');
        if (file_exists($logoPath)) {
            return 'data:image/png;base64,' . base64_encode(file_get_contents($logoPath));
        }
        return null;
    }

    private function prepareData(BvSPK $spk): array
    {
        $tanggal = $spk->tanggal_perjanjian
            ? Carbon::parse($spk->tanggal_perjanjian)
            : Carbon::now();

        $bulanId = [
            1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April',
            5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus',
            9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember',
        ];

        $bulanEn = [
            1 => 'January', 2 => 'February', 3 => 'March', 4 => 'April',
            5 => 'May', 6 => 'June', 7 => 'July', 8 => 'August',
            9 => 'September', 10 => 'October', 11 => 'November', 12 => 'December',
        ];

        return [
            'spk'            => $spk,
            'client'         => $spk->client,
            'tanggalId'      => $tanggal->day . ' ' . $bulanId[$tanggal->month] . ' ' . $tanggal->year,
            'tanggalEn'      => $bulanEn[$tanggal->month] . ' ' . $tanggal->day . ', ' . $tanggal->year,
            'logoBase64'     => $this->getLogoBase64(),
            'generatedAt'    => Carbon::now()->format('d M Y H:i'),
        ];
    }

    /**
     * Download KOL contract as PDF
     */
    public function download(BvSPK $spk)
    {
        $data = $this->prepareData($spk);

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
        $data = $this->prepareData($spk);

        $pdf = Pdf::loadView('pdf.kol-contract', $data);
        $pdf->setPaper('a4', 'portrait');

        return $pdf->stream('kol_contract_preview.pdf');
    }

    /**
     * Render HTML version (for debugging / Filament iframe preview)
     */
    public function html(BvSPK $spk)
    {
        $data = $this->prepareData($spk);
        return view('pdf.kol-contract', $data);
    }
}
