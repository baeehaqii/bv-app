<?php

namespace App\Http\Controllers;

use App\Models\BvQuotation;

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
}
