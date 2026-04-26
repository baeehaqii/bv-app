<x-filament-panels::page>
    {{-- Filter bar: title desc kiri, filter buttons kanan ──────────── --}}
    <div class="flex flex-wrap items-center justify-between gap-3 -mt-2 mb-6">
        <p class="text-sm text-gray-500 dark:text-gray-400">
            Ringkasan performa bisnis {{ \Carbon\Carbon::now()->translatedFormat('F Y') }}
        </p>
        <div class="flex gap-1.5 flex-wrap">
            @foreach([
                'today'     => 'Hari Ini',
                'yesterday' => 'Kemarin',
                '7d'        => '7 Hari',
                '30d'       => '30 Hari',
                '90d'       => '90 Hari',
            ] as $value => $label)
                <button
                    wire:click="$set('dateFilter', '{{ $value }}')"
                    @class([
                        'px-3 py-1.5 text-xs font-medium rounded-lg border transition-colors duration-150 focus:outline-none',
                        'bg-primary-600 border-primary-600 text-white shadow-sm' => $dateFilter === $value,
                        'bg-white dark:bg-gray-800 border-gray-200 dark:border-gray-700 text-gray-600 dark:text-gray-400 hover:bg-gray-50 dark:hover:bg-gray-700' => $dateFilter !== $value,
                    ])
                >
                    {{ $label }}
                </button>
            @endforeach
        </div>
    </div>

    <x-filament-widgets::widgets
        :widgets="$this->getWidgets()"
        :columns="$this->getColumns()"
    />
</x-filament-panels::page>
