<x-filament-panels::page>
    @php
        $colorMap = [
            'primary' => ['bg' => 'bg-primary-50 dark:bg-primary-500/10', 'text' => 'text-primary-600 dark:text-primary-400', 'soft' => 'bg-primary-100 dark:bg-primary-500/20'],
            'danger' => ['bg' => 'bg-rose-50 dark:bg-rose-500/10', 'text' => 'text-rose-600 dark:text-rose-400', 'soft' => 'bg-rose-100 dark:bg-rose-500/20'],
            'warning' => ['bg' => 'bg-amber-50 dark:bg-amber-500/10', 'text' => 'text-amber-600 dark:text-amber-400', 'soft' => 'bg-amber-100 dark:bg-amber-500/20'],
            'success' => ['bg' => 'bg-emerald-50 dark:bg-emerald-500/10', 'text' => 'text-emerald-600 dark:text-emerald-400', 'soft' => 'bg-emerald-100 dark:bg-emerald-500/20'],
        ];
        $stats = $this->getQuickStats();
        $myTarget = $this->getMyTarget();
        $clientCategoryStats = $this->getClientCategoryStats();
        $activePipeline = $this->getActivePipelineForCard();
        $todos = $this->getMyTodos();
        $notifications = $this->getNotifications();
        $clients = $this->getMyClients();
        $pipelineStatusColors = [
            'not_started'      => 'bg-gray-100 dark:bg-gray-800 text-gray-600 dark:text-gray-400',
            'pitching'         => 'bg-blue-100 dark:bg-blue-500/20 text-blue-700 dark:text-blue-300',
            'briefing'         => 'bg-cyan-100 dark:bg-cyan-500/20 text-cyan-700 dark:text-cyan-300',
            'proposal_building'=> 'bg-amber-100 dark:bg-amber-500/20 text-amber-700 dark:text-amber-300',
            'negotiation'      => 'bg-orange-100 dark:bg-orange-500/20 text-orange-700 dark:text-orange-300',
            'campaign_live'    => 'bg-emerald-100 dark:bg-emerald-500/20 text-emerald-700 dark:text-emerald-300',
            'reporting'        => 'bg-teal-100 dark:bg-teal-500/20 text-teal-700 dark:text-teal-300',
            'invoicing'        => 'bg-violet-100 dark:bg-violet-500/20 text-violet-700 dark:text-violet-300',
        ];
    @endphp

    @if ($this->isExecutiveViewer())
        <div class="mb-4 rounded-2xl bg-white dark:bg-gray-900 p-4 shadow-sm border border-gray-100 dark:border-gray-800">
            <div class="flex flex-col sm:flex-row sm:items-center gap-3">
                <div class="flex items-center gap-2 text-sm font-medium text-gray-700 dark:text-gray-200">
                    <x-filament::icon icon="heroicon-o-eye" class="h-5 w-5 text-primary-500" />
                    Monitoring Sales
                </div>
                <select
                    wire:model.live="selectedSalesId"
                    class="w-full sm:w-72 rounded-lg border-gray-300 dark:border-gray-700 dark:bg-gray-800 text-sm text-gray-900 dark:text-gray-100 focus:border-primary-500 focus:ring-primary-500"
                >
                    @foreach ($this->getSalesOptions() as $id => $name)
                        <option value="{{ $id }}" @selected($this->selectedSalesId === $id)>{{ $name }}</option>
                    @endforeach
                </select>
            </div>
        </div>
    @endif

    <div class="grid grid-cols-12 gap-4">
        {{-- ────────────── ROW 1: Greeting + Quick Stats ────────────── --}}
        <div class="col-span-12 lg:col-span-7">
            <div class="rounded-2xl bg-white dark:bg-gray-900 p-5 shadow-sm border border-gray-100 dark:border-gray-800 h-full">
                <div class="flex items-start justify-between gap-3">
                    <div>
                        <p class="text-sm text-gray-500 dark:text-gray-400">Good Evening</p>
                        <p class="text-xs text-gray-400 dark:text-gray-500 mt-0.5">{{ $this->getCurrentDateLabel() }}</p>
                    </div>
                    <span class="inline-flex items-center gap-1.5 rounded-full bg-primary-50 dark:bg-primary-500/10 px-3 py-1 text-xs font-medium text-primary-700 dark:text-primary-300">
                        <span class="text-base leading-none">😊</span> Mood Today!
                    </span>
                </div>

                <div class="mt-4 flex items-center gap-4">
                    @if (auth()->user()->avatar_url)
                        <img src="{{ auth()->user()->avatar_url }}" alt="" class="h-16 w-16 rounded-full object-cover">
                    @else
                        <div class="h-16 w-16 rounded-full bg-primary-100 dark:bg-primary-500/20 flex items-center justify-center text-xl font-semibold text-primary-700 dark:text-primary-300">
                            {{ \Illuminate\Support\Str::substr($this->getDisplayName(), 0, 1) }}
                        </div>
                    @endif
                    <div>
                        <h2 class="text-2xl font-bold text-gray-900 dark:text-gray-100">
                            Hi, {{ $this->getDisplayName() }}! <span class="text-2xl">👋</span>
                        </h2>
                        <p class="text-sm text-gray-500 dark:text-gray-400 mt-0.5">Apa yang sedang dikerjakan hari ini?</p>
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

        {{-- ────────────── ROW 2: Progress Team / My Progress / My Team ────────────── --}}
        <div class="col-span-12 lg:col-span-3">
            <div class="rounded-2xl bg-white dark:bg-gray-900 p-5 shadow-sm border border-gray-100 dark:border-gray-800 h-full">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-base font-semibold text-gray-900 dark:text-gray-100">Target Saya</h3>
                    <a href="{{ url('/office/target-per-sales') }}" class="text-xs font-medium text-primary-600 hover:underline dark:text-primary-400">Atur</a>
                </div>

                <div class="space-y-4">
                    @foreach ([['key' => 'month', 'label' => 'Target Bulan Ini'], ['key' => 'year', 'label' => 'Target Tahun Ini']] as $baris)
                        @php $t = $myTarget[$baris['key']]; @endphp
                        <div>
                            <div class="flex justify-between items-baseline">
                                <p class="text-sm text-gray-600 dark:text-gray-400">{{ $baris['label'] }}</p>
                                @if ($t['has_target'])
                                    <span class="text-lg font-bold text-gray-900 dark:text-gray-100">{{ $t['percent'] }}%</span>
                                @else
                                    <span class="text-xs font-medium text-gray-400">belum diatur</span>
                                @endif
                            </div>

                            @if ($t['has_target'])
                                <div class="mt-2 h-2 w-full rounded-full bg-gray-100 dark:bg-gray-800 overflow-hidden">
                                    <div class="h-full rounded-full bg-emerald-500 transition-all" style="width: {{ $t['percent'] }}%"></div>
                                </div>
                                <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                                    Rp {{ number_format($t['achieved'], 0, ',', '.') }}
                                    / Rp {{ number_format($t['target'], 0, ',', '.') }}
                                </p>
                            @else
                                {{-- "Rp 0 / Rp 0" terbaca seperti gagal ambil data; sebutkan apa adanya. --}}
                                <p class="mt-2 text-xs text-gray-400 dark:text-gray-500">
                                    Belum ada target untuk {{ $this->getDisplayName() }}.
                                    <a href="{{ url('/office/target-per-sales') }}" class="text-primary-600 hover:underline dark:text-primary-400">Atur di Sales Targets</a>
                                </p>
                                @if ($t['achieved'] > 0)
                                    <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Tercapai Rp {{ number_format($t['achieved'], 0, ',', '.') }}</p>
                                @endif
                            @endif
                        </div>
                    @endforeach
                </div>
            </div>
        </div>

        <div class="col-span-12 lg:col-span-3">
            <div class="rounded-2xl bg-white dark:bg-gray-900 p-5 shadow-sm border border-gray-100 dark:border-gray-800 h-full flex flex-col">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-base font-semibold text-gray-900 dark:text-gray-100">Kategori Client</h3>
                    <a href="{{ url('/office/data-client') }}" class="text-xs font-medium text-primary-600 hover:underline dark:text-primary-400">Semua</a>
                </div>

                @if (count($clientCategoryStats) > 0)
                    {{-- Vertical bar chart --}}
                    <div class="flex-1 flex flex-col justify-end">
                        {{-- Chart area: fixed 96px tall, bars pinned to bottom --}}
                        <div class="relative flex items-end justify-around gap-2" style="height: 96px;">
                            @foreach ($clientCategoryStats as $stat)
                                <div class="flex flex-col items-center gap-1 flex-1">
                                    <span class="text-xs font-bold text-gray-700 dark:text-gray-300">{{ $stat['total'] }}</span>
                                    <div class="w-full rounded-t-lg {{ $stat['color'] }} transition-all duration-500"
                                         style="height: {{ $stat['height_px'] }}px"></div>
                                </div>
                            @endforeach
                        </div>
                        {{-- X-axis line --}}
                        <div class="h-px bg-gray-200 dark:bg-gray-700 mt-0"></div>
                        {{-- Labels --}}
                        <div class="flex justify-around gap-2 mt-2">
                            @foreach ($clientCategoryStats as $stat)
                                <div class="flex-1 text-center">
                                    <p class="text-[10px] text-gray-500 dark:text-gray-400 leading-tight truncate" title="{{ $stat['category'] }}">
                                        {{ $stat['category'] }}
                                    </p>
                                </div>
                            @endforeach
                        </div>
                    </div>

                    {{-- Legend dots --}}
                    <div class="mt-4 space-y-1.5">
                        @foreach ($clientCategoryStats as $stat)
                            <div class="flex items-center gap-2">
                                <span class="h-2 w-2 rounded-full {{ $stat['color'] }} shrink-0"></span>
                                <span class="text-xs text-gray-600 dark:text-gray-400 truncate flex-1">{{ $stat['category'] }}</span>
                                <span class="text-xs font-semibold text-gray-800 dark:text-gray-200 shrink-0">{{ $stat['total'] }}</span>
                            </div>
                        @endforeach
                    </div>
                @else
                    <div class="flex-1 flex items-center justify-center">
                        <p class="text-sm text-gray-400 text-center">Belum ada data client.</p>
                    </div>
                @endif
            </div>
        </div>

        {{-- Pipeline Aktif Card + Progress Modal --}}
        <div class="col-span-12 lg:col-span-6"
             x-data="{ open: false, deal: null }"
             @keydown.escape.window="open = false">

            <div class="rounded-2xl bg-white dark:bg-gray-900 p-5 shadow-sm border border-gray-100 dark:border-gray-800 h-full">
                <div class="flex items-center justify-between mb-3">
                    <h3 class="text-base font-semibold text-gray-900 dark:text-gray-100 inline-flex items-center gap-1.5">
                        <x-heroicon-m-rocket-launch class="h-4 w-4 text-primary-500" /> Pipeline Aktif
                    </h3>
                    <a href="{{ url('/office/sales-activity') }}" class="text-xs font-medium text-primary-600 hover:underline dark:text-primary-400">Lihat semua</a>
                </div>

                <div class="space-y-2 max-h-72 overflow-y-auto pr-1">
                    @forelse ($activePipeline as $deal)
                        <div class="flex items-center gap-3 rounded-xl px-3 py-2.5 hover:bg-gray-50 dark:hover:bg-gray-800/60 transition-colors">
                            {{-- Status dot --}}
                            @php
                                $dotColor = match($deal['status']) {
                                    'campaign_live' => 'bg-emerald-500',
                                    'negotiation'   => 'bg-blue-500',
                                    'proposal_building' => 'bg-amber-500',
                                    'reporting'     => 'bg-teal-500',
                                    'invoicing'     => 'bg-violet-500',
                                    default         => 'bg-gray-400',
                                };
                            @endphp
                            <span class="h-2.5 w-2.5 rounded-full {{ $dotColor }} shrink-0"></span>

                            <div class="min-w-0 flex-1">
                                <p class="text-sm font-medium text-gray-900 dark:text-gray-100 truncate">{{ $deal['title'] }}</p>
                                <p class="text-xs text-gray-500 dark:text-gray-400 truncate">
                                    {{ $deal['company'] }}
                                    @if ($deal['close_date'])
                                        <span class="mx-1 text-gray-300">·</span>
                                        <span class="{{ $deal['is_overdue'] ? 'text-rose-500 font-medium' : '' }}">{{ $deal['close_date'] }}</span>
                                    @endif
                                </p>
                            </div>

                            <button type="button"
                                    @click="deal = {{ json_encode($deal) }}; open = true"
                                    class="shrink-0 inline-flex items-center gap-1 rounded-full bg-primary-50 dark:bg-primary-500/10 px-2.5 py-1 text-[11px] font-medium text-primary-700 dark:text-primary-300 hover:bg-primary-100 dark:hover:bg-primary-500/20 transition-colors">
                                <x-heroicon-m-chart-bar class="h-3 w-3" /> Progres
                            </button>
                        </div>
                    @empty
                        <p class="text-sm text-gray-400 text-center py-6">Belum ada pipeline aktif.</p>
                    @endforelse
                </div>
            </div>

            {{-- Progress Modal --}}
            <div x-show="open"
                 x-transition:enter="transition ease-out duration-200"
                 x-transition:enter-start="opacity-0"
                 x-transition:enter-end="opacity-100"
                 x-transition:leave="transition ease-in duration-150"
                 x-transition:leave-start="opacity-100"
                 x-transition:leave-end="opacity-0"
                 class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/50 backdrop-blur-sm"
                 @click.self="open = false"
                 style="display: none;">

                <div x-show="open"
                     x-transition:enter="transition ease-out duration-200"
                     x-transition:enter-start="opacity-0 scale-95"
                     x-transition:enter-end="opacity-100 scale-100"
                     x-transition:leave="transition ease-in duration-150"
                     x-transition:leave-start="opacity-100 scale-100"
                     x-transition:leave-end="opacity-0 scale-95"
                     class="relative w-full max-w-md rounded-2xl bg-white dark:bg-gray-900 shadow-xl border border-gray-100 dark:border-gray-800 p-6">

                    {{-- Header --}}
                    <div class="flex items-start justify-between mb-5">
                        <div class="min-w-0 flex-1 pr-4">
                            <p class="text-xs text-gray-500 dark:text-gray-400 mb-0.5">Progress Deal</p>
                            <h4 class="text-base font-semibold text-gray-900 dark:text-gray-100 truncate" x-text="deal?.title"></h4>
                            <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5" x-text="deal?.company"></p>
                        </div>
                        <button @click="open = false" class="shrink-0 rounded-full p-1 hover:bg-gray-100 dark:hover:bg-gray-800">
                            <x-heroicon-m-x-mark class="h-4 w-4 text-gray-500" />
                        </button>
                    </div>

                    {{-- Deal value + close date --}}
                    <div class="flex items-center gap-4 mb-6">
                        <template x-if="deal?.deal_value">
                            <span class="inline-flex items-center gap-1 rounded-full bg-emerald-50 dark:bg-emerald-500/10 px-2.5 py-1 text-xs font-medium text-emerald-700 dark:text-emerald-300">
                                <x-heroicon-m-currency-dollar class="h-3 w-3" />
                                <span x-text="deal?.deal_value"></span>
                            </span>
                        </template>
                        <template x-if="deal?.close_date">
                            <span class="inline-flex items-center gap-1 rounded-full bg-gray-100 dark:bg-gray-800 px-2.5 py-1 text-xs font-medium"
                                  :class="deal?.is_overdue ? 'text-rose-600 dark:text-rose-400' : 'text-gray-600 dark:text-gray-400'">
                                <x-heroicon-m-calendar class="h-3 w-3" />
                                <span x-text="deal?.close_date"></span>
                            </span>
                        </template>
                    </div>

                    {{-- Timeline --}}
                    <div class="relative">
                        <template x-for="(step, i) in (deal?.timeline ?? [])" :key="i">
                            <div class="flex items-start gap-4 relative"
                                 :class="i < (deal?.timeline?.length - 1) ? 'pb-5' : ''">

                                {{-- Vertical line --}}
                                <template x-if="i < (deal?.timeline?.length - 1)">
                                    <div class="absolute left-[13px] top-7 bottom-0 w-0.5"
                                         :class="step.done ? 'bg-primary-400' : 'bg-gray-200 dark:bg-gray-700'"></div>
                                </template>

                                {{-- Dot --}}
                                <div class="relative z-10 shrink-0 flex h-7 w-7 items-center justify-center rounded-full border-2 transition-all"
                                     :class="{
                                         'bg-primary-600 border-primary-600 text-white': step.active,
                                         'bg-primary-100 dark:bg-primary-500/20 border-primary-400 text-primary-600 dark:text-primary-400': step.done,
                                         'bg-white dark:bg-gray-900 border-gray-300 dark:border-gray-700 text-gray-400': !step.done && !step.active
                                     }">
                                    <template x-if="step.done">
                                        <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" />
                                        </svg>
                                    </template>
                                    <template x-if="!step.done">
                                        <span class="h-2 w-2 rounded-full"
                                              :class="step.active ? 'bg-white' : 'bg-gray-300 dark:bg-gray-600'"></span>
                                    </template>
                                </div>

                                {{-- Label --}}
                                <div class="min-w-0 flex-1 pt-0.5">
                                    <p class="text-sm leading-6 transition-all"
                                       :class="{
                                           'font-semibold text-primary-700 dark:text-primary-400': step.active,
                                           'text-gray-500 dark:text-gray-400': step.done,
                                           'text-gray-400 dark:text-gray-600': !step.done && !step.active
                                       }"
                                       x-text="step.label"></p>
                                </div>
                            </div>
                        </template>
                    </div>
                </div>
            </div>
        </div>

        {{-- ────────────── ROW 3: My To-Do List + Notifications ────────────── --}}
        <div class="col-span-12 lg:col-span-6">
            <div class="rounded-2xl bg-white dark:bg-gray-900 p-5 shadow-sm border border-gray-100 dark:border-gray-800 h-full">
                <div class="flex items-center justify-between mb-3 gap-2">
                    <h3 class="text-base font-semibold text-gray-900 dark:text-gray-100 inline-flex items-center gap-1.5">
                        <x-heroicon-m-check-circle class="h-4 w-4 text-primary-500" /> To Do
                        @if ($todos['total'] > 0)
                            <span class="inline-flex items-center justify-center rounded-full bg-primary-100 dark:bg-primary-500/20 px-1.5 text-[10px] font-bold text-primary-700 dark:text-primary-300">{{ $todos['total'] }}</span>
                        @endif
                    </h3>
                    <a href="{{ url('/office/sales-activity') }}" class="inline-flex items-center rounded-full bg-gray-100 dark:bg-gray-800 px-3 py-1 text-xs font-medium text-gray-600 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-gray-700">
                        Activity Tracker
                    </a>
                </div>

                {{-- Ringkasan per tahap: "berapa lead baru", "berapa yang masih proposal". --}}
                @if (count($todos['groups']) > 0)
                    <div class="flex flex-wrap gap-1.5 mb-3">
                        @foreach ($todos['groups'] as $group)
                            @php $chip = $pipelineStatusColors[$group['status']] ?? 'bg-gray-100 dark:bg-gray-800 text-gray-600 dark:text-gray-400'; @endphp
                            <span class="inline-flex items-center gap-1 rounded-full px-2 py-0.5 text-[10px] font-medium {{ $chip }}">
                                {{ $group['label'] }}
                                <span class="font-bold">{{ $group['total'] }}</span>
                            </span>
                        @endforeach
                    </div>
                @endif

                <ul class="divide-y divide-gray-100 dark:divide-gray-800">
                    @forelse ($todos['items'] as $todo)
                        <li class="flex items-start gap-3 py-2.5">
                            <span class="mt-1.5 h-2 w-2 shrink-0 rounded-full {{ $todo['is_overdue'] ? 'bg-rose-500' : ($todo['is_today'] ? 'bg-amber-500' : 'bg-gray-300 dark:bg-gray-600') }}"></span>
                            <div class="min-w-0 flex-1">
                                <p class="text-sm font-medium text-gray-900 dark:text-gray-100">
                                    {{ $todo['action'] }}
                                    @if ($todo['is_new'])
                                        <span class="ml-1 inline-flex items-center rounded-full bg-emerald-100 dark:bg-emerald-500/20 px-1.5 text-[10px] font-semibold text-emerald-700 dark:text-emerald-300">Baru</span>
                                    @endif
                                </p>
                                <p class="text-xs text-gray-500 dark:text-gray-400 truncate">
                                    {{ $todo['title'] }}@if ($todo['company']) · {{ $todo['company'] }}@endif
                                </p>
                            </div>
                            <span class="shrink-0 text-xs {{ $todo['is_overdue'] ? 'text-rose-500 font-semibold' : ($todo['is_today'] ? 'text-amber-600 font-medium' : 'text-gray-400') }}">
                                @if ($todo['is_overdue'])
                                    Lewat {{ $todo['due'] }}
                                @elseif ($todo['is_today'])
                                    Hari ini
                                @else
                                    {{ $todo['due'] ?? '—' }}
                                @endif
                            </span>
                        </li>
                    @empty
                        <li class="text-sm text-gray-400 text-center py-6">Tidak ada yang perlu ditindaklanjuti.</li>
                    @endforelse
                </ul>
            </div>
        </div>

        <div class="col-span-12 lg:col-span-6">
            <div class="rounded-2xl bg-white dark:bg-gray-900 p-5 shadow-sm border border-gray-100 dark:border-gray-800 h-full">
                <div class="flex items-center justify-between mb-3">
                    <h3 class="text-base font-semibold text-gray-900 dark:text-gray-100 inline-flex items-center gap-1.5">
                        <x-heroicon-m-bell class="h-4 w-4 text-primary-500" /> Notification
                        @php $newCount = collect($notifications)->where('is_new', true)->count(); @endphp
                        @if ($newCount > 0)
                            <span class="inline-flex items-center justify-center rounded-full bg-rose-500 px-1.5 text-[10px] font-bold text-white">{{ $newCount }}</span>
                        @endif
                    </h3>
                    <button type="button" class="text-xs font-medium text-primary-600 hover:underline dark:text-primary-400 inline-flex items-center gap-1">
                        <x-heroicon-m-check class="h-3 w-3" /> Mark all read
                    </button>
                </div>

                <ul class="space-y-3 max-h-72 overflow-y-auto pr-1">
                    @forelse ($notifications as $notif)
                        <li class="flex items-start gap-3">
                            @if ($notif['avatar'])
                                <img src="{{ $notif['avatar'] }}" alt="" class="h-8 w-8 rounded-full object-cover shrink-0">
                            @else
                                <div class="h-8 w-8 rounded-full bg-primary-100 dark:bg-primary-500/20 flex items-center justify-center text-[10px] font-semibold text-primary-700 dark:text-primary-300 shrink-0">
                                    {{ \Illuminate\Support\Str::substr($notif['actor'], 0, 2) }}
                                </div>
                            @endif
                            <div class="min-w-0 flex-1">
                                <p class="text-sm text-gray-700 dark:text-gray-300">
                                    <span class="font-medium text-gray-900 dark:text-gray-100">{{ $notif['actor'] }}</span> {{ $notif['message'] }}
                                </p>
                                <p class="text-xs text-gray-400 mt-0.5">{{ $notif['time'] }}</p>
                            </div>
                            @if ($notif['is_new'])
                                <span class="h-2 w-2 rounded-full bg-primary-500 shrink-0 mt-2"></span>
                            @endif
                        </li>
                    @empty
                        <li class="text-sm text-gray-400 text-center py-6">Belum ada notifikasi.</li>
                    @endforelse
                </ul>
            </div>
        </div>

        {{-- ────────────── ROW 4: Data Client ────────────── --}}
        <div class="col-span-12">
            <div class="rounded-2xl bg-white dark:bg-gray-900 p-5 shadow-sm border border-gray-100 dark:border-gray-800">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-base font-semibold text-gray-900 dark:text-gray-100 inline-flex items-center gap-1.5">
                        <x-heroicon-m-building-office-2 class="h-4 w-4 text-primary-500" /> Data Client Saya
                    </h3>
                    <a href="{{ url('/office/data-client') }}" class="text-xs font-medium text-primary-600 hover:underline dark:text-primary-400">
                        Lihat semua
                    </a>
                </div>

                @if (count($clients) > 0)
                    <div class="overflow-x-auto">
                        <table class="w-full text-sm">
                            <thead>
                                <tr class="border-b border-gray-100 dark:border-gray-800 text-xs text-gray-500 dark:text-gray-400">
                                    <th class="pb-2 text-left font-medium">Brand</th>
                                    <th class="pb-2 text-left font-medium">Tipe</th>
                                    <th class="pb-2 text-left font-medium">Kategori</th>
                                    <th class="pb-2 text-left font-medium">Status</th>
                                    <th class="pb-2 text-left font-medium">Follow Up</th>
                                    <th class="pb-2 text-left font-medium">Prioritas</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-50 dark:divide-gray-800">
                                @foreach ($clients as $client)
                                    <tr class="group">
                                        <td class="py-2.5 pr-4">
                                            <a href="{{ url('/office/data-client/' . $client['id'] . '/edit') }}" class="font-medium text-gray-900 dark:text-gray-100 group-hover:text-primary-600 dark:group-hover:text-primary-400">
                                                {{ $client['name'] }}
                                            </a>
                                        </td>
                                        <td class="py-2.5 pr-4">
                                            <span class="inline-flex items-center rounded-full {{ $client['type'] === 'direct' ? 'bg-blue-100 dark:bg-blue-500/20 text-blue-700 dark:text-blue-300' : 'bg-purple-100 dark:bg-purple-500/20 text-purple-700 dark:text-purple-300' }} px-2 py-0.5 text-[10px] font-medium capitalize">
                                                {{ $client['type'] }}
                                            </span>
                                        </td>
                                        <td class="py-2.5 pr-4 text-gray-600 dark:text-gray-400">{{ $client['category'] }}</td>
                                        <td class="py-2.5 pr-4">
                                            @if ($client['status'] && $client['status'] !== '—')
                                                <span class="inline-flex items-center rounded-full bg-emerald-100 dark:bg-emerald-500/20 px-2 py-0.5 text-[10px] font-medium text-emerald-700 dark:text-emerald-300">
                                                    {{ $client['status'] }}
                                                </span>
                                            @else
                                                <span class="text-gray-400 text-xs">—</span>
                                            @endif
                                        </td>
                                        <td class="py-2.5 pr-4 text-gray-500 dark:text-gray-400 text-xs">{{ $client['follow_up'] ?? '—' }}</td>
                                        <td class="py-2.5">
                                            @if ($client['priority'])
                                                @php
                                                    $priorityColor = match(strtolower($client['priority'])) {
                                                        'high' => 'bg-rose-100 dark:bg-rose-500/20 text-rose-700 dark:text-rose-300',
                                                        'medium' => 'bg-amber-100 dark:bg-amber-500/20 text-amber-700 dark:text-amber-300',
                                                        'low' => 'bg-gray-100 dark:bg-gray-800 text-gray-600 dark:text-gray-400',
                                                        default => 'bg-gray-100 dark:bg-gray-800 text-gray-600 dark:text-gray-400',
                                                    };
                                                @endphp
                                                <span class="inline-flex items-center rounded-full {{ $priorityColor }} px-2 py-0.5 text-[10px] font-medium capitalize">
                                                    {{ $client['priority'] }}
                                                </span>
                                            @else
                                                <span class="text-gray-400 text-xs">—</span>
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                    <p class="text-sm text-gray-400 text-center py-6">Belum ada data client yang ditugaskan ke Anda.</p>
                @endif
            </div>
        </div>
    </div>
</x-filament-panels::page>
