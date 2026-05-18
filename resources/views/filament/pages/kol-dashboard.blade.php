<x-filament-panels::page>
    @php
        $colorMap = [
            'primary' => ['bg' => 'bg-primary-50 dark:bg-primary-500/10', 'text' => 'text-primary-600 dark:text-primary-400', 'soft' => 'bg-primary-100 dark:bg-primary-500/20'],
            'success' => ['bg' => 'bg-emerald-50 dark:bg-emerald-500/10', 'text' => 'text-emerald-600 dark:text-emerald-400', 'soft' => 'bg-emerald-100 dark:bg-emerald-500/20'],
            'warning' => ['bg' => 'bg-amber-50 dark:bg-amber-500/10',     'text' => 'text-amber-600 dark:text-amber-400',     'soft' => 'bg-amber-100 dark:bg-amber-500/20'],
            'danger'  => ['bg' => 'bg-rose-50 dark:bg-rose-500/10',       'text' => 'text-rose-600 dark:text-rose-400',       'soft' => 'bg-rose-100 dark:bg-rose-500/20'],
        ];

        $stats        = $this->getQuickStats();
        $campaigns    = $this->getAssignedCampaigns();
        $mediaPlans   = $this->getAssignedMediaPlans();
        $activity     = $this->getRecentActivity();

        $platformIcons = [
            'instagram' => '📸',
            'tiktok'    => '🎵',
            'youtube'   => '▶️',
            'threads'   => '🧵',
        ];
    @endphp

    <div class="grid grid-cols-12 gap-4">

        {{-- ─── ROW 1: Greeting + Quick Stats ─── --}}
        <div class="col-span-12 lg:col-span-7">
            <div class="rounded-2xl bg-white dark:bg-gray-900 p-5 shadow-sm border border-gray-100 dark:border-gray-800 h-full">
                <div class="flex items-start justify-between gap-3">
                    <div>
                        <p class="text-sm text-gray-500 dark:text-gray-400">{{ $this->getCurrentDateLabel() }}</p>
                    </div>
                    <span class="inline-flex items-center gap-1.5 rounded-full bg-emerald-50 dark:bg-emerald-500/10 px-3 py-1 text-xs font-medium text-emerald-700 dark:text-emerald-300">
                        <span class="text-base leading-none">🎯</span> KOL Dashboard
                    </span>
                </div>

                <div class="mt-4 flex items-center gap-4">
                    @if (auth()->user()->avatar_url)
                        <img src="{{ auth()->user()->avatar_url }}" alt="" class="h-16 w-16 rounded-full object-cover">
                    @else
                        <div class="h-16 w-16 rounded-full bg-emerald-100 dark:bg-emerald-500/20 flex items-center justify-center text-xl font-semibold text-emerald-700 dark:text-emerald-300">
                            {{ \Illuminate\Support\Str::substr($this->getDisplayName(), 0, 1) }}
                        </div>
                    @endif
                    <div>
                        <h2 class="text-2xl font-bold text-gray-900 dark:text-gray-100">
                            Hi, {{ $this->getDisplayName() }}! <span class="text-2xl">👋</span>
                        </h2>
                        <p class="text-sm text-gray-500 dark:text-gray-400 mt-0.5">Berikut campaign yang di-assign ke kamu.</p>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-span-12 lg:col-span-5 grid grid-cols-2 gap-3">
            @foreach ($stats as $stat)
                @php $c = $colorMap[$stat['color']] ?? $colorMap['primary']; @endphp
                <div class="rounded-2xl {{ $c['bg'] }} border border-transparent p-4 flex items-center justify-between">
                    <div class="flex items-center gap-2.5">
                        <span class="flex h-8 w-8 items-center justify-center rounded-lg {{ $c['soft'] }}">
                            <x-filament::icon :icon="$stat['icon']" class="h-4 w-4 {{ $c['text'] }}" />
                        </span>
                        <span class="text-sm font-semibold {{ $c['text'] }}">{{ $stat['label'] }}</span>
                    </div>
                    <span class="text-2xl font-bold {{ $c['text'] }}">{{ $stat['value'] }}</span>
                </div>
            @endforeach
        </div>

        {{-- ─── ROW 2: Campaign Assigned (kiri) + Aktivitas Terbaru (kanan) ─── --}}
        <div class="col-span-12 lg:col-span-8">
            <div class="rounded-2xl bg-white dark:bg-gray-900 p-5 shadow-sm border border-gray-100 dark:border-gray-800 h-full">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-base font-semibold text-gray-900 dark:text-gray-100 inline-flex items-center gap-1.5">
                        <x-heroicon-m-megaphone class="h-4 w-4 text-emerald-500" /> Campaign Saya
                    </h3>
                    <a href="{{ url('/office/bv-campigns') }}" class="text-xs font-medium text-primary-600 hover:underline dark:text-primary-400">Lihat semua</a>
                </div>

                <div class="space-y-3 max-h-80 overflow-y-auto pr-1">
                    @forelse ($campaigns as $campaign)
                        <div class="rounded-xl border border-gray-100 dark:border-gray-800 p-3.5 hover:bg-gray-50 dark:hover:bg-gray-800/50 transition-colors">
                            <div class="flex items-start justify-between gap-3">
                                <div class="min-w-0 flex-1">
                                    <div class="flex items-center gap-2 flex-wrap">
                                        <span class="text-sm font-semibold text-gray-900 dark:text-gray-100 truncate">
                                            {{ $campaign['name'] }}
                                        </span>
                                        <span class="shrink-0 inline-block rounded-full px-2 py-0.5 text-[10px] font-semibold"
                                              style="background:{{ $campaign['status_bg'] }};color:{{ $campaign['status_text'] }};">
                                            {{ $campaign['status_label'] }}
                                        </span>
                                    </div>
                                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">{{ $campaign['client'] }}</p>
                                </div>

                                {{-- Platform icons --}}
                                <div class="shrink-0 flex items-center gap-1">
                                    @foreach (array_slice($campaign['platforms'], 0, 3) as $platform)
                                        <span class="text-sm" title="{{ $platform }}">
                                            {{ $platformIcons[$platform] ?? '🌐' }}
                                        </span>
                                    @endforeach
                                </div>
                            </div>

                            {{-- Progress bar --}}
                            @if ($campaign['start_date'] && $campaign['end_date'])
                                <div class="mt-3">
                                    <div class="flex justify-between items-baseline mb-1">
                                        <span class="text-xs text-gray-500 dark:text-gray-400">
                                            {{ $campaign['start_date'] }} — {{ $campaign['end_date'] }}
                                        </span>
                                        <span class="text-xs font-semibold text-gray-700 dark:text-gray-300">{{ $campaign['progress'] }}%</span>
                                    </div>
                                    <div class="h-1.5 w-full rounded-full bg-gray-100 dark:bg-gray-800 overflow-hidden">
                                        <div class="h-full rounded-full bg-emerald-500 transition-all"
                                             style="width: {{ $campaign['progress'] }}%"></div>
                                    </div>
                                </div>
                            @endif

                            {{-- KOL progress --}}
                            <div class="mt-2 flex items-center gap-3">
                                <span class="text-xs text-gray-500 dark:text-gray-400">
                                    KOL: <span class="font-semibold text-gray-700 dark:text-gray-300">{{ $campaign['kol_posted'] }}/{{ $campaign['kol_count'] }}</span> posted
                                </span>
                                @if ($campaign['kol_count'] > 0)
                                    <div class="flex-1 h-1 rounded-full bg-gray-100 dark:bg-gray-800 overflow-hidden">
                                        @php $kolPct = $campaign['kol_count'] > 0 ? round(($campaign['kol_posted'] / $campaign['kol_count']) * 100) : 0; @endphp
                                        <div class="h-full rounded-full bg-blue-400 transition-all" style="width: {{ $kolPct }}%"></div>
                                    </div>
                                @endif
                            </div>
                        </div>
                    @empty
                        <div class="flex flex-col items-center justify-center py-12 text-center">
                            <x-heroicon-o-megaphone class="h-10 w-10 text-gray-300 dark:text-gray-600 mb-3" />
                            <p class="text-sm text-gray-400 dark:text-gray-500">Belum ada campaign yang di-assign.</p>
                        </div>
                    @endforelse
                </div>
            </div>
        </div>

        {{-- Aktivitas Terbaru --}}
        <div class="col-span-12 lg:col-span-4">
            <div class="rounded-2xl bg-white dark:bg-gray-900 p-5 shadow-sm border border-gray-100 dark:border-gray-800 h-full">
                <h3 class="text-base font-semibold text-gray-900 dark:text-gray-100 inline-flex items-center gap-1.5 mb-4">
                    <x-heroicon-m-bell class="h-4 w-4 text-amber-500" /> Aktivitas Terbaru
                </h3>

                <div class="space-y-3">
                    @forelse ($activity as $item)
                        <div class="flex items-start gap-3">
                            <span class="mt-0.5 h-2 w-2 rounded-full shrink-0 {{ $item['is_new'] ? 'bg-emerald-500' : 'bg-gray-300 dark:bg-gray-600' }}"></span>
                            <div class="min-w-0 flex-1">
                                <p class="text-sm text-gray-800 dark:text-gray-200 leading-snug">
                                    <span class="font-medium">{{ $item['name'] }}</span>
                                    <span class="text-gray-500 dark:text-gray-400"> → {{ $item['status'] }}</span>
                                </p>
                                <p class="text-xs text-gray-400 dark:text-gray-500 mt-0.5">{{ $item['time'] }}</p>
                            </div>
                        </div>
                    @empty
                        <p class="text-sm text-gray-400 text-center py-6">Belum ada aktivitas.</p>
                    @endforelse
                </div>
            </div>
        </div>

        {{-- ─── ROW 3: Media Plan Internal ─── --}}
        <div class="col-span-12">
            <div class="rounded-2xl bg-white dark:bg-gray-900 p-5 shadow-sm border border-gray-100 dark:border-gray-800">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-base font-semibold text-gray-900 dark:text-gray-100 inline-flex items-center gap-1.5">
                        <x-heroicon-m-document-text class="h-4 w-4 text-primary-500" /> Media Plan Internal Saya
                    </h3>
                    <a href="{{ url('/office/media-plans') }}" class="text-xs font-medium text-primary-600 hover:underline dark:text-primary-400">Lihat semua</a>
                </div>

                @if (count($mediaPlans) > 0)
                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3">
                        @foreach ($mediaPlans as $plan)
                            <div class="rounded-xl border border-gray-100 dark:border-gray-800 p-4 hover:bg-gray-50 dark:hover:bg-gray-800/50 transition-colors">
                                <div class="flex items-center justify-between gap-2 mb-2">
                                    <span class="text-sm font-semibold text-gray-900 dark:text-gray-100 truncate">{{ $plan['brand'] }}</span>
                                    <span class="shrink-0 inline-block rounded-full px-2 py-0.5 text-[10px] font-semibold"
                                          style="background:{{ $plan['status_bg'] }};color:{{ $plan['status_text'] }};">
                                        {{ $plan['status'] }}
                                    </span>
                                </div>

                                @if ($plan['period_start'])
                                    <p class="text-xs text-gray-500 dark:text-gray-400">
                                        {{ $plan['period_start'] }}
                                        @if ($plan['period_end']) — {{ $plan['period_end'] }} @endif
                                    </p>
                                @endif

                                <div class="mt-3 flex items-center justify-between">
                                    <span class="text-xs text-gray-500 dark:text-gray-400">
                                        KOL: <span class="font-semibold text-gray-700 dark:text-gray-300">{{ $plan['selected_kols'] }}</span> dipilih dari <span class="font-semibold">{{ $plan['total_kols'] }}</span>
                                    </span>
                                    <a href="{{ url('/office/media-plans/' . $plan['id'] . '/edit') }}"
                                       class="text-xs text-primary-600 hover:underline dark:text-primary-400 font-medium">
                                        Buka →
                                    </a>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @else
                    <div class="flex flex-col items-center justify-center py-10 text-center">
                        <x-heroicon-o-document-text class="h-10 w-10 text-gray-300 dark:text-gray-600 mb-3" />
                        <p class="text-sm text-gray-400 dark:text-gray-500">Belum ada media plan yang di-assign.</p>
                    </div>
                @endif
            </div>
        </div>

    </div>
</x-filament-panels::page>
