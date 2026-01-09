<x-filament-widgets::widget>
    <x-filament::section>
        <x-slot name="heading">
            <div class="flex items-center gap-2">
                <x-heroicon-o-currency-dollar class="w-5 h-5 text-success-500" />
                <span>Top Spender Clients</span>
            </div>
        </x-slot>

        <x-slot name="description">
            <div class="flex items-center gap-4 text-sm">
                <span class="text-gray-500">Total Revenue: <span class="font-semibold text-success-600">Rp {{ number_format($this->getTotalRevenue(), 0, ',', '.') }}</span></span>
                <span class="text-gray-500">Active Clients: <span class="font-semibold text-primary-600">{{ $this->getActiveClients() }}</span></span>
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
                    View All Clients
                @endif
            </x-filament::button>
        </x-slot>

        <div class="space-y-4">
            {{-- Top 5 Cards View --}}
            @if(!$this->showFullList)
                <div class="grid grid-cols-1 md:grid-cols-5 gap-4">
                    @foreach($this->getTopSpenderData() as $index => $client)
                        @if($index < 5)
                            <div class="relative bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-4 shadow-sm hover:shadow-md transition-shadow">
                                {{-- Rank Badge --}}
                                <div class="absolute -top-2 -left-2">
                                    <span class="inline-flex items-center justify-center w-8 h-8 rounded-full text-xs font-bold 
                                        {{ $client['rank'] === 1 ? 'bg-yellow-400 text-yellow-900' : ($client['rank'] === 2 ? 'bg-gray-300 text-gray-800' : ($client['rank'] === 3 ? 'bg-orange-400 text-orange-900' : 'bg-primary-100 text-primary-700')) }}">
                                        #{{ $client['rank'] }}
                                    </span>
                                </div>

                                {{-- Status Badge --}}
                                <div class="absolute -top-2 -right-2">
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium
                                        {{ $client['status'] === 'Active' ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800' }}">
                                        {{ $client['status'] }}
                                    </span>
                                </div>

                                {{-- Content --}}
                                <div class="pt-3">
                                    <p class="font-semibold text-gray-900 dark:text-white truncate text-sm">{{ $client['client_name'] }}</p>
                                    <p class="text-xs text-gray-500 dark:text-gray-400">{{ $client['industry'] }}</p>
                                    
                                    <div class="mt-3 space-y-1">
                                        <div class="flex justify-between text-xs">
                                            <span class="text-gray-500">Campaigns</span>
                                            <span class="font-medium text-primary-600">{{ $client['total_campaigns'] }}</span>
                                        </div>
                                        <div class="flex justify-between text-xs">
                                            <span class="text-gray-500">Total Spent</span>
                                            <span class="font-bold text-green-600">Rp {{ number_format($client['total_spent'] / 1000000000, 1) }}B</span>
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
                                <th class="px-4 py-3 text-left font-semibold text-gray-600 dark:text-gray-300">Client</th>
                                <th class="px-4 py-3 text-left font-semibold text-gray-600 dark:text-gray-300">Industry</th>
                                <th class="px-4 py-3 text-center font-semibold text-gray-600 dark:text-gray-300">Campaigns</th>
                                <th class="px-4 py-3 text-right font-semibold text-gray-600 dark:text-gray-300">Total Spent</th>
                                <th class="px-4 py-3 text-left font-semibold text-gray-600 dark:text-gray-300">Last Campaign</th>
                                <th class="px-4 py-3 text-center font-semibold text-gray-600 dark:text-gray-300">Status</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                            @foreach($this->getTopSpenderData() as $client)
                                <tr class="hover:bg-gray-50 dark:hover:bg-gray-800/50">
                                    <td class="px-4 py-3">
                                        <span class="inline-flex items-center justify-center w-7 h-7 rounded-full text-xs font-bold 
                                            {{ $client['rank'] === 1 ? 'bg-yellow-400 text-yellow-900' : ($client['rank'] === 2 ? 'bg-gray-300 text-gray-800' : ($client['rank'] === 3 ? 'bg-orange-400 text-orange-900' : 'bg-primary-100 text-primary-700 dark:bg-primary-900 dark:text-primary-300')) }}">
                                            {{ $client['rank'] }}
                                        </span>
                                    </td>
                                    <td class="px-4 py-3">
                                        <div>
                                            <p class="font-semibold text-gray-900 dark:text-white">{{ $client['client_name'] }}</p>
                                        </div>
                                    </td>
                                    <td class="px-4 py-3">
                                        <span class="inline-flex items-center px-2 py-1 rounded-md text-xs font-medium bg-gray-100 text-gray-700 dark:bg-gray-700 dark:text-gray-300">
                                            {{ $client['industry'] }}
                                        </span>
                                    </td>
                                    <td class="px-4 py-3 text-center">
                                        <span class="inline-flex items-center px-2 py-1 rounded-md text-xs font-medium bg-primary-100 text-primary-700 dark:bg-primary-900 dark:text-primary-300">
                                            {{ $client['total_campaigns'] }} campaigns
                                        </span>
                                    </td>
                                    <td class="px-4 py-3 text-right font-bold text-green-600">
                                        Rp {{ number_format($client['total_spent'], 0, ',', '.') }}
                                    </td>
                                    <td class="px-4 py-3 text-gray-600 dark:text-gray-400">
                                        {{ $client['last_campaign'] }}
                                    </td>
                                    <td class="px-4 py-3 text-center">
                                        <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium
                                            {{ $client['status'] === 'Active' ? 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-300' : 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300' }}">
                                            {{ $client['status'] }}
                                        </span>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                        <tfoot class="bg-gray-50 dark:bg-gray-800">
                            <tr>
                                <td colspan="4" class="px-4 py-3 text-right font-semibold text-gray-600 dark:text-gray-300">
                                    Total Revenue:
                                </td>
                                <td class="px-4 py-3 text-right font-bold text-green-600 text-lg">
                                    Rp {{ number_format($this->getTotalRevenue(), 0, ',', '.') }}
                                </td>
                                <td colspan="2"></td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            @endif
        </div>
    </x-filament::section>
</x-filament-widgets::widget>
