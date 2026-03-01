<x-filament-widgets::widget>
    <x-filament::section>
        @php
            $data = $this->getReportData();
            $reports = $data['reports'];
            $totals = $data['totals'];
            $periodLabel = $data['period_label'];

            $fmt = fn(float $val): string => 'Rp ' . number_format($val, 0, ',', '.');
        @endphp

        <div class="space-y-4">
            {{-- Header --}}
            <div class="flex items-center justify-between">
                <div>
                    <h3 class="text-base font-semibold text-gray-950 dark:text-white">
                        Report Per BD Manager
                    </h3>
                    <p class="text-sm text-gray-500 dark:text-gray-400">
                        Periode: {{ $periodLabel }}
                    </p>
                </div>
                <div class="flex items-center gap-3 text-xs text-gray-500 dark:text-gray-400">
                    <span class="inline-flex items-center gap-1">
                        <span class="w-2 h-2 rounded-full bg-primary-500"></span> Total Deal
                    </span>
                    <span class="inline-flex items-center gap-1">
                        <span class="w-2 h-2 rounded-full bg-emerald-500"></span> Gross Profit
                    </span>
                </div>
            </div>

            {{-- Summary Cards --}}
            <div class="grid grid-cols-2 gap-3 sm:grid-cols-4">
                <div class="rounded-lg border border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800/50 p-3">
                    <p class="text-xs text-gray-500 dark:text-gray-400">Total Campaign</p>
                    <p class="text-lg font-bold text-gray-950 dark:text-white">{{ number_format($totals['total_campaigns']) }}</p>
                </div>
                <div class="rounded-lg border border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800/50 p-3">
                    <p class="text-xs text-gray-500 dark:text-gray-400">Total Deal Value</p>
                    <p class="text-lg font-bold text-primary-600 dark:text-primary-400">{{ $fmt($totals['total_deal_value']) }}</p>
                </div>
                <div class="rounded-lg border border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800/50 p-3">
                    <p class="text-xs text-gray-500 dark:text-gray-400">Total Gross Profit</p>
                    <p class="text-lg font-bold text-emerald-600 dark:text-emerald-400">{{ $fmt($totals['total_gross_profit']) }}</p>
                </div>
                <div class="rounded-lg border border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800/50 p-3">
                    <p class="text-xs text-gray-500 dark:text-gray-400">Win Rate</p>
                    <p class="text-lg font-bold text-amber-600 dark:text-amber-400">
                        {{ $totals['total_campaigns'] > 0 ? number_format(($totals['won_campaigns'] / $totals['total_campaigns']) * 100, 1) : 0 }}%
                    </p>
                </div>
            </div>

            {{-- Table --}}
            @if(count($reports) > 0)
                <div class="overflow-x-auto rounded-lg border border-gray-200 dark:border-gray-700">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="bg-gray-50 dark:bg-gray-800/80">
                                <th class="px-4 py-2.5 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">BD Manager</th>
                                <th class="px-4 py-2.5 text-center text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Campaign</th>
                                <th class="px-4 py-2.5 text-center text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Won</th>
                                <th class="px-4 py-2.5 text-center text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Lost</th>
                                <th class="px-4 py-2.5 text-right text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Budget Propose</th>
                                <th class="px-4 py-2.5 text-right text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Deal Value</th>
                                <th class="px-4 py-2.5 text-right text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Gross Profit</th>
                                <th class="px-4 py-2.5 text-center text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Win Rate</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                            @foreach($reports as $index => $report)
                                <tr class="hover:bg-gray-50 dark:hover:bg-gray-800/40 transition-colors">
                                    <td class="px-4 py-3">
                                        <div class="flex items-center gap-2.5">
                                            <div class="flex-shrink-0 w-7 h-7 rounded-full bg-primary-100 dark:bg-primary-500/20 flex items-center justify-center">
                                                <span class="text-xs font-bold text-primary-700 dark:text-primary-400">{{ $index + 1 }}</span>
                                            </div>
                                            <span class="font-medium text-gray-900 dark:text-gray-100">{{ $report['name'] }}</span>
                                        </div>
                                    </td>
                                    <td class="px-4 py-3 text-center text-gray-700 dark:text-gray-300">{{ $report['total_campaigns'] }}</td>
                                    <td class="px-4 py-3 text-center">
                                        <span class="inline-flex items-center px-1.5 py-0.5 text-xs font-medium rounded bg-emerald-50 text-emerald-700 dark:bg-emerald-500/10 dark:text-emerald-400">
                                            {{ $report['won_campaigns'] }}
                                        </span>
                                    </td>
                                    <td class="px-4 py-3 text-center">
                                        <span class="inline-flex items-center px-1.5 py-0.5 text-xs font-medium rounded bg-red-50 text-red-700 dark:bg-red-500/10 dark:text-red-400">
                                            {{ $report['lost_campaigns'] }}
                                        </span>
                                    </td>
                                    <td class="px-4 py-3 text-right text-gray-600 dark:text-gray-400">{{ $fmt($report['total_budget_propose']) }}</td>
                                    <td class="px-4 py-3 text-right font-semibold text-primary-600 dark:text-primary-400">{{ $fmt($report['total_deal_value']) }}</td>
                                    <td class="px-4 py-3 text-right font-semibold text-emerald-600 dark:text-emerald-400">{{ $fmt($report['gross_profit']) }}</td>
                                    <td class="px-4 py-3 text-center">
                                        @php
                                            $rateColor = $report['win_rate'] >= 60 ? 'text-emerald-600 dark:text-emerald-400' :
                                                        ($report['win_rate'] >= 30 ? 'text-amber-600 dark:text-amber-400' : 'text-red-600 dark:text-red-400');
                                        @endphp
                                        <span class="font-semibold {{ $rateColor }}">{{ $report['win_rate'] }}%</span>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                        {{-- Footer Total --}}
                        <tfoot>
                            <tr class="bg-gray-100 dark:bg-gray-800 font-semibold">
                                <td class="px-4 py-3 text-gray-900 dark:text-white">Total</td>
                                <td class="px-4 py-3 text-center text-gray-900 dark:text-white">{{ $totals['total_campaigns'] }}</td>
                                <td class="px-4 py-3 text-center text-emerald-700 dark:text-emerald-400">{{ $totals['won_campaigns'] }}</td>
                                <td class="px-4 py-3 text-center text-red-700 dark:text-red-400">{{ $totals['lost_campaigns'] }}</td>
                                <td class="px-4 py-3 text-right text-gray-900 dark:text-white">{{ $fmt($totals['total_budget_propose']) }}</td>
                                <td class="px-4 py-3 text-right text-primary-600 dark:text-primary-400">{{ $fmt($totals['total_deal_value']) }}</td>
                                <td class="px-4 py-3 text-right text-emerald-600 dark:text-emerald-400">{{ $fmt($totals['total_gross_profit']) }}</td>
                                <td class="px-4 py-3 text-center text-gray-900 dark:text-white">
                                    {{ $totals['total_campaigns'] > 0 ? number_format(($totals['won_campaigns'] / $totals['total_campaigns']) * 100, 1) : 0 }}%
                                </td>
                            </tr>
                        </tfoot>
                    </table>
                </div>

                {{-- Visual Bar per BD Manager --}}
                <div class="space-y-2 mt-2">
                    <p class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Deal Value per BD Manager</p>
                    @php $maxDeal = collect($reports)->max('total_deal_value') ?: 1; @endphp
                    @foreach($reports as $report)
                        <div class="flex items-center gap-3">
                            <span class="w-28 text-xs text-gray-600 dark:text-gray-400 truncate text-right" title="{{ $report['name'] }}">{{ $report['name'] }}</span>
                            <div class="flex-1 h-5 bg-gray-100 dark:bg-gray-800 rounded-full overflow-hidden relative">
                                {{-- Deal value bar --}}
                                <div class="absolute inset-y-0 left-0 bg-primary-500/80 dark:bg-primary-400/60 rounded-full transition-all duration-500"
                                     style="width: {{ ($report['total_deal_value'] / $maxDeal) * 100 }}%"></div>
                                {{-- Gross profit bar (overlay) --}}
                                @if($report['total_deal_value'] > 0)
                                    <div class="absolute inset-y-0 left-0 bg-emerald-500/80 dark:bg-emerald-400/60 rounded-full transition-all duration-500"
                                         style="width: {{ ($report['gross_profit'] / $maxDeal) * 100 }}%"></div>
                                @endif
                            </div>
                            <span class="w-28 text-xs font-medium text-gray-700 dark:text-gray-300">{{ $fmt($report['total_deal_value']) }}</span>
                        </div>
                    @endforeach
                </div>
            @else
                <div class="text-center py-8 text-gray-500 dark:text-gray-400">
                    <x-heroicon-o-chart-bar class="w-12 h-12 mx-auto mb-2 opacity-40" />
                    <p class="text-sm">Belum ada data campaign untuk periode ini.</p>
                </div>
            @endif
        </div>
    </x-filament::section>
</x-filament-widgets::widget>
