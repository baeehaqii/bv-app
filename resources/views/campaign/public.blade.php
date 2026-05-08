<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $campaign->campaign_name }} — Campaign Report | Beyond Viral</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Inter', sans-serif; }
        .stat-card { transition: transform 0.15s, box-shadow 0.15s; }
        .stat-card:hover { transform: translateY(-2px); box-shadow: 0 8px 25px rgba(0,0,0,0.1); }
    </style>
</head>
<body class="bg-gray-50 min-h-screen">

    {{-- Header --}}
    <header class="bg-white border-b border-gray-200 sticky top-0 z-10 shadow-sm">
        <div class="max-w-5xl mx-auto px-4 sm:px-6 py-4 flex items-center justify-between">
            <div class="flex items-center gap-3">
                <img src="https://res.cloudinary.com/dbr6xazzh/image/upload/v1763576028/01_Logo_Main_f2t5wp.avif"
                     alt="Beyond Viral" class="h-8 object-contain">
                <span class="text-gray-300">|</span>
                <span class="text-sm text-gray-500 font-medium">Campaign Report</span>
            </div>
            <span class="text-xs text-gray-400">Powered by Beyond Viral</span>
        </div>
    </header>

    <main class="max-w-5xl mx-auto px-4 sm:px-6 py-8 space-y-8">

        {{-- Campaign Hero --}}
        <div class="bg-white rounded-2xl overflow-hidden shadow-sm border border-gray-100">
            @if ($campaign->campaign_image)
                <div class="h-48 sm:h-64 bg-gray-100 overflow-hidden">
                    <img src="{{ asset('storage/' . $campaign->campaign_image) }}"
                         alt="{{ $campaign->campaign_name }}"
                         class="w-full h-full object-cover">
                </div>
            @endif
            <div class="p-6 sm:p-8">
                <div class="flex flex-wrap items-start justify-between gap-4">
                    <div>
                        <h1 class="text-2xl sm:text-3xl font-bold text-gray-900">{{ $campaign->campaign_name }}</h1>
                        @if ($campaign->client?->nama_brand)
                            <p class="mt-1 text-gray-500 text-sm">{{ $campaign->client->nama_brand }}</p>
                        @endif
                    </div>
                    @php
                        $statusMap = [
                            'draft'     => ['label' => 'Draft',     'class' => 'bg-gray-100 text-gray-600'],
                            'upcoming'  => ['label' => 'Upcoming',  'class' => 'bg-blue-100 text-blue-700'],
                            'ongoing'   => ['label' => 'Ongoing',   'class' => 'bg-yellow-100 text-yellow-700'],
                            'completed' => ['label' => 'Completed', 'class' => 'bg-green-100 text-green-700'],
                            'cancelled' => ['label' => 'Cancelled', 'class' => 'bg-red-100 text-red-700'],
                        ];
                        $s = $statusMap[$campaign->status] ?? ['label' => ucfirst($campaign->status), 'class' => 'bg-gray-100 text-gray-600'];
                    @endphp
                    <span class="inline-flex items-center px-3 py-1.5 rounded-full text-sm font-semibold {{ $s['class'] }}">
                        {{ $s['label'] }}
                    </span>
                </div>

                {{-- Timeline --}}
                @if ($campaign->start_date && $campaign->end_date)
                    <div class="mt-6">
                        <div class="flex justify-between text-xs text-gray-500 mb-1.5">
                            <span>{{ $campaign->start_date->format('d M Y') }}</span>
                            <span class="font-semibold text-indigo-600">{{ $campaign->progress }}% selesai</span>
                            <span>{{ $campaign->end_date->format('d M Y') }}</span>
                        </div>
                        <div class="bg-gray-100 rounded-full h-2.5 overflow-hidden">
                            <div class="h-full rounded-full bg-gradient-to-r from-indigo-500 to-purple-500 transition-all"
                                 style="width: {{ $campaign->progress }}%"></div>
                        </div>
                    </div>
                @endif

                @if ($campaign->campaign_description)
                    <p class="mt-5 text-gray-600 text-sm leading-relaxed">{{ $campaign->campaign_description }}</p>
                @endif
            </div>
        </div>

        {{-- Performance Summary --}}
        @if ($kols->isNotEmpty())
            <div>
                <h2 class="text-lg font-semibold text-gray-800 mb-4">Performance Summary</h2>
                <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-5 gap-4">
                    @php
                        $stats = [
                            ['label' => 'Total KOL',        'value' => $kols->count(),              'format' => 'number', 'color' => 'indigo'],
                            ['label' => 'Total Views',      'value' => $totalViews,                  'format' => 'number', 'color' => 'blue'],
                            ['label' => 'Total Engagement', 'value' => $totalEngagement,             'format' => 'number', 'color' => 'purple'],
                            ['label' => 'Total Likes',      'value' => $totalLikes,                  'format' => 'number', 'color' => 'pink'],
                            ['label' => 'Avg. ER',          'value' => number_format($avgEr, 2).'%', 'format' => 'raw',    'color' => 'green'],
                        ];
                        $colorMap = [
                            'indigo' => 'bg-indigo-50 text-indigo-700',
                            'blue'   => 'bg-blue-50 text-blue-700',
                            'purple' => 'bg-purple-50 text-purple-700',
                            'pink'   => 'bg-pink-50 text-pink-700',
                            'green'  => 'bg-green-50 text-green-700',
                        ];
                    @endphp
                    @foreach ($stats as $stat)
                        <div class="stat-card bg-white rounded-xl border border-gray-100 shadow-sm p-4 text-center">
                            <div class="text-xl font-bold {{ str_replace(['bg-', 'text-'], ['', 'text-'], $colorMap[$stat['color']]) }}">
                                {{ $stat['format'] === 'number' ? number_format($stat['value']) : $stat['value'] }}
                            </div>
                            <div class="text-xs text-gray-500 mt-1 font-medium">{{ $stat['label'] }}</div>
                        </div>
                    @endforeach
                </div>
            </div>

            {{-- KOL Breakdown per Platform --}}
            @foreach ($platforms as $platform => $platformKols)
                @php
                    $platformLabel = match($platform) {
                        'instagram' => 'Instagram',
                        'tiktok'    => 'TikTok',
                        'youtube'   => 'YouTube',
                        default     => ucfirst($platform),
                    };
                    $platformIcon = match($platform) {
                        'instagram' => '📸',
                        'tiktok'    => '🎵',
                        'youtube'   => '▶️',
                        default     => '🌐',
                    };
                @endphp
                <div class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden">
                    <div class="px-6 py-4 border-b border-gray-100 flex items-center gap-2">
                        <span class="text-lg">{{ $platformIcon }}</span>
                        <h3 class="font-semibold text-gray-800">{{ $platformLabel }}</h3>
                        <span class="ml-auto text-xs text-gray-400 font-medium">{{ $platformKols->count() }} creator</span>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="w-full text-sm">
                            <thead>
                                <tr class="bg-gray-50 text-left">
                                    <th class="px-6 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wide">Creator</th>
                                    <th class="px-4 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wide">Type</th>
                                    <th class="px-4 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wide text-right">Views</th>
                                    <th class="px-4 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wide text-right">Likes</th>
                                    <th class="px-4 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wide text-right">Comments</th>
                                    <th class="px-4 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wide text-right">ER</th>
                                    <th class="px-4 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wide text-center">Status</th>
                                    <th class="px-4 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wide text-center">Post</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-50">
                                @foreach ($platformKols as $kol)
                                    @php
                                        $statusKol = match($kol->status) {
                                            'posted'    => ['label' => 'Posted',    'class' => 'bg-blue-100 text-blue-700'],
                                            'completed' => ['label' => 'Completed', 'class' => 'bg-green-100 text-green-700'],
                                            default     => ['label' => 'Pending',   'class' => 'bg-yellow-100 text-yellow-700'],
                                        };
                                    @endphp
                                    <tr class="hover:bg-gray-50/50 transition-colors">
                                        <td class="px-6 py-3.5">
                                            <div class="font-medium text-gray-900">{{ $kol->creator_name }}</div>
                                            @if ($kol->username)
                                                <div class="text-xs text-gray-400">@{{ $kol->username }}</div>
                                            @endif
                                        </td>
                                        <td class="px-4 py-3.5">
                                            <span class="text-xs text-gray-500 capitalize">{{ str_replace('_', ' ', $kol->content_type) }}</span>
                                        </td>
                                        <td class="px-4 py-3.5 text-right text-gray-700 tabular-nums">
                                            {{ $kol->views > 0 ? number_format($kol->views) : '—' }}
                                        </td>
                                        <td class="px-4 py-3.5 text-right text-gray-700 tabular-nums">
                                            {{ $kol->likes > 0 ? number_format($kol->likes) : '—' }}
                                        </td>
                                        <td class="px-4 py-3.5 text-right text-gray-700 tabular-nums">
                                            {{ $kol->comments > 0 ? number_format($kol->comments) : '—' }}
                                        </td>
                                        <td class="px-4 py-3.5 text-right tabular-nums">
                                            @if ($kol->engagement_rate > 0)
                                                <span class="text-green-600 font-medium">{{ number_format($kol->engagement_rate, 2) }}%</span>
                                            @else
                                                <span class="text-gray-300">—</span>
                                            @endif
                                        </td>
                                        <td class="px-4 py-3.5 text-center">
                                            <span class="inline-flex px-2 py-0.5 rounded-full text-xs font-semibold {{ $statusKol['class'] }}">
                                                {{ $statusKol['label'] }}
                                            </span>
                                        </td>
                                        <td class="px-4 py-3.5 text-center">
                                            @if ($kol->post_url)
                                                <a href="{{ $kol->post_url }}" target="_blank" rel="noopener noreferrer"
                                                   class="inline-flex items-center gap-1 text-xs text-indigo-600 hover:text-indigo-800 font-medium">
                                                    View
                                                    <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                              d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/>
                                                    </svg>
                                                </a>
                                            @else
                                                <span class="text-gray-300 text-xs">—</span>
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            @endforeach
        @else
            <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-12 text-center">
                <div class="text-4xl mb-3">📊</div>
                <p class="text-gray-500 text-sm">Data performance belum tersedia. Silakan kembali nanti.</p>
            </div>
        @endif

    </main>

    {{-- Footer --}}
    <footer class="mt-16 border-t border-gray-200 bg-white">
        <div class="max-w-5xl mx-auto px-4 sm:px-6 py-6 text-center text-xs text-gray-400">
            Laporan ini dibuat oleh <span class="font-semibold text-gray-500">Beyond Viral</span>
            &nbsp;·&nbsp; Data diperbarui secara berkala
        </div>
    </footer>

</body>
</html>
