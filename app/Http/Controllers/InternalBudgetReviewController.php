<?php

namespace App\Http\Controllers;

use App\Models\InternalBudget;
use App\Models\InternalBudgetItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

/**
 * Link Review Client untuk Media Plan External (InternalBudget).
 *
 * Halaman publik (tanpa auth) yang menampilkan Budget Items ke client agar
 * client bisa menandai SOW mana yang dipakai (✓ / ✗) dan memberi feedback.
 * Hasilnya disimpan ke kolom client_choice & client_feedback per item.
 * Promosi status budget tetap dilakukan manual oleh BV.
 */
class InternalBudgetReviewController extends Controller
{
    public function show(string $token)
    {
        $budget = InternalBudget::where('review_token', $token)
            ->where('review_is_public', true)
            ->with(['items.mediaPlanKol.dataKol', 'mediaPlan.bvSales.client'])
            ->firstOrFail();

        $items = $budget->items;

        // Group SOW per KOL agar tampil rapi (1 KOL bisa banyak scope item).
        // Group by id KOL (bukan nama) supaya baris KOL pengganti dengan nama sama tidak menyatu.
        $groupedItems = $items->groupBy(fn($item) => $item->media_plan_kol_id ?? 'item-' . $item->id)
            ->map(fn($kolItems) => [
                'kol_name' => $kolItems->first()->mediaPlanKol?->name ?? $kolItems->first()->scope_item,
                'kol'      => $kolItems->first()->mediaPlanKol,
                'items'    => $kolItems->values(),
            ])
            ->values();

        $campaignName = $budget->mediaPlan?->campaign_name ?? '-';
        $clientName   = $budget->mediaPlan?->bvSales?->client?->nama_brand
            ?? $budget->mediaPlan?->brand
            ?? 'Client';

        $alreadySubmitted = $budget->review_submitted_at !== null;

        return view('media-plan.review', compact(
            'budget',
            'groupedItems',
            'campaignName',
            'clientName',
            'alreadySubmitted',
        ));
    }

    public function submit(Request $request, string $token)
    {
        $budget = InternalBudget::where('review_token', $token)
            ->where('review_is_public', true)
            ->with('items')
            ->firstOrFail();

        $data = Validator::make($request->all(), [
            'choices'          => ['array'],
            'choices.*'        => ['nullable', 'in:approved,rejected'],
            'feedback'         => ['array'],
            'feedback.*'       => ['nullable', 'string', 'max:2000'],
        ])->validate();

        $choices  = $data['choices'] ?? [];
        $feedback = $data['feedback'] ?? [];

        // Hanya item milik budget ini yang boleh di-update.
        $itemIds = $budget->items->pluck('id')->all();

        foreach ($itemIds as $id) {
            $choice = $choices[$id] ?? null;
            $fb     = $feedback[$id] ?? null;

            InternalBudgetItem::where('id', $id)
                ->where('internal_budget_id', $budget->id)
                ->update([
                    'client_choice'   => $choice,
                    'client_feedback' => $fb,
                ]);
        }

        $budget->forceFill(['review_submitted_at' => now()])->saveQuietly();

        return redirect()
            ->route('media-plan-external.review', ['token' => $token])
            ->with('submitted', true);
    }
}
