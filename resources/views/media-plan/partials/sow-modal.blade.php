{{--
    Rincian SOW satu KOL. $group = satu elemen $groupedItems.

    Pakai <dialog> bawaan browser, bukan modal buatan sendiri: penengahan,
    backdrop, tutup dengan Esc, dan jebakan fokus sudah jadi tanpa satu baris JS
    pun. Halaman ini publik dan dibuka client dari HP — makin sedikit yang
    dijalankan makin baik.
--}}
<dialog id="sow-{{ $group['key'] }}"
        class="w-[min(40rem,92vw)] rounded-2xl p-0 backdrop:bg-gray-900/50">
    <div class="flex items-start justify-between gap-4 border-b border-gray-100 px-5 py-4">
        <div class="min-w-0">
            <p class="text-base font-semibold text-gray-900">{{ $group['kol_name'] }}</p>
            <p class="text-xs text-gray-500">{{ $group['jumlah_sow'] }} scope of work</p>
        </div>
        <form method="dialog">
            <button class="rounded-lg px-2 py-1 text-xl leading-none text-gray-400 hover:bg-gray-100 hover:text-gray-600"
                    aria-label="Tutup">&times;</button>
        </form>
    </div>

    <div class="max-h-[70vh] overflow-y-auto px-5 py-4">
        @include('media-plan.partials.kol-stats', ['kol' => $group['kol']])

        @if ($group['notes'])
            <div class="mt-4 rounded-xl bg-amber-50 border border-amber-100 px-3 py-2">
                <p class="text-[10px] font-semibold uppercase tracking-wide text-amber-700">Catatan</p>
                <p class="mt-0.5 text-xs text-amber-900 whitespace-pre-line">{{ $group['notes'] }}</p>
            </div>
        @endif

        <table class="mt-4 w-full text-sm">
            <thead>
                <tr class="border-b border-gray-100 text-left text-[10px] uppercase tracking-wide text-gray-400">
                    <th class="pb-2 font-semibold">Scope of Work</th>
                    <th class="pb-2 text-center font-semibold">Qty</th>
                    <th class="pb-2 text-right font-semibold">Harga</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-50">
                @foreach ($group['items'] as $item)
                    <tr>
                        <td class="py-2 pr-3 text-gray-700">{{ $item->scope_item }}</td>
                        <td class="py-2 text-center text-gray-500">{{ $item->qty }}</td>
                        <td class="py-2 text-right font-medium text-gray-900">
                            {{-- Rp 0 dibaca client sebagai "gratis"; sebagian SOW memang
                                 belum berharga di media plan. Strip lebih jujur. --}}
                            @if (($item->rounded ?? 0) > 0)
                                Rp {{ number_format($item->rounded, 0, ',', '.') }}
                            @else
                                <span class="text-gray-300">&mdash;</span>
                            @endif
                        </td>
                    </tr>
                @endforeach
            </tbody>
            <tfoot>
                <tr class="border-t border-gray-200">
                    <td class="pt-2 text-xs font-semibold text-gray-500" colspan="2">Total</td>
                    <td class="pt-2 text-right text-sm font-bold text-gray-900">
                        {!! $group['total'] > 0 ? 'Rp ' . number_format($group['total'], 0, ',', '.') : '<span class="text-gray-300">&mdash;</span>' !!}
                    </td>
                </tr>
            </tfoot>
        </table>
    </div>
</dialog>
