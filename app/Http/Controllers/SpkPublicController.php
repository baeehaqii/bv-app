<?php

namespace App\Http\Controllers;

use App\Models\BvSPK;
use App\Models\DataKol;
use Illuminate\Http\Request;

/**
 * Halaman publik e-SPK: KOL verifikasi data → baca dokumen → tanda tangan.
 * Tanpa auth — gerbangnya token 48 karakter di URL + langkah verifikasi.
 */
class SpkPublicController extends Controller
{
    public function show(string $token)
    {
        $spk = $this->resolve($token);

        return view('spk.public', [
            'spk' => $spk,
            'step' => $this->currentStep($spk),
            'platforms' => $this->platformOptions(),
        ]);
    }

    /**
     * Langkah 1. Gagal cocok = balik ke form dengan error; tidak membocorkan
     * field mana yang salah supaya link yang bocor tidak bisa dipakai menebak data.
     */
    public function verify(Request $request, string $token)
    {
        $spk = $this->resolve($token);

        if ($spk->isSigned()) {
            return redirect()->route('spk.public', ['token' => $token]);
        }

        $data = $request->validate([
            'spk_number' => ['required', 'string', 'max:64'],
            'name' => ['required', 'string', 'max:191'],
            'platform' => ['nullable', 'string', 'max:64'],
        ]);

        if (! $spk->matchesVerification($data['spk_number'], $data['name'], $data['platform'] ?? null)) {
            return back()
                ->withInput()
                ->withErrors(['spk_number' => 'Data yang dimasukkan tidak cocok dengan SPK ini. Periksa kembali No. SPK, nama lengkap, dan platform Anda.']);
        }

        $request->session()->put($this->sessionKey($spk), true);

        return redirect()->route('spk.public', ['token' => $token]);
    }

    /** Langkah 3. */
    public function sign(Request $request, string $token)
    {
        $spk = $this->resolve($token);

        // Replay POST setelah tanda tangan sah tidak boleh menimpa yang sudah ada.
        if ($spk->isSigned()) {
            return redirect()->route('spk.public', ['token' => $token]);
        }

        if (! $request->session()->get($this->sessionKey($spk))) {
            return redirect()->route('spk.public', ['token' => $token])
                ->withErrors(['spk_number' => 'Sesi verifikasi berakhir. Mohon verifikasi ulang data Anda.']);
        }

        $data = $request->validate([
            'signature' => ['required', 'string', 'starts_with:data:image/png;base64,', 'max:2000000'],
            'agree' => ['accepted'],
        ]);

        try {
            $spk->signByKol($data['signature'], $request->ip());
        } catch (\InvalidArgumentException $e) {
            return back()->withErrors(['signature' => $e->getMessage()]);
        }

        return redirect()->route('spk.public', ['token' => $token])->with('just_signed', true);
    }

    /** Dokumen SPK versi HTML untuk iframe preview — dibuka pakai token, tanpa login. */
    public function document(string $token)
    {
        return view('pdf.kol-contract', KolContractController::prepareData($this->resolve($token)));
    }

    /**
     * Download dokumen yang sudah ditandatangani. PDF di-generate ulang dari
     * data + gambar tanda tangan, jadi tidak ada file hasil tanda tangan yang
     * bisa basi terhadap datanya.
     */
    public function download(string $token)
    {
        $spk = $this->resolve($token);

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('pdf.kol-contract', KolContractController::prepareData($spk))
            ->setPaper('a4', 'portrait');

        $name = str_replace(['/', ' '], ['_', '_'], 'SPK_' . $spk->spk_number)
            . ($spk->isSigned() ? '_signed' : '') . '.pdf';

        return $pdf->download($name);
    }

    private function resolve(string $token): BvSPK
    {
        return BvSPK::where('public_token', $token)
            ->whereNotNull('public_token')
            ->where('status', '!=', 'cancelled')
            ->with(['dataKol', 'mediaPlanKol'])
            ->firstOrFail();
    }

    /** 1 = verifikasi, 2 = preview + tanda tangan, 4 = selesai. */
    private function currentStep(BvSPK $spk): int
    {
        if ($spk->isSigned()) {
            return 4;
        }

        return session($this->sessionKey($spk)) ? 2 : 1;
    }

    private function sessionKey(BvSPK $spk): string
    {
        return "spk_verified_{$spk->id}";
    }

    /** @return array<int, string> */
    private function platformOptions(): array
    {
        return DataKol::query()
            ->whereNotNull('channel')
            ->distinct()
            ->orderBy('channel')
            ->pluck('channel')
            ->all();
    }
}
