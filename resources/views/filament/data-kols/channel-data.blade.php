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
                    <th class="py-2">Aksi</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($rows as $row)
                    @php $aktif = $row->id === $currentId; @endphp
                    <tr class="border-b" @class(['bg-primary-50 dark:bg-primary-950/40' => $aktif])>
                        <td class="py-2 pr-4 font-medium">
                            {{ $row->channel ?: '-' }}
                            @if ($aktif)
                                {{-- Menandai baris mana yang disunting form di bawah: rate card,
                                     Additional Info, dan data legal semuanya milik baris ini. --}}
                                <span class="ml-1 cursor-help rounded-full bg-primary-100 px-1.5 py-0.5 text-[10px] font-medium text-primary-700 dark:bg-primary-900 dark:text-primary-200"
                                    title="Rate Card & Additional Info di bawah adalah milik channel ini. Klik ikon pensil di baris lain untuk menyuntingnya.">
                                    disunting di bawah
                                </span>
                            @endif
                        </td>
                        <td class="py-2 pr-4">{{ number_format((int) $row->followers) }}</td>
                        <td class="py-2 pr-4">{{ $row->tier ?: '-' }}</td>
                        <td class="py-2 pr-4">{{ number_format((float) $row->engagement_rate, 2) }}%</td>
                        <td class="py-2 pr-4">{{ number_format((int) $row->engagements) }}</td>
                        <td class="py-2 pr-4">{{ number_format((int) $row->impressions) }}</td>
                        <td class="py-2 pr-4">{{ $row->terakhir_update?->format('d M Y') ?? '-' }}</td>
                        <td class="py-2">
                            <div class="flex items-center gap-2">
                                <button type="button" title="Lihat kartu KOL"
                                    x-on:click="open = {{ $row->id }}"
                                    class="text-primary-600 hover:text-primary-500">
                                    <x-filament::icon icon="heroicon-o-eye" class="h-5 w-5" />
                                </button>

                                {{-- Hanya channel yang punya service scraping yang bisa di-refresh. --}}
                                @if (array_key_exists($row->channel, \App\Service\KolProfileImporter::SCRAPABLE))
                                    <button type="button" title="Ambil ulang data terbaru dari {{ $row->channel }}"
                                        wire:click="refreshChannel({{ $row->id }})"
                                        wire:target="refreshChannel({{ $row->id }})"
                                        wire:loading.attr="disabled"
                                        class="text-gray-400 hover:text-primary-500 disabled:opacity-50">
                                        <x-filament::icon icon="heroicon-o-arrow-path" class="h-5 w-5"
                                            wire:loading.class="animate-spin"
                                            wire:target="refreshChannel({{ $row->id }})" />
                                    </button>
                                @endif

                                @if (filled($row->link_userprofile))
                                    <a href="{{ \Illuminate\Support\Str::startsWith($row->link_userprofile, ['http://', 'https://']) ? $row->link_userprofile : '#' }}"
                                        target="_blank" rel="noopener" title="Buka profil {{ $row->channel }}"
                                        class="text-gray-400 hover:text-primary-500">
                                        <x-filament::icon icon="heroicon-o-arrow-top-right-on-square" class="h-5 w-5" />
                                    </a>
                                @endif

                                {{-- Rate card & data tiap channel tersimpan di barisnya sendiri,
                                     jadi untuk menyunting channel lain harus pindah baris. --}}
                                @unless ($aktif)
                                    <a href="{{ \App\Filament\Resources\DataKols\DataKolResource::getUrl('edit', ['record' => $row]) }}"
                                        title="Sunting channel ini" class="text-gray-400 hover:text-primary-500">
                                        <x-filament::icon icon="heroicon-o-pencil-square" class="h-5 w-5" />
                                    </a>
                                @endunless

                                @php
                                    $jumlahRateCard = $row->rateCards->count();
                                    $konfirmasi = "Hapus channel {$row->channel} milik @{$row->username}?"
                                        . ($jumlahRateCard > 0 ? " {$jumlahRateCard} rate card ikut terhapus." : '')
                                        . ' Tindakan ini tidak bisa dibatalkan.';
                                @endphp
                                <button type="button" title="Hapus channel ini"
                                    wire:click="deleteChannel({{ $row->id }})"
                                    wire:confirm="{{ $konfirmasi }}"
                                    wire:target="deleteChannel({{ $row->id }})"
                                    wire:loading.attr="disabled"
                                    class="text-gray-400 hover:text-danger-500 disabled:opacity-50">
                                    <x-filament::icon icon="heroicon-o-trash" class="h-5 w-5" />
                                </button>
                            </div>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    @foreach ($rows as $row)
        {{-- teleport ke body: kalau tidak, modal ke-clip container form yang punya transform/overflow --}}
        <template x-teleport="body">
        @php
            // notes hasil scraping mencampur bio dengan statistik yang sudah tampil di kartu —
            // baris statistik itu dibuang, sisakan bio & kontaknya saja.
            $noteLines = collect(explode("\n", (string) $row->notes))->map(fn($l) => trim($l));
            $isVerified = $noteLines->contains(fn($l) => str_contains($l, 'Verified Account'));
            $bio = $noteLines
                ->reject(fn($l) => $l === '' || preg_match(
                    '/^(Tier|Engagement Rate|Avg Impressions|Avg Likes|Avg Comments|Following|Posts|Videos|Shorts)\s*:/u',
                    $l
                ) || str_contains($l, 'Verified Account'))
                ->implode("\n");
            $contact = array_filter([$row->full_name, $row->email, $row->wa_number]);
        @endphp
        <div x-cloak x-show="open === {{ $row->id }}" x-on:keydown.escape.window="open = null"
            class="fixed inset-0 z-50 flex items-center justify-center overflow-y-auto bg-black/50 p-4">
            <div x-on:click.outside="open = null"
                class="max-h-[90vh] w-full max-w-2xl overflow-y-auto rounded-xl bg-white p-6 shadow-xl dark:bg-gray-900">

                <div class="mb-4 flex items-start justify-between">
                    <div>
                        <div class="text-xs uppercase tracking-wide text-gray-400">
                            Detail {{ $row->channel }}
                        </div>
                        <div class="flex items-center gap-1 text-xl font-bold">
                            &#64;{{ $row->username }}
                            @if ($isVerified)
                                <x-filament::icon icon="heroicon-s-check-badge" class="h-5 w-5 text-blue-500"
                                    title="Verified Account" />
                            @endif
                        </div>
                        <div class="text-sm text-gray-500">
                            {{ number_format((int) $row->followers) }} Followers
                            @if ($row->tier)
                                <span class="ml-1 rounded-full bg-primary-50 px-2 py-0.5 text-xs text-primary-700 dark:bg-primary-950 dark:text-primary-300">{{ $row->tier }}</span>
                            @endif
                        </div>
                        @if ($row->link_userprofile)
                            <a href="{{ \Illuminate\Support\Str::startsWith($row->link_userprofile, ['http://', 'https://']) ? $row->link_userprofile : '#' }}"
                                target="_blank" rel="noopener"
                                class="mt-1 inline-block break-all text-xs text-primary-600 underline hover:text-primary-500">
                                {{ $row->link_userprofile }}
                            </a>
                        @endif
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

                <div class="mt-4 grid grid-cols-2 gap-3 sm:grid-cols-4 text-center">
                    @foreach ([
                        ['Engagement Rate', number_format((float) $row->engagement_rate, 2) . '%', 'bg-emerald-50 text-emerald-700 dark:bg-emerald-950/50 dark:text-emerald-300'],
                        ['Total Engagements', number_format((int) $row->engagements), 'bg-rose-50 text-rose-700 dark:bg-rose-950/50 dark:text-rose-300'],
                        ['Avg Impressions', number_format((int) $row->impressions), 'bg-amber-50 text-amber-700 dark:bg-amber-950/50 dark:text-amber-300'],
                        ['Followers', number_format((int) $row->followers), 'bg-sky-50 text-sky-700 dark:bg-sky-950/50 dark:text-sky-300'],
                    ] as [$label, $value, $tone])
                        <div class="rounded-lg p-3 {{ $tone }}">
                            <div class="text-base font-bold">{{ $value }}</div>
                            <div class="text-xs opacity-80">{{ $label }}</div>
                        </div>
                    @endforeach
                </div>

                {{-- Eks-section "Additional Info". Semuanya hasil scraping, jadi dipindah
                     ke sini sebagai tampilan saja — tidak ada lagi form untuk mengetiknya
                     manual. Golongan Pajak pindah ke section Data Legal karena itu SATU-
                     SATUNYA field di sana yang memang diisi orang, bukan mesin. --}}
                <div class="mt-4">
                    <div class="mb-2 text-xs font-semibold uppercase tracking-wide text-gray-400">Additional Info</div>
                    <dl class="grid grid-cols-1 gap-x-6 gap-y-2 sm:grid-cols-2">
                        @foreach ([
                            ['Nama Lengkap PIC', $row->full_name],
                            ['Email', $row->email],
                            ['No WhatsApp', $row->wa_number],
                            ['Contact (legacy)', $row->contact],
                            ['Terakhir Update', $row->terakhir_update?->format('d M Y')],
                        ] as [$label, $value])
                            <div class="flex justify-between gap-3 border-b border-gray-100 py-1 dark:border-gray-800">
                                <dt class="shrink-0 text-xs text-gray-400">{{ $label }}</dt>
                                <dd class="break-all text-right text-xs">{{ filled($value) ? $value : '—' }}</dd>
                            </div>
                        @endforeach
                    </dl>
                </div>

                @if (filled($bio))
                    <div class="mt-4">
                        <div class="mb-2 text-xs font-semibold uppercase tracking-wide text-gray-400">Notes</div>
                        <pre class="whitespace-pre-wrap rounded bg-gray-50 p-3 text-xs text-gray-600 dark:bg-gray-800 dark:text-gray-300">{{ $bio }}</pre>
                    </div>
                @endif
            </div>
        </div>
        </template>
    @endforeach
</div>
