@props(['columnId', 'record'])

@php
    $cardAction = $this->getBoard()->getCardAction();
    $hasCardAction = $cardAction !== null;
    $hasPositionIdentifier = $this->getBoard()->getPositionIdentifierAttribute() !== null;

    $model = $record['model'] ?? null;
    $commentCount = $model?->salesComments?->count() ?? 0;

    // Ambil data DataClient untuk Client Type & Agency
    $dataClient = null;
    if ($model && $model->company_name) {
        $dataClient = \App\Models\DataClient::where('nama_brand', $model->company_name)->first();
    }
    $clientType = $dataClient?->type;
    $agencyName = $dataClient?->agency_name;

    // Card left-border color berdasarkan kolom kanban
    $cardBorderColor = match ($columnId) {
        'briefing' => '#60a5fa', // blue-400
        'proposal_building' => '#fbbf24', // amber-400
        'negotiation' => '#c084fc', // purple-400
        'campaign_live' => '#818cf8', // indigo-400
        'reporting' => '#fb923c', // orange-400
        'close_lose' => '#f87171', // red-400
        'invoicing' => '#22d3ee', // cyan-400
        'paid' => '#4ade80', // green-400
        default => '#e5e7eb', // gray-200
    };
@endphp

<div @class([
    'flowforge-card group mb-1.5 rounded-md bg-white dark:bg-gray-900',
    'border border-gray-200 dark:border-gray-700/60',
    'transition-all duration-100',
    'hover:shadow-sm',
    'cursor-pointer' => $hasCardAction,
    'cursor-grab active:cursor-grabbing' => $hasPositionIdentifier && !$hasCardAction,
])
    style="border-left: 3px solid {{ $cardBorderColor }};" @if($hasPositionIdentifier) x-sortable-handle
    x-sortable-item="{{ $record['id'] }}" @endif data-card-id="{{ $record['id'] }}"
    data-position="{{ $record['position'] ?? '' }}" @if($hasCardAction && $cardAction)
    wire:click="mountAction('{{ $cardAction }}', [], @js(['recordKey' => $record['id']]))" @endif>
    <div class="px-3 pt-2.5 pb-2">

        {{-- Title row --}}
        <div class="flex items-start gap-1.5">
            <p class="text-[13px] font-medium text-gray-900 dark:text-gray-100 leading-snug flex-1">
                {{ $record['title'] }}
            </p>
        </div>

        {{-- Footer: badges + comment count --}}
        <div class="flex items-center justify-between mt-2 gap-1.5">
            <div class="flex items-center gap-1.5 min-w-0">
                @if($clientType === 'agency')
                    <span class="inline-flex items-center gap-1 text-[10px] font-medium text-amber-600 dark:text-amber-400">
                        <span class="w-1.5 h-1.5 rounded-full bg-amber-400 flex-shrink-0"></span>
                        Agency
                    </span>
                    @if($agencyName)
                        <span class="text-[10px] text-gray-400 dark:text-gray-500 truncate max-w-[90px]"
                            title="{{ $agencyName }}">
                            · {{ $agencyName }}
                        </span>
                    @endif
                @elseif($clientType === 'direct')
                    <span class="inline-flex items-center gap-1 text-[10px] font-medium text-sky-600 dark:text-sky-400">
                        <span class="w-1.5 h-1.5 rounded-full bg-sky-400 flex-shrink-0"></span>
                        Direct
                    </span>
                @endif
            </div>

            @if($commentCount > 0)
                <div class="flex items-center gap-0.5 text-gray-400 dark:text-gray-500 flex-shrink-0"
                    title="{{ $commentCount }} komentar">
                    <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round"
                            d="M8.625 12a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Zm0 0H8.25m4.125 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Zm0 0H12m4.125 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Zm0 0h-.375M21 12c0 4.556-4.03 8.25-9 8.25a9.764 9.764 0 0 1-2.555-.337A5.972 5.972 0 0 1 5.41 20.97a5.969 5.969 0 0 1-.474-.065 4.48 4.48 0 0 0 .978-2.025c.09-.457-.133-.901-.467-1.226C3.93 16.178 3 14.189 3 12c0-4.556 4.03-8.25 9-8.25s9 3.694 9 8.25Z" />
                    </svg>
                    <span class="text-[10px]">{{ $commentCount }}</span>
                </div>
            @endif
        </div>
    </div>
</div>