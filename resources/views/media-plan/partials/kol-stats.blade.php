{{-- Statistik akun KOL untuk halaman review client. $kol = MediaPlanKol|null --}}
@php
    $dataKol   = $kol?->dataKol;
    $link      = collect((array) ($kol?->links ?? []))->filter()->first() ?: $dataKol?->link_userprofile;
    $followers = (int) ($kol?->followers ?: $dataKol?->followers ?: 0);
    $tier      = $kol?->tier ?: $dataKol?->tier;
    $er        = (float) ($kol?->er_percent ?: $dataKol?->engagement_rate ?: 0);
    $engage    = (int) ($kol?->engagement ?: $dataKol?->engagements ?: 0);
    $impress   = (int) ($kol?->impression ?: $dataKol?->impressions ?: 0);
    $channel   = $kol?->channel ?: $dataKol?->channel;

    $stats = array_filter([
        'Followers'         => $followers > 0 ? number_format($followers, 0, ',', '.') : null,
        'Tier'              => $tier ?: null,
        'ER'                => $er > 0 ? number_format($er, 2, ',', '.') . '%' : null,
        'Total Engagements' => $engage > 0 ? number_format($engage, 0, ',', '.') : null,
        'Avg Impressions'   => $impress > 0 ? number_format($impress, 0, ',', '.') : null,
    ]);
@endphp

@if ($link || $channel || $stats)
    <div class="mt-1 space-y-2">
        @if ($link || $channel)
            <div class="flex flex-wrap items-center gap-2 text-xs">
                @if ($channel)
                    <span class="inline-flex items-center px-2 py-0.5 rounded-full bg-gray-100 text-gray-600 font-medium">{{ $channel }}</span>
                @endif
                @if ($link)
                    <a href="{{ $link }}" target="_blank" rel="noopener noreferrer"
                       class="text-indigo-600 hover:text-indigo-800 hover:underline break-all">
                        {{ $link }} ↗
                    </a>
                @endif
            </div>
        @endif

        @if ($stats)
            <div class="flex flex-wrap gap-x-5 gap-y-1">
                @foreach ($stats as $label => $value)
                    <div>
                        <p class="text-[10px] uppercase tracking-wide text-gray-400 font-semibold">{{ $label }}</p>
                        <p class="text-xs font-semibold text-gray-800">{{ $value }}</p>
                    </div>
                @endforeach
            </div>
        @endif
    </div>
@endif
