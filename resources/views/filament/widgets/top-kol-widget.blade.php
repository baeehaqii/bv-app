<x-filament-widgets::widget>
    <x-filament::section>
        <x-slot name="heading">
            <div class="flex items-center gap-2">
                <x-heroicon-o-trophy class="w-5 h-5 text-warning-500" />
                <span>Top KOL Performance</span>
            </div>
        </x-slot>

        <x-slot name="headerEnd">
            <x-filament::button
                wire:click="toggleFullList"
                size="sm"
                color="primary"
                :outlined="true"
            >
                @if($this->showFullList)
                    <x-heroicon-m-chevron-up class="w-4 h-4 mr-1" />
                    Show Less
                @else
                    <x-heroicon-m-list-bullet class="w-4 h-4 mr-1" />
                    View All Rankings
                @endif
            </x-filament::button>
        </x-slot>

        <div class="space-y-4">
            {{-- Top 5 Cards View --}}
            @if(!$this->showFullList)
                <div class="grid grid-cols-1 md:grid-cols-5 gap-4">
                    @foreach($this->getTopKolData() as $index => $kol)
                        @if($index < 5)
                            <div class="relative bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-4 shadow-sm hover:shadow-md transition-shadow">
                                {{-- Rank Badge --}}
                                <div class="absolute -top-2 -left-2">
                                    <span class="inline-flex items-center justify-center w-8 h-8 rounded-full text-xs font-bold 
                                        {{ $kol['rank'] === 1 ? 'bg-yellow-400 text-yellow-900' : ($kol['rank'] === 2 ? 'bg-gray-300 text-gray-800' : ($kol['rank'] === 3 ? 'bg-orange-400 text-orange-900' : 'bg-primary-100 text-primary-700')) }}">
                                        #{{ $kol['rank'] }}
                                    </span>
                                </div>

                                {{-- Content --}}
                                <div class="pt-2">
                                    <p class="font-semibold text-gray-900 dark:text-white truncate">{{ $kol['username'] }}</p>
                                    <p class="text-xs text-gray-500 dark:text-gray-400 truncate">{{ $kol['campaign'] }}</p>
                                    
                                    <div class="mt-2">
                                        <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium
                                            {{ $kol['channel'] === 'Instagram' ? 'bg-pink-100 text-pink-800' : ($kol['channel'] === 'TikTok' ? 'bg-gray-100 text-gray-800' : 'bg-red-100 text-red-800') }}">
                                            {{ $kol['channel'] }}
                                        </span>
                                    </div>

                                    <div class="mt-3 space-y-1">
                                        <div class="flex justify-between text-xs">
                                            <span class="text-gray-500">ER</span>
                                            <span class="font-medium text-green-600">{{ $kol['engagement_rate'] }}%</span>
                                        </div>
                                        <div class="flex justify-between text-xs">
                                            <span class="text-gray-500">Revenue</span>
                                            <span class="font-bold text-green-600">Rp {{ number_format($kol['revenue'] / 1000000, 0) }}M</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @endif
                    @endforeach
                </div>
            @else
                {{-- Full Table View --}}
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead class="bg-gray-50 dark:bg-gray-800">
                            <tr>
                                <th class="px-4 py-3 text-left font-semibold text-gray-600 dark:text-gray-300">#</th>
                                <th class="px-4 py-3 text-left font-semibold text-gray-600 dark:text-gray-300">KOL</th>
                                <th class="px-4 py-3 text-left font-semibold text-gray-600 dark:text-gray-300">Campaign</th>
                                <th class="px-4 py-3 text-left font-semibold text-gray-600 dark:text-gray-300">Channel</th>
                                <th class="px-4 py-3 text-right font-semibold text-gray-600 dark:text-gray-300">Followers</th>
                                <th class="px-4 py-3 text-right font-semibold text-gray-600 dark:text-gray-300">ER</th>
                                <th class="px-4 py-3 text-right font-semibold text-gray-600 dark:text-gray-300">Total Reach</th>
                                <th class="px-4 py-3 text-right font-semibold text-gray-600 dark:text-gray-300">Conversions</th>
                                <th class="px-4 py-3 text-right font-semibold text-gray-600 dark:text-gray-300">Revenue</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                            @foreach($this->getTopKolData() as $kol)
                                <tr class="hover:bg-gray-50 dark:hover:bg-gray-800/50">
                                    <td class="px-4 py-3">
                                        <span class="inline-flex items-center justify-center w-7 h-7 rounded-full text-xs font-bold 
                                            {{ $kol['rank'] === 1 ? 'bg-yellow-400 text-yellow-900' : ($kol['rank'] === 2 ? 'bg-gray-300 text-gray-800' : ($kol['rank'] === 3 ? 'bg-orange-400 text-orange-900' : 'bg-primary-100 text-primary-700 dark:bg-primary-900 dark:text-primary-300')) }}">
                                            {{ $kol['rank'] }}
                                        </span>
                                    </td>
                                    <td class="px-4 py-3 font-semibold text-gray-900 dark:text-white">{{ $kol['username'] }}</td>
                                    <td class="px-4 py-3">
                                        <span class="inline-flex items-center px-2 py-1 rounded-md text-xs font-medium bg-primary-100 text-primary-700 dark:bg-primary-900 dark:text-primary-300">
                                            {{ $kol['campaign'] }}
                                        </span>
                                    </td>
                                    <td class="px-4 py-3">
                                        <span class="inline-flex items-center px-2 py-1 rounded-md text-xs font-medium
                                            {{ $kol['channel'] === 'Instagram' ? 'bg-pink-100 text-pink-800 dark:bg-pink-900 dark:text-pink-300' : ($kol['channel'] === 'TikTok' ? 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300' : 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-300') }}">
                                            {{ $kol['channel'] }}
                                        </span>
                                    </td>
                                    <td class="px-4 py-3 text-right">{{ number_format($kol['followers'], 0, ',', '.') }}</td>
                                    <td class="px-4 py-3 text-right text-green-600 font-medium">{{ $kol['engagement_rate'] }}%</td>
                                    <td class="px-4 py-3 text-right">{{ number_format($kol['total_reach'], 0, ',', '.') }}</td>
                                    <td class="px-4 py-3 text-right">{{ number_format($kol['conversions'], 0, ',', '.') }}</td>
                                    <td class="px-4 py-3 text-right font-bold text-green-600">Rp {{ number_format($kol['revenue'], 0, ',', '.') }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>
    </x-filament::section>
</x-filament-widgets::widget>
