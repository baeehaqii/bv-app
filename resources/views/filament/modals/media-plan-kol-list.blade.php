<div class="space-y-4 p-1">
    @if($kols->isEmpty())
        <p class="text-sm text-gray-500 dark:text-gray-400 text-center py-6">Belum ada KOL di media plan ini.</p>
    @else
        <div class="overflow-x-auto rounded-lg border border-gray-200 dark:border-gray-700">
            <table class="w-full text-sm text-left">
                <thead
                    class="bg-gray-50 dark:bg-gray-800 text-xs text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                    <tr>
                        <th class="px-4 py-3">#</th>
                        <th class="px-4 py-3">Nama KOL</th>
                        <th class="px-4 py-3">Channel</th>
                        <th class="px-4 py-3">Followers</th>
                        <th class="px-4 py-3">Rate</th>
                        <th class="px-4 py-3">Status</th>
                        <th class="px-4 py-3">Selected</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                    @foreach($kols as $index => $kol)
                                    <tr class="bg-white dark:bg-gray-900 hover:bg-gray-50 dark:hover:bg-gray-800 transition-colors">
                                        <td class="px-4 py-3 text-gray-500 dark:text-gray-400">{{ $index + 1 }}</td>
                                        <td class="px-4 py-3 font-medium text-gray-900 dark:text-white">
                                            {{ $kol->name ?? '-' }}
                                        </td>
                                        <td class="px-4 py-3">
                                            @php
                                                $channelColor = match ($kol->channel) {
                                                    'Instagram' => 'bg-pink-100 text-pink-700 dark:bg-pink-900/30 dark:text-pink-400',
                                                    'Tiktok' => 'bg-gray-100 text-gray-700 dark:bg-gray-700 dark:text-gray-300',
                                                    'Youtube Channels', 'Youtube Shorts' => 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400',
                                                    default => 'bg-gray-100 text-gray-600 dark:bg-gray-700 dark:text-gray-300',
                                                };
                                            @endphp
                         <span
                                                class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium {{ $channelColor }}">
                                                {{ $kol->channel ?? '-' }}
                                            </span>
                                        </td>
                                        <td class="px-4 py-3 text-gray-700 dark:text-gray-300">
                                            {{ $kol->followers ? number_format($kol->followers) : '-' }}
                                        </td>
                                        <td class="px-4 py-3 text-gray-700 dark:text-gray-300">
                                            {{ $kol->rate ? 'Rp ' . number_format($kol->rate, 0, ',', '.') : '-' }}
                                        </td>
                                        <td class="px-4 py-3">
                                            @php
                                                $statusColor = match ($kol->status) {
                                                    'Locked' => 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400',
                                                    'Approaching' => 'bg-yellow-100 text-yellow-700 dark:bg-yellow-900/30 dark:text-yellow-400',
                                                    'Canceled' => 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400',
                                                    default => 'bg-gray-100 text-gray-600 dark:bg-gray-700 dark:text-gray-300',
                                                };
                                            @endphp
                         <span
                                                class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium {{ $statusColor }}">
                                                {{ $kol->status ?? 'New List' }}
                                            </span>
                                        </td>
                                        <td class="px-4 py-3 text-center">
                                            @if($kol->is_selected)
                                                <span class="text-green-500 dark:text-green-400">
                                                    <svg class="w-4 h-4 inline" fill="currentColor" viewBox="0 0 20 20">
                                                        <path fill-rule="evenodd"
                                                            d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z"
                                                            clip-rule="evenodd" />
                                                    </svg>
                                                </span>
                                            @else
                                                <span class="text-gray-300 dark:text-gray-600">—</span>
                                            @endif
                                        </td>
                                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        <p class="text-xs text-gray-400 dark:text-gray-500 text-right">Total: {{ $kols->count() }} KOL</p>
    @endif
</div>