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
            <div class="flex items-center gap-3">
                <div class="flex bg-gray-100 dark:bg-gray-800 rounded-lg p-1 text-sm">
                    <button 
                        wire:click="setFilter('client')"
                        class="px-3 py-1 rounded-md transition-all {{ $this->filter === 'client' ? 'bg-white dark:bg-gray-700 shadow text-gray-900 dark:text-gray-100 font-medium' : 'text-gray-500 hover:text-gray-700 dark:hover:text-gray-300' }}"
                    >
                        Direct Client
                    </button>
                    <button 
                        wire:click="setFilter('agency')"
                        class="px-3 py-1 rounded-md transition-all {{ $this->filter === 'agency' ? 'bg-white dark:bg-gray-700 shadow text-gray-900 dark:text-gray-100 font-medium' : 'text-gray-500 hover:text-gray-700 dark:hover:text-gray-300' }}"
                    >
                        Agency
                    </button>
                </div>

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
            </div>
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
                                            <button 
                                                wire:click="openCampaignsModal('{{ addslashes($client['client_name']) }}')"
                                                class="font-medium text-primary-600 hover:text-primary-700 hover:underline cursor-pointer focus:outline-none"
                                            >
                                                {{ $client['total_campaigns'] }}
                                            </button>
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
                                        <button 
                                            wire:click="openCampaignsModal('{{ addslashes($client['client_name']) }}')"
                                            class="inline-flex items-center px-2 py-1 rounded-md text-xs font-medium bg-primary-100 text-primary-700 dark:bg-primary-900 dark:text-primary-300 hover:bg-primary-200 transition-colors cursor-pointer focus:outline-none"
                                        >
                                            {{ $client['total_campaigns'] }} campaigns
                                        </button>
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

    {{-- Modal --}}
    @if($campaignsModalOpen)
        <div 
            x-data
            class="fixed inset-0 z-50 flex items-center justify-center p-4 overflow-y-auto" 
            style="z-index: 9999;"
        >
            <div 
                class="fixed inset-0 bg-gray-900/50 backdrop-blur-sm transition-opacity" 
                wire:click="closeCampaignsModal"
            ></div>
            
            <div class="relative w-full max-w-4xl bg-white dark:bg-gray-900 rounded-xl shadow-2xl overflow-hidden ring-1 ring-gray-200 dark:ring-gray-800 animate-in fade-in zoom-in-95 duration-200">
                {{-- Modal Header --}}
                <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-800 flex items-center justify-between bg-gray-50 dark:bg-gray-800/50">
                    <h3 class="text-base font-semibold text-gray-900 dark:text-white flex items-center gap-2">
                        <x-heroicon-o-megaphone class="w-5 h-5 text-primary-600" />
                        Campaign List - <span class="text-primary-600">{{ $selectedClientName }}</span>
                    </h3>
                    <button wire:click="closeCampaignsModal" class="text-gray-400 hover:text-gray-500 dark:hover:text-gray-300 transition-colors">
                        <x-heroicon-m-x-mark class="w-5 h-5" />
                    </button>
                </div>
                
                {{-- Modal Content --}}
                <div class="p-6">
                     <div class="overflow-x-auto rounded-lg border border-gray-200 dark:border-gray-700">
                        <table class="w-full text-sm text-left">
                            <thead class="bg-gray-50 dark:bg-gray-800 text-gray-500 font-medium border-b border-gray-200 dark:border-gray-700">
                                <tr>
                                    <th class="px-4 py-3">Campaign Name</th>
                                    <th class="px-4 py-3">Period</th>
                                    <th class="px-4 py-3 text-center">KOLs</th>
                                    <th class="px-4 py-3 text-right">Budget</th>
                                    <th class="px-4 py-3 text-center">Status</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100 dark:divide-gray-700 bg-white dark:bg-gray-900">
                                @foreach($selectedClientCampaigns as $campaign)
                                <tr class="hover:bg-gray-50 dark:hover:bg-gray-800/50">
                                    <td class="px-4 py-3 font-medium text-gray-900 dark:text-white">{{ $campaign['name'] }}</td>
                                    <td class="px-4 py-3 text-gray-500 dark:text-gray-400">{{ $campaign['period'] }}</td>
                                    <td class="px-4 py-3 text-center text-gray-600 dark:text-gray-300">{{ $campaign['kol_count'] }}</td>
                                    <td class="px-4 py-3 text-right font-medium text-gray-900 dark:text-white">Rp {{ number_format($campaign['budget'], 0, ',', '.') }}</td>
                                    <td class="px-4 py-3 text-center">
                                        @php
                                            $statusColors = [
                                                'Ongoing' => 'bg-green-100 text-green-700 border-green-200',
                                                'Planning' => 'bg-blue-100 text-blue-700 border-blue-200',
                                                'Completed' => 'bg-gray-100 text-gray-700 border-gray-200',
                                                'Draft' => 'bg-gray-100 text-gray-500 border-gray-200',
                                            ];
                                            $colorClass = $statusColors[$campaign['status']] ?? 'bg-gray-100 text-gray-700';
                                        @endphp
                                        <span class="px-2.5 py-0.5 text-xs rounded-full border {{ $colorClass }}">
                                            {{ $campaign['status'] }}
                                        </span>
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                     </div>
                </div>
            </div>
        </div>
    @endif
</x-filament-widgets::widget>
