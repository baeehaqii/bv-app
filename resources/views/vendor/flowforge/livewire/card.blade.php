@props(['columnId', 'record'])

@php
    $cardAction = $this->getBoard()->getCardAction();
    $hasCardAction = $cardAction !== null;
    $hasPositionIdentifier = $this->getBoard()->getPositionIdentifierAttribute() !== null;

    $model = $record['model'] ?? null;
    $isDataClient = $model instanceof \App\Models\DataClient;

    // ── DataClient card vars ─────────────────────────────────────────────────
    if ($isDataClient) {
        $clientType = $model->type;
        $agencyName = $model->agency_name;
        if (is_array($agencyName)) {
            $agencyName = implode(', ', array_filter($agencyName));
        }
        $priority     = $model->priority;
        $picName      = $model->picInternalSales?->nama_sales;
        $category     = $model->category;
        $dateOutreach = $model->date_outreach ? \Carbon\Carbon::parse($model->date_outreach)->format('d M Y') : null;

        $cardBorderColor = match ($columnId) {
            'Newest'           => '#3b82f6', // blue-500
            'Number of Meeting'=> '#6366f1', // indigo-500
            'Brief'            => '#eab308', // yellow-500
            'Waiting Feedback' => '#f97316', // orange-500
            'On Going'         => '#22c55e', // green-500
            default            => '#9ca3af', // gray-400
        };

        [$chipBg, $chipColor] = match ($clientType) {
            'agency'  => ['#ede9fe', '#5b21b6'],
            default   => ['#dbeafe', '#1e40af'],
        };

        $priorityColors = [
            'P0' => ['#fee2e2', '#991b1b'],
            'P1' => ['#ffedd5', '#9a3412'],
            'P2' => ['#fef3c7', '#92400e'],
            'P3' => ['#f0fdf4', '#166534'],
        ];
        [$priBg, $priColor] = $priorityColors[$priority] ?? ['#f3f4f6', '#374151'];

    // ── BvSales / default card vars ──────────────────────────────────────────
    } else {
        $commentCount = $model?->salesComments?->count() ?? 0;

        $dataClient = null;
        if ($model && $model->company_name) {
            $dataClient = \App\Models\DataClient::where('nama_brand', $model->company_name)->first();
        }
        $clientType = $dataClient?->type;
        $agencyName = $dataClient?->agency_name;
        if (is_array($agencyName)) {
            $agencyName = implode(', ', array_filter($agencyName));
        }

        $briefUrl     = $model?->brief_link ?: $model?->formBrief?->sheet_link_external ?: null;
        $briefFormUrl = $model?->formBrief?->getPublicUrl();

        $isBriefingColumn = $columnId === 'briefing';
        $iconHref     = null;
        $iconColor    = '#94a3b8';
        $iconHoverBg  = null;
        $iconTitle    = 'Form Brief belum tersedia';

        if ($isBriefingColumn && $briefUrl) {
            $iconHref    = $briefUrl;
            $iconColor   = '#16a34a';
            $iconHoverBg = '#f0fdf4';
            $iconTitle   = 'Brief URL tersedia';
        } elseif ($isBriefingColumn && $briefFormUrl) {
            $iconHref    = $briefFormUrl;
            $iconColor   = '#2563eb';
            $iconHoverBg = '#eff6ff';
            $iconTitle   = 'Buka Form Brief Client';
        } elseif (!$isBriefingColumn && ($briefUrl || $briefFormUrl)) {
            $iconColor = '#f59e0b';
            $iconTitle = 'Link brief hanya bisa dibuka di kolom Briefing';
        }

        $cardBorderColor = match ($columnId) {
            'briefing'          => '#eab308',
            'proposal_building' => '#a855f7',
            'negotiation'       => '#3b82f6',
            'campaign_live'     => '#22c55e',
            'reporting'         => '#f59e0b',
            'invoicing'         => '#ef4444',
            'paid'              => '#22c55e',
            default             => '#9ca3af',
        };

        [$chipBg, $chipColor] = match ($columnId) {
            'briefing'          => ['#fef3c7', '#92400e'],
            'proposal_building' => ['#f3e8ff', '#6b21a8'],
            'negotiation'       => ['#dbeafe', '#1e40af'],
            'campaign_live'     => ['#dcfce7', '#14532d'],
            'reporting'         => ['#fef3c7', '#78350f'],
            'invoicing'         => ['#fee2e2', '#991b1b'],
            'paid'              => ['#d1fae5', '#065f46'],
            default             => ['#f3f4f6', '#374151'],
        };
    }
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

        {{-- Schema (if any) --}}
        @if(filled($record['schema']))
            <div class="px-1">
                {{ $record['schema'] }}
            </div>
        @endif

        {{-- ── DataClient footer ───────────────────────────────────────────── --}}
        @if($isDataClient)
            <div class="flex items-center justify-between mt-2 gap-1.5">
                <div class="flex items-center gap-1.5 min-w-0 flex-wrap">
                    {{-- Type chip --}}
                    <span class="inline-flex items-center px-1.5 py-0.5 rounded-full text-[10px] font-medium leading-none flex-shrink-0"
                        style="background-color: {{ $chipBg }}; color: {{ $chipColor }};">
                        {{ $clientType === 'agency' ? 'Agency' : 'Direct' }}
                    </span>
                    {{-- Category --}}
                    @if($category)
                        <span class="text-[10px] text-gray-400 truncate max-w-[90px]" title="{{ $category }}">
                            {{ $category }}
                        </span>
                    @endif
                </div>
                {{-- Right: priority + PIC --}}
                <div class="flex items-center gap-1.5 flex-shrink-0">
                    @if($priority)
                        <span class="inline-flex items-center px-1.5 py-0.5 rounded-full text-[10px] font-medium leading-none"
                            style="background-color: {{ $priBg }}; color: {{ $priColor }};">
                            {{ $priority }}
                        </span>
                    @endif
                    @if($picName)
                        <span class="text-[10px] text-gray-400 truncate max-w-[70px]" title="{{ $picName }}">
                            {{ $picName }}
                        </span>
                    @endif
                </div>
            </div>
            @if($dateOutreach)
                <div class="mt-1 text-[10px] text-gray-400">
                    Outreach: {{ $dateOutreach }}
                </div>
            @endif

        {{-- ── BvSales footer ──────────────────────────────────────────────── --}}
        @else
            <div class="flex items-center justify-between mt-2 gap-1.5">
                <div class="flex items-center gap-1.5 min-w-0">
                    {{-- Brief URL icon --}}
                    @if($iconHref)
                        <a href="{{ $iconHref }}" target="_blank" rel="noopener noreferrer" wire:click.stop
                            class="inline-flex items-center justify-center w-5 h-5 rounded-full transition-colors duration-150 flex-shrink-0"
                            style="color: {{ $iconColor }};"
                            @if($iconHoverBg) onmouseover="this.style.backgroundColor='{{ $iconHoverBg }}'" onmouseout="this.style.backgroundColor='transparent'" @endif
                            title="{{ $iconTitle }}">
                            <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M13.19 8.688a4.5 4.5 0 0 1 1.242 7.244l-4.5 4.5a4.5 4.5 0 0 1-6.364-6.364l1.757-1.757m13.35-.622 1.757-1.757a4.5 4.5 0 0 0-6.364-6.364l-4.5 4.5a4.5 4.5 0 0 0 1.242 7.244" />
                            </svg>
                        </a>
                    @else
                        <span class="inline-flex items-center justify-center w-5 h-5 rounded-full flex-shrink-0"
                            style="color: {{ $iconColor }};"
                            title="{{ $iconTitle }}">
                            <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M13.19 8.688a4.5 4.5 0 0 1 1.242 7.244l-4.5 4.5a4.5 4.5 0 0 1-6.364-6.364l1.757-1.757m13.35-.622 1.757-1.757a4.5 4.5 0 0 0-6.364-6.364l-4.5 4.5a4.5 4.5 0 0 0 1.242 7.244" />
                            </svg>
                        </span>
                    @endif

                    {{-- Client type chip --}}
                    @if($clientType)
                        <span class="inline-flex items-center px-1.5 py-0.5 rounded-full text-[10px] font-medium leading-none flex-shrink-0"
                            style="background-color: {{ $chipBg }}; color: {{ $chipColor }};">
                            {{ ucfirst($clientType) }}
                        </span>
                        @if($agencyName)
                            <span class="text-[10px] text-gray-400 truncate max-w-[80px]" title="{{ $agencyName }}">
                                · {{ $agencyName }}
                            </span>
                        @endif
                    @endif
                </div>

                {{-- Comment count --}}
                @if($commentCount > 0)
                    <div class="flex items-center gap-0.5 text-gray-400 flex-shrink-0" title="{{ $commentCount }} komentar">
                        <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                d="M8.625 12a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Zm0 0H8.25m4.125 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Zm0 0H12m4.125 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Zm0 0h-.375M21 12c0 4.556-4.03 8.25-9 8.25a9.764 9.764 0 0 1-2.555-.337A5.972 5.972 0 0 1 5.41 20.97a5.969 5.969 0 0 1-.474-.065 4.48 4.48 0 0 0 .978-2.025c.09-.457-.133-.901-.467-1.226C3.93 16.178 3 14.189 3 12c0-4.556 4.03-8.25 9-8.25s9 3.694 9 8.25Z" />
                        </svg>
                        <span class="text-[10px]">{{ $commentCount }}</span>
                    </div>
                @endif
            </div>
        @endif
    </div>
</div>