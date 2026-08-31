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
 * client bisa menandai KOL mana yang dipakai (✓ / ✗) dan memberi feedback.
 *
 * Keputusannya satu per KOL, bukan per SOW: client memilih orangnya, bukan
 * memilah-milah paket kerjanya — dan satu halaman berisi 98 KOL × 6 SOW tidak
 * mungkin dibaca. Rincian SOW-nya ada di modal tiap baris. Penyimpanannya
 * tetap per item (client_choice & client_feedback): pilihan satu KOL ditulis
 * ke seluruh SOW miliknya.
 *
 * Client juga bisa mengusulkan KOL penggantinya sendiri (client_replace_note).
 * Itu usulan, bukan penggantian: yang benar-benar mengganti KOL tetap aksi
 * "Ganti KOL" di Media Plan External, dijalankan BV setelah usulannya dibaca.
 * Usulannya muncul sebagai keterangan di baris KOL Media Plan Internal.
 *
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

        $groupedItems = self::kelompokPerKol($budget)
            ->map(function ($kolItems, $key) {
                $kol = $kolItems->first()->mediaPlanKol;
                $pilihan = $kolItems->pluck('client_choice')->unique();

                return [
                    'key'        => $key,
                    'kol_name'   => $kol?->name ?? $kolItems->first()->scope_item,
                    'kol'        => $kol,
                    'items'      => $kolItems->values(),
                    'username'   => $kol?->dataKol?->username,
                    'sow_utama'  => $kolItems->first()->scope_item,
                    'jumlah_sow' => $kolItems->count(),
                    'total'      => (float) $kolItems->sum('rounded'),
                    // Catatan KOL dari Media Plan Internal.
                    'notes'      => $kol?->notes,
                    // Tersimpan per item, ditampilkan per KOL: dianggap terpilih
                    // hanya kalau seluruh SOW-nya seragam. Data lama yang dipilih
                    // per SOW karena itu tampil sebagai "belum dipilih".
                    'choice'     => $pilihan->count() === 1 ? $pilihan->first() : null,
                    'feedback'   => $kolItems->pluck('client_feedback')->filter()->first(),
                    'replace'    => $kolItems->pluck('client_replace_note')->filter()->first(),
                ];
            })
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
            'replace'          => ['array'],
            'replace.*'        => ['nullable', 'string', 'max:2000'],
        ])->validate();

        $choices  = $data['choices'] ?? [];
        $feedback = $data['feedback'] ?? [];
        $replace  = $data['replace'] ?? [];

        // Kuncinya per KOL; pilihannya ditulis ke seluruh SOW milik KOL itu.
        // Hanya item milik budget ini yang tersentuh — daftar id-nya datang dari
        // relasi, bukan dari request.
        foreach (self::kelompokPerKol($budget) as $key => $kolItems) {
            InternalBudgetItem::whereIn('id', $kolItems->pluck('id'))
                ->where('internal_budget_id', $budget->id)
                ->update([
                    'client_choice'       => $choices[$key] ?? null,
                    'client_feedback'     => $feedback[$key] ?? null,
                    'client_replace_note' => $replace[$key] ?? null,
                ]);
        }

        $budget->forceFill(['review_submitted_at' => now()])->saveQuietly();

        return redirect()
            ->route('media-plan-external.review', ['token' => $token])
            ->with('submitted', true);
    }

    /**
     * Item budget dikelompokkan per KOL.
     *
     * Kuncinya id KOL, bukan namanya: KOL pengganti bisa bernama sama dengan
     * yang digantikan dan barisnya tidak boleh menyatu. Item yang belum
     * terhubung ke KOL mana pun berdiri sendiri, diberi awalan berbeda supaya
     * id-nya tidak bertabrakan dengan id KOL.
     *
     * @return \Illuminate\Support\Collection<string, \Illuminate\Support\Collection<int, InternalBudgetItem>>
     */
    private static function kelompokPerKol(InternalBudget $budget): \Illuminate\Support\Collection
    {
        return $budget->items->groupBy(fn($item) => $item->media_plan_kol_id
            ? 'kol-' . $item->media_plan_kol_id
            : 'item-' . $item->id);
    }
}
