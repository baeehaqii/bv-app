{{-- Tabel hasil scraping per channel untuk 1 KOL (dikelompokkan by username) + kartu KOL per channel. --}}
<div x-data="{ open: null }" class="text-sm">
    <div style="overflow-x:auto;">
        <table class="w-full text-left" style="min-width:52rem;">
            <thead>
                <tr class="border-b text-xs uppercase text-gray-400">
                    <th class="py-2 pr-4">Channel</th>
                    <th class="py-2 pr-4">Followers</th>
                    <th class="py-2 pr-4">Tier</th>
                    <th class="py-2 pr-4">ER</th>
                    <th class="py-2 pr-4">Engagements</th>
                    <th class="py-2 pr-4">Avg Impressions</th>
                    <th class="py-2 pr-4">Update</th>
                    <th class="py-2">Kartu</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($rows as $row)
                    <tr class="border-b" @class(['bg-primary-50 dark:bg-primary-950/40' => $row->id === $currentId])>
                        <td class="py-2 pr-4 font-medium">{{ $row->channel ?: '-' }}</td>
                        <td class="py-2 pr-4">{{ number_format((int) $row->followers) }}</td>
                        <td class="py-2 pr-4">{{ $row->tier ?: '-' }}</td>
                        <td class="py-2 pr-4">{{ number_format((float) $row->engagement_rate, 2) }}%</td>
                        <td class="py-2 pr-4">{{ number_format((int) $row->engagements) }}</td>
                        <td class="py-2 pr-4">{{ number_format((int) $row->impressions) }}</td>
                        <td class="py-2 pr-4">{{ $row->terakhir_update?->format('d M Y') ?? '-' }}</td>
                        <td class="py-2">
                            <button type="button" title="Lihat kartu KOL"
                                x-on:click="open = {{ $row->id }}"
                                class="text-primary-600 hover:text-primary-500">
                                <x-filament::icon icon="heroicon-o-eye" class="h-5 w-5" />
                            </button>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    @foreach ($rows as $row)
        {{-- teleport ke body: kalau tidak, modal ke-clip container form yang punya transform/overflow --}}
        <template x-teleport="body">
        <div x-cloak x-show="open === {{ $row->id }}" x-on:keydown.escape.window="open = null"
            class="fixed inset-0 z-50 flex items-start justify-center overflow-y-auto bg-black/50 p-4">
            <div x-on:click.outside="open = null"
                class="my-8 w-full max-w-2xl rounded-xl bg-white p-6 shadow-xl dark:bg-gray-900">

                <div class="mb-4 flex items-start justify-between">
                    <div>
                        <div class="text-xs uppercase tracking-wide text-gray-400">
                            Rate Card {{ $row->channel }}
                        </div>
                        <div class="text-xl font-bold">&#64;{{ $row->username }}</div>
                        <div class="text-sm text-gray-500">
                            {{ number_format((int) $row->followers) }} Followers
                            @if ($row->tier)
                                <span class="ml-1 rounded-full bg-primary-50 px-2 py-0.5 text-xs text-primary-700 dark:bg-primary-950 dark:text-primary-300">{{ $row->tier }}</span>
                            @endif
                        </div>
                        <div class="mt-1 text-xs text-gray-500">
                            {{ $row->full_name ?: '-' }} &middot; {{ $row->email ?: '-' }} &middot; {{ $row->wa_number ?: '-' }}
                        </div>
                    </div>
                    <button type="button" x-on:click="open = null" class="text-gray-400 hover:text-gray-600">
                        <x-filament::icon icon="heroicon-o-x-mark" class="h-5 w-5" />
                    </button>
                </div>

                <div class="rounded-lg bg-gray-50 p-4 dark:bg-gray-800">
                    <div class="mb-3 text-center text-sm font-semibold tracking-wide">RATE CARD</div>
                    @if ($row->rateCards->isEmpty())
                        <p class="text-center text-sm text-gray-500">Belum ada rate card.</p>
                    @else
                        <div class="grid grid-cols-2 gap-4 sm:grid-cols-4">
                            @foreach ($row->rateCards as $card)
                                <div class="text-center">
                                    <div class="font-bold text-primary-600">
                                        Rp{{ number_format((float) $card->rate, 0, ',', '.') }}
                                    </div>
                                    <div class="text-xs text-gray-500">{{ $card->sow_label }}</div>
                                </div>
                            @endforeach
                        </div>
                    @endif
                    <p class="mt-3 text-center text-xs italic text-gray-400">Harga dalam satuan Rupiah</p>
                </div>

                <div class="mt-4 grid grid-cols-2 gap-4 sm:grid-cols-4 text-center">
                    <div>
                        <div class="font-bold">{{ number_format((float) $row->engagement_rate, 2) }}%</div>
                        <div class="text-xs text-gray-500">Engagement Rate</div>
                    </div>
                    <div>
                        <div class="font-bold">{{ number_format((int) $row->engagements) }}</div>
                        <div class="text-xs text-gray-500">Total Engagements</div>
                    </div>
                    <div>
                        <div class="font-bold">{{ number_format((int) $row->impressions) }}</div>
                        <div class="text-xs text-gray-500">Avg Impressions</div>
                    </div>
                    <div>
                        <div class="font-bold">{{ number_format((int) $row->followers) }}</div>
                        <div class="text-xs text-gray-500">Followers</div>
                    </div>
                </div>

                @if (filled($row->category))
                    <div class="mt-4 flex flex-wrap gap-1">
                        @foreach ((array) $row->category as $cat)
                            <span class="rounded bg-gray-100 px-2 py-0.5 text-xs dark:bg-gray-800">{{ $cat }}</span>
                        @endforeach
                    </div>
                @endif

                @if ($row->notes)
                    <pre class="mt-4 whitespace-pre-wrap rounded bg-gray-50 p-3 text-xs text-gray-600 dark:bg-gray-800 dark:text-gray-300">{{ $row->notes }}</pre>
                @endif

                <div class="mt-4 text-right text-xs text-gray-400">
                    Last updated {{ $row->terakhir_update?->format('Y-m-d') ?? '-' }}
                </div>
            </div>
        </div>
        </template>
    @endforeach
</div>
