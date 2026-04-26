<x-filament-widgets::widget>
    <x-filament::section :heading="'Pipeline & Aktivitas — ' . $this->getPeriodLabel()">
        @php
            $deals = $this->getRecentDeals();
            $summary = $this->getPipelineSummary();
        @endphp

        {{-- Pipeline summary ─────────────────────────────────────── --}}
        @if(count($summary) > 0)
            <div class="mb-6">
                <p class="text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400 mb-3">
                    Ringkasan Pipeline
                </p>
                <div class="flex flex-wrap gap-2">
                    @foreach($summary as $item)
                        @php
                            $status = $item['status'];
                            $colorMap = [
                                'gray' => 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-200',
                                'warning' => 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200',
                                'info' => 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200',
                                'success' => 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200',
                                'danger' => 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200',
                                'purple' => 'bg-purple-100 text-purple-800 dark:bg-purple-900 dark:text-purple-200',
                                'orange' => 'bg-orange-100 text-orange-800 dark:bg-orange-900 dark:text-orange-200',
                            ];
                            $colorClass = $colorMap[$status->getColor()] ?? $colorMap['gray'];
                        @endphp
                        <span
                            class="inline-flex items-center gap-1 rounded-full px-3 py-1 text-xs font-medium {{ $colorClass }}">
                            {{ $status->getLabel() }}
                            <span class="font-bold">{{ $item['count'] }}</span>
                        </span>
                    @endforeach
                </div>
            </div>
        @endif

        {{-- Recent activity ──────────────────────────────────────── --}}
        <p class="text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400 mb-3">
            Aktivitas Terakhir — {{ $this->getPeriodLabel() }}
        </p>

        @if($deals->isEmpty())
            <p class="text-sm text-gray-400 dark:text-gray-500 py-4 text-center">Belum ada aktivitas deal</p>
        @else
            <div class="divide-y divide-gray-100 dark:divide-gray-700">
                @foreach($deals as $deal)
                    @php
                        $status = $deal->status;
                        $colorMap = [
                            'gray' => 'bg-gray-100 text-gray-700 dark:bg-gray-700 dark:text-gray-300',
                            'warning' => 'bg-yellow-100 text-yellow-700 dark:bg-yellow-900 dark:text-yellow-300',
                            'info' => 'bg-blue-100 text-blue-700 dark:bg-blue-900 dark:text-blue-300',
                            'success' => 'bg-green-100 text-green-700 dark:bg-green-900 dark:text-green-300',
                            'danger' => 'bg-red-100 text-red-700 dark:bg-red-900 dark:text-red-300',
                            'purple' => 'bg-purple-100 text-purple-700 dark:bg-purple-900 dark:text-purple-300',
                            'orange' => 'bg-orange-100 text-orange-700 dark:bg-orange-900 dark:text-orange-300',
                        ];
                        $colorClass = $colorMap[$status->getColor()] ?? $colorMap['gray'];
                    @endphp
                    <div class="flex items-center justify-between py-3">
                        <div class="flex items-center gap-3 min-w-0">
                            <span
                                class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium {{ $colorClass }} shrink-0">
                                {{ $status->getLabel() }}
                            </span>
                            <div class="min-w-0">
                                <p class="text-sm font-medium text-gray-900 dark:text-white truncate">
                                    {{ $deal->event_name }}
                                </p>
                                @if($deal->company_name)
                                    <p class="text-xs text-gray-500 dark:text-gray-400 truncate">
                                        {{ $deal->company_name }}
                                    </p>
                                @endif
                            </div>
                        </div>
                        <div class="text-right shrink-0 ml-4">
                            @if($deal->deal_value > 0)
                                <p class="text-sm font-semibold text-gray-900 dark:text-white">
                                    Rp {{ number_format($deal->deal_value, 0, ',', '.') }}
                                </p>
                            @elseif($deal->budget_propose > 0)
                                <p class="text-sm text-gray-500 dark:text-gray-400">
                                    ~Rp {{ number_format($deal->budget_propose, 0, ',', '.') }}
                                </p>
                            @endif
                            <p class="text-xs text-gray-400 dark:text-gray-500">
                                {{ \Carbon\Carbon::parse($deal->updated_at)->diffForHumans() }}
                            </p>
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    </x-filament::section>
</x-filament-widgets::widget>