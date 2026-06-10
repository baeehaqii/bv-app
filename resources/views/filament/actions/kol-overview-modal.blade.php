@php
    $linkList = array_filter(array_map('trim', explode(',', $links)));
    $channelIcon = match ($channel) {
        'Instagram' => '📸',
        'Tiktok' => '🎵',
        'Youtube Channels', 'Youtube Shorts' => '▶️',
        'Facebook' => '📘',
        'Threads' => '🧵',
        'X' => '✖️',
        'Talent' => '🌟',
        default => '📡',
    };
@endphp

<div class="divide-y divide-gray-100 dark:divide-gray-800">

    {{-- Header --}}
    <div class="pb-5">
        <div class="flex items-start justify-between gap-3">
            <div>
                <p class="text-xs text-gray-400 dark:text-gray-500 mb-0.5">{{ $channelIcon }} {{ $channel }}</p>
                <h2 class="text-xl font-semibold text-gray-900 dark:text-white tracking-tight">{{ $name }}</h2>
                @if($domisili && $domisili !== '—')
                    <p class="text-sm text-gray-500 dark:text-gray-400 mt-0.5">📍 {{ $domisili }}</p>
                @endif
            </div>
            <div class="flex flex-col items-end gap-1.5 shrink-0">
                @if($is_selected === 'Ya ✅')
                    <span
                        class="inline-flex items-center rounded-full bg-green-50 dark:bg-green-900/30 px-2.5 py-0.5 text-xs font-medium text-green-700 dark:text-green-400 ring-1 ring-green-600/20">
                        Masuk Quotation
                    </span>
                @endif
                @if($status && $status !== '—')
                    <span
                        class="inline-flex items-center rounded-full bg-violet-50 dark:bg-violet-900/30 px-2.5 py-0.5 text-xs font-medium text-violet-700 dark:text-violet-400 ring-1 ring-violet-600/20">
                        {{ $status }}
                    </span>
                @endif
                @if($pic && $pic !== '—')
                    <span class="text-xs text-gray-400 dark:text-gray-500">PIC: {{ $pic }}</span>
                @endif
            </div>
        </div>

        {{-- Links --}}
        @if(count($linkList) > 0)
            <div class="mt-3 flex flex-wrap gap-2">
                @foreach($linkList as $link)
                    <a href="{{ $link }}" target="_blank" rel="noopener noreferrer"
                        class="inline-flex items-center gap-1.5 rounded-md bg-gray-100 dark:bg-gray-800 px-2.5 py-1 text-xs font-medium text-gray-700 dark:text-gray-300 hover:bg-violet-100 dark:hover:bg-violet-900/40 hover:text-violet-700 dark:hover:text-violet-300 transition-colors max-w-xs truncate">
                        <svg class="w-3 h-3 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1" />
                        </svg>
                        {{ $link }}
                    </a>
                @endforeach
            </div>
        @endif
    </div>

    {{-- Performance --}}
    <div class="py-5">
        <p class="text-[11px] font-semibold text-gray-400 dark:text-gray-500 uppercase tracking-widest mb-3">Performance
        </p>
        <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-5 gap-x-4 gap-y-4">
            <div>
                <p class="text-xs text-gray-400 dark:text-gray-500">Followers</p>
                <p class="mt-0.5 text-sm font-semibold text-gray-800 dark:text-gray-200">{{ $followers }}</p>
            </div>
            <div>
                <p class="text-xs text-gray-400 dark:text-gray-500">Tier</p>
                <p class="mt-0.5 text-sm font-semibold text-gray-800 dark:text-gray-200">{{ $tier }}</p>
            </div>
            <div>
                <p class="text-xs text-gray-400 dark:text-gray-500">ER%</p>
                <p class="mt-0.5 text-sm font-semibold text-gray-800 dark:text-gray-200">{{ $er_percent }}</p>
            </div>
            <div>
                <p class="text-xs text-gray-400 dark:text-gray-500">Impression</p>
                <p class="mt-0.5 text-sm font-semibold text-gray-800 dark:text-gray-200">{{ $impression }}</p>
            </div>
            <div>
                <p class="text-xs text-gray-400 dark:text-gray-500">Engagement</p>
                <p class="mt-0.5 text-sm font-semibold text-gray-800 dark:text-gray-200">{{ $engagement }}</p>
            </div>
            <div>
                <p class="text-xs text-gray-400 dark:text-gray-500">CPI / CPV</p>
                <p class="mt-0.5 text-sm font-semibold text-gray-800 dark:text-gray-200">{{ $cpi_cpv }}</p>
            </div>
            <div>
                <p class="text-xs text-gray-400 dark:text-gray-500">CPE</p>
                <p class="mt-0.5 text-sm font-semibold text-gray-800 dark:text-gray-200">{{ $cpe }}</p>
            </div>
            <div class="col-span-2">
                <p class="text-xs text-gray-400 dark:text-gray-500">Scope of Work</p>
                <p class="mt-0.5 text-sm font-semibold text-gray-800 dark:text-gray-200">{{ $scope_items ?: '—' }}</p>
            </div>
        </div>
    </div>

    {{-- Info KOL dari Database --}}
    <div class="py-5">
        <p class="text-[11px] font-semibold text-gray-400 dark:text-gray-500 uppercase tracking-widest mb-3">Info KOL
        </p>
        <div class="grid grid-cols-2 sm:grid-cols-4 gap-x-4 gap-y-4">
            <div>
                <p class="text-xs text-gray-400 dark:text-gray-500">Nama PIC KOL</p>
                <p class="mt-0.5 text-sm font-semibold text-gray-800 dark:text-gray-200">{{ $kol_pic_name }}</p>
            </div>
            <div>
                <p class="text-xs text-gray-400 dark:text-gray-500">Email</p>
                <p class="mt-0.5 text-sm font-semibold text-gray-800 dark:text-gray-200">{{ $kol_email }}</p>
            </div>
            <div>
                <p class="text-xs text-gray-400 dark:text-gray-500">No WhatsApp</p>
                <p class="mt-0.5 text-sm font-semibold text-gray-800 dark:text-gray-200">{{ $kol_wa }}</p>
            </div>
            <div>
                <p class="text-xs text-gray-400 dark:text-gray-500">Category</p>
                <p class="mt-0.5 text-sm font-semibold text-gray-800 dark:text-gray-200">{{ $kol_category }}</p>
            </div>
        </div>
    </div>

    {{-- Pembayaran + Pajak --}}
    <div class="py-5">
        <p class="text-[11px] font-semibold text-gray-400 dark:text-gray-500 uppercase tracking-widest mb-3">Pembayaran
        </p>
        <div class="grid grid-cols-3 gap-4">
            <div>
                <p class="text-xs text-gray-400 dark:text-gray-500">Jadwal Payment</p>
                <p class="mt-0.5 text-sm font-semibold text-gray-800 dark:text-gray-200">{{ $payment_date }}</p>
            </div>
            <div>
                <p class="text-xs text-gray-400 dark:text-gray-500">Golongan Pajak</p>
                <p class="mt-0.5 text-sm font-semibold text-gray-800 dark:text-gray-200">{{ $tipe_pajak_kol }}</p>
            </div>
        </div>
    </div>

    {{-- SOW / Budget Items untuk KOL ini --}}
    @if(isset($budget_items) && $budget_items->isNotEmpty())
        <div class="py-5">
            <p class="text-[11px] font-semibold text-gray-400 dark:text-gray-500 uppercase tracking-widest mb-3">Scope of
                Work & Budget</p>
            <div class="rounded-xl border border-gray-200 dark:border-gray-700 overflow-hidden">
                <table class="min-w-full text-sm">
                    <thead>
                        <tr class="bg-gray-50 dark:bg-gray-800/60 border-b border-gray-200 dark:border-gray-700">
                            <th class="px-4 py-2.5 text-left text-xs font-semibold text-gray-500 dark:text-gray-400">Scope
                                Item</th>
                            <th class="px-4 py-2.5 text-left text-xs font-semibold text-gray-500 dark:text-gray-400">Qty
                            </th>
                            <th class="px-4 py-2.5 text-left text-xs font-semibold text-gray-500 dark:text-gray-400">Rate
                                (Base)</th>
                            <th class="px-4 py-2.5 text-left text-xs font-semibold text-gray-500 dark:text-gray-400">🔴 Cost
                            </th>
                            <th class="px-4 py-2.5 text-left text-xs font-semibold text-gray-500 dark:text-gray-400">🟢
                                Client Price</th>
                            <th class="px-4 py-2.5 text-left text-xs font-semibold text-gray-500 dark:text-gray-400">Margin
                            </th>
                            <th class="px-4 py-2.5 text-left text-xs font-semibold text-gray-500 dark:text-gray-400">Status
                                Approval</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                        @foreach($budget_items as $bi)
                            <tr class="hover:bg-gray-50/70 dark:hover:bg-gray-800/40 transition-colors">
                                <td class="px-4 py-3 font-medium text-gray-800 dark:text-gray-200 text-sm">
                                    {{ $bi['scope_item'] ?? '—' }}</td>
                                <td class="px-4 py-3 text-gray-600 dark:text-gray-400 text-sm">{{ $bi['qty'] ?? 1 }}</td>
                                <td class="px-4 py-3 text-gray-700 dark:text-gray-300 text-sm font-mono">
                                    @php $rateBase = (float) str_replace(',', '', (string) ($bi['rate_base'] ?? '')); @endphp
                                    {{ $rateBase ? 'Rp ' . number_format($rateBase, 0, ',', '.') : '—' }}
                                </td>
                                <td class="px-4 py-3 text-red-600 dark:text-red-400 text-sm font-mono">
                                    @php $muPph = (float) str_replace(',', '', (string) ($bi['mu_pph'] ?? '')); @endphp
                                    {{ $muPph ? 'Rp ' . number_format($muPph, 0, ',', '.') : '—' }}
                                </td>
                                <td class="px-4 py-3 text-green-700 dark:text-green-400 text-sm font-mono">
                                    @php $rounded = (float) str_replace(',', '', (string) ($bi['rounded'] ?? '')); @endphp
                                    {{ $rounded ? 'Rp ' . number_format($rounded, 0, ',', '.') : '—' }}
                                </td>
                                <td class="px-4 py-3 text-gray-500 dark:text-gray-400 text-xs">
                                    {{ isset($bi['actual_margin_percent']) && $bi['actual_margin_percent'] ? $bi['actual_margin_percent'] . '%' : '—' }}
                                </td>
                                <td class="px-4 py-3">
                                    @php $biStatus = $bi['status'] ?? 'pending'; @endphp
                                    @if($biStatus === 'approved')
                                        <span
                                            class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-xs font-semibold bg-green-100 text-green-700 dark:bg-green-900/40 dark:text-green-300">
                                            ✅ Approved
                                        </span>
                                    @elseif($biStatus === 'rejected')
                                        <span
                                            class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-xs font-semibold bg-red-100 text-red-700 dark:bg-red-900/40 dark:text-red-300">
                                            ❌ Rejected
                                        </span>
                                    @else
                                        <span
                                            class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-xs font-semibold bg-yellow-100 text-yellow-700 dark:bg-yellow-900/40 dark:text-yellow-300">
                                            ⏳ Pending
                                        </span>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @endif

    {{-- Rate Card dari Database KOL --}}
    @if($rate_cards->isNotEmpty())
        <div class="py-5">
            <p class="text-[11px] font-semibold text-gray-400 dark:text-gray-500 uppercase tracking-widest mb-3">Rate Card
                per SOW</p>
            <div class="rounded-xl border border-gray-200 dark:border-gray-700 overflow-hidden">
                <table class="min-w-full text-sm">
                    <thead>
                        <tr class="bg-gray-50 dark:bg-gray-800/60 border-b border-gray-200 dark:border-gray-700">
                            <th class="px-4 py-2.5 text-left text-xs font-semibold text-gray-500 dark:text-gray-400">Channel
                            </th>
                            <th class="px-4 py-2.5 text-left text-xs font-semibold text-gray-500 dark:text-gray-400">SOW
                            </th>
                            <th class="px-4 py-2.5 text-left text-xs font-semibold text-gray-500 dark:text-gray-400">Rate
                            </th>
                            <th class="px-4 py-2.5 text-left text-xs font-semibold text-gray-500 dark:text-gray-400">Berlaku
                                Dari</th>
                            <th class="px-4 py-2.5 text-left text-xs font-semibold text-gray-500 dark:text-gray-400">Catatan
                            </th>
                            <th class="px-4 py-2.5 text-left text-xs font-semibold text-gray-500 dark:text-gray-400">File
                            </th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                        @foreach($rate_cards as $rc)
                            <tr class="hover:bg-gray-50/70 dark:hover:bg-gray-800/40 transition-colors">
                                <td class="px-4 py-3 font-medium text-gray-800 dark:text-gray-200 text-sm">
                                    {{ $rc->channel ?? '—' }}
                                </td>
                                <td class="px-4 py-3 text-gray-700 dark:text-gray-300 text-sm">
                                    {{ $rc->sow_label }}
                                </td>
                                <td class="px-4 py-3 text-gray-700 dark:text-gray-300 text-sm font-mono">
                                    {{ $rc->rate ? 'Rp ' . number_format((float) $rc->rate, 0, ',', '.') : '—' }}
                                </td>
                                <td class="px-4 py-3 text-gray-500 dark:text-gray-400 text-xs">
                                    {{ $rc->valid_from ? \Carbon\Carbon::parse($rc->valid_from)->translatedFormat('d M Y') : '—' }}
                                </td>
                                <td class="px-4 py-3 text-gray-500 dark:text-gray-400 text-xs max-w-[180px]">
                                    {{ $rc->notes ?: '—' }}
                                </td>
                                <td class="px-4 py-3">
                                    @if($rc->file_path)
                                        <a href="{{ \Illuminate\Support\Facades\Storage::url($rc->file_path) }}" target="_blank"
                                            rel="noopener noreferrer"
                                            class="inline-flex items-center gap-1 text-violet-600 hover:text-violet-800 dark:text-violet-400 dark:hover:text-violet-200 text-xs font-medium transition-colors">
                                            <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"
                                                stroke-width="2">
                                                <path stroke-linecap="round" stroke-linejoin="round"
                                                    d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13" />
                                            </svg>
                                            Lihat
                                        </a>
                                    @else
                                        <span class="text-gray-300 dark:text-gray-600 text-xs">—</span>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @endif

</div>