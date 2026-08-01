{{-- Rate card 1 KOL, dikelompokkan per channel. $record = DataKol (baris wakil) --}}
@php
    // Hanya channel yang punya rate card yang ditampilkan; sisanya diringkas di catatan bawah.
    $berisi = $record->channels->filter(fn($c) => $c->rateCards->isNotEmpty())->sortBy('channel');
    $kosong = $record->channels->filter(fn($c) => $c->rateCards->isEmpty())->pluck('channel')->filter();
@endphp

@if ($berisi->isEmpty())
    <p class="py-6 text-center text-sm text-gray-500">
        Belum ada rate card. Tambahkan lewat tombol <strong>Detail</strong> → section “Rate Card Per Channel”.
    </p>
@else
    <div class="space-y-5">
        @foreach ($berisi as $channel)
            <div>
                <div class="mb-2 flex items-baseline gap-2">
                    <span class="text-sm font-semibold">{{ $channel->channel }}</span>
                    <span class="text-xs text-gray-400">
                        {{ number_format((int) $channel->followers) }} followers
                    </span>
                </div>

                <div style="overflow-x:auto;">
                    <table class="w-full text-left text-sm" style="min-width:36rem;">
                        <thead>
                            <tr class="border-b text-xs uppercase text-gray-400">
                                <th class="py-2 pr-4">SOW</th>
                                <th class="py-2 pr-4 text-right">Rate</th>
                                <th class="py-2 pr-4">Berlaku Dari</th>
                                <th class="py-2">Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($channel->rateCards as $card)
                                <tr class="border-b last:border-0">
                                    <td class="py-2 pr-4">{{ $card->sow_label }}</td>
                                    <td class="py-2 pr-4 text-right font-semibold text-primary-600">
                                        Rp{{ number_format((float) $card->rate, 0, ',', '.') }}
                                    </td>
                                    <td class="py-2 pr-4">{{ $card->valid_from?->format('d M Y') ?? '—' }}</td>
                                    <td class="py-2">
                                        @if ($card->isExpired())
                                            <span class="rounded-full bg-rose-100 px-2 py-0.5 text-xs font-medium text-rose-700 dark:bg-rose-950/60 dark:text-rose-300">
                                                Kedaluwarsa
                                            </span>
                                        @else
                                            <span class="rounded-full bg-emerald-100 px-2 py-0.5 text-xs font-medium text-emerald-700 dark:bg-emerald-950/60 dark:text-emerald-300">
                                                Berlaku
                                            </span>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        @endforeach
    </div>

    <p class="mt-4 text-xs italic text-gray-400">Harga dalam satuan Rupiah.</p>
@endif

@if ($kosong->isNotEmpty())
    <p class="mt-2 text-xs text-gray-400">
        Belum ada rate card untuk: {{ $kosong->implode(', ') }}.
    </p>
@endif
