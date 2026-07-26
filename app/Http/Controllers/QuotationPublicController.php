<?php

namespace App\Http\Controllers;

use App\Models\BvQuotation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class QuotationPublicController extends Controller
{
    public function show(string $token)
    {
        $quotation = BvQuotation::where('public_token', $token)
            ->where('is_public', true)
            ->with(['internalBudget.items.mediaPlanKol', 'mediaPlan.bvSales'])
            ->firstOrFail();

        $items = $quotation->public_items;

        // Group by KOL name — KOL sama digabung, SOW ditampilkan bersamaan
        $groupedItems = $items->groupBy(function ($item) {
            return $item->mediaPlanKol?->name ?? $item->scope_item;
        })->map(function ($kolItems) {
            return [
                'kol_name'   => $kolItems->first()->mediaPlanKol?->name ?? $kolItems->first()->scope_item,
                'sow_list'   => $kolItems->pluck('scope_item')->filter()->values(),
                'total_rate' => $kolItems->sum('rounded'),
                'items'      => $kolItems,
            ];
        })->values();

        $totalAmount = $items->sum('rounded');

        $campaignName = $quotation->mediaPlan?->campaign_name
            ?? $quotation->internalBudget?->mediaPlan?->campaign_name
            ?? $quotation->notes
            ?? '-';

        return view('quotation.public', compact(
            'quotation',
            'items',
            'groupedItems',
            'totalAmount',
            'campaignName',
        ));
    }

    /**
     * Tanda tangan client di halaman public. Urutan dijaga model:
     * client baru bisa tanda tangan setelah CEO & Business Development.
     * Gambar TTD dikirim sebagai data URL PNG dari canvas.
     */
    public function sign(Request $request, string $token)
    {
        $quotation = BvQuotation::where('public_token', $token)
            ->where('is_public', true)
            ->firstOrFail();

        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'job_title' => ['nullable', 'string', 'max:120'],
            'signature' => ['required', 'string', 'starts_with:data:image/png;base64,', 'max:2000000'],
            'agree' => ['accepted'],
        ]);

        if (! $quotation->canSign('client')) {
            return back()->withErrors([
                'signature' => $quotation->isFullySigned()
                    ? 'Quotation ini sudah ditandatangani.'
                    : 'Menunggu tanda tangan ' . BvQuotation::SIGN_FLOW[$quotation->nextSigner()] . ' dari pihak Beyond Viral.',
            ]);
        }

        $binary = base64_decode(substr($data['signature'], strlen('data:image/png;base64,')), true);
        if ($binary === false) {
            return back()->withErrors(['signature' => 'Gambar tanda tangan tidak valid.']);
        }

        $path = "signatures/quotation-{$quotation->id}-client.png";
        Storage::disk('public')->put($path, $binary);

        $quotation->sign('client', $data['name'], $data['job_title'] ?? null, $path);

        return redirect()
            ->route('quotation.public', ['token' => $token])
            ->with('signed', true);
    }
}
