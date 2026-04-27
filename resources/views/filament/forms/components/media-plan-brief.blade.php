@php
    $record = $getRecord();
    $sales = $record?->bvSales;
    $brief = $sales?->formBrief;
    $histories = $sales?->briefHistories ?? collect();

    // Split history by type, latest first
    $linkHistories = $histories->where('type', 'link')->values();
    $fileHistories = $histories->where('type', 'file')->values();

    $hasNewerLinks = $linkHistories->isNotEmpty();
    $hasNewerFiles = $fileHistories->isNotEmpty();

    // Legacy/archive entries (from FormBrief + BvSales fields)
    $legacyLinks = collect([
        ['label' => 'Sheet Internal', 'url' => $brief?->sheet_link_internal],
        ['label' => 'Sheet External', 'url' => $brief?->sheet_link_external],
        ['label' => 'Brief Link', 'url' => $sales?->brief_link],
    ])->filter(fn ($l) => !empty($l['url']))->values();

    $legacyFiles = collect($brief?->attachments ?? [])
        ->merge($sales?->brief_files ?? [])
        ->unique()
        ->values();

    $mapAttachment = function ($path) {
        $ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));
        return [
            'path' => $path,
            'name' => basename($path),
            'ext' => $ext,
            'url' => route('brief-file.view', ['path' => $path]),
            'previewable' => in_array($ext, ['pdf', 'png', 'jpg', 'jpeg', 'webp', 'gif']),
        ];
    };

    // Show Sheet Links section if any link history OR legacy link exists
    $showLinkSection = $hasNewerLinks || $legacyLinks->isNotEmpty();
    // Show File section if any file history OR legacy file exists
    $showFileSection = $hasNewerFiles || $legacyFiles->isNotEmpty();

    $hasAnyContent = $brief || $showLinkSection || $showFileSection;
@endphp

<div x-data="{
        open: false,
        src: '',
        name: '',
        isPdf: false,
        sidebarWasOpen: false,
        openPreview(src, name, isPdf) {
            this.src = src;
            this.name = name;
            this.isPdf = isPdf;
            try {
                this.sidebarWasOpen = Alpine.store('sidebar')?.isOpen ?? false;
                Alpine.store('sidebar')?.close();
            } catch (e) {}
            this.open = true;
        },
        closePreview() {
            this.open = false;
            try {
                if (this.sidebarWasOpen) Alpine.store('sidebar')?.open();
            } catch (e) {}
        },
    }" class="space-y-4">
    @if (!$hasAnyContent)
        <div class="rounded-lg border border-dashed border-gray-300 dark:border-gray-700 p-6 text-center">
            <p class="text-gray-500 dark:text-gray-400">
                Belum ada brief yang terhubung dengan media plan ini.
            </p>
            @if ($record?->bv_sales_id)
                <p class="mt-1 text-xs text-gray-400">
                    Tambah brief baru dari Sales Activity terkait, lalu refresh halaman ini.
                </p>
            @endif
        </div>
    @else
        @if ($brief)
        <div class="rounded-lg border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-900 p-4">
            <div class="flex items-start justify-between gap-3">
                <div>
                    <h3 class="text-base font-semibold text-gray-900 dark:text-gray-100">
                        {{ $brief->title }}
                    </h3>
                    <p class="text-sm text-gray-500 dark:text-gray-400">
                        {{ $brief->brand ?? '-' }} · {{ $brief->campaign_name ?? '-' }}
                    </p>
                </div>
                <span @class([
                    'inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium',
                    'bg-gray-100 text-gray-700' => $brief->status === 'draft',
                    'bg-blue-100 text-blue-700' => $brief->status === 'submitted',
                    'bg-yellow-100 text-yellow-700' => $brief->status === 'reviewed',
                    'bg-green-100 text-green-700' => $brief->status === 'approved',
                    'bg-red-100 text-red-700' => $brief->status === 'revision',
                ])>
                    {{ $brief->status_label }}
                </span>
            </div>

            <dl class="mt-3 grid grid-cols-1 gap-2 text-sm sm:grid-cols-2">
                <div>
                    <dt class="text-gray-500 dark:text-gray-400">Timeline</dt>
                    <dd class="text-gray-900 dark:text-gray-100">{{ $brief->timeline ?? '-' }}</dd>
                </div>
                <div>
                    <dt class="text-gray-500 dark:text-gray-400">Deadline</dt>
                    <dd class="text-gray-900 dark:text-gray-100">{{ $brief->deadline ?? '-' }}</dd>
                </div>
                <div>
                    <dt class="text-gray-500 dark:text-gray-400">PIC</dt>
                    <dd class="text-gray-900 dark:text-gray-100">{{ $brief->pic ?? '-' }}</dd>
                </div>
                <div>
                    <dt class="text-gray-500 dark:text-gray-400">Client Status</dt>
                    <dd class="text-gray-900 dark:text-gray-100">{{ $brief->client_status ?? '-' }}</dd>
                </div>
            </dl>

            @if ($brief->campaign_objective)
                <div class="mt-3">
                    <p class="text-xs font-medium text-gray-500 dark:text-gray-400">Campaign Objective</p>
                    <p class="mt-1 text-sm text-gray-900 dark:text-gray-100 whitespace-pre-wrap">{{ $brief->campaign_objective }}</p>
                </div>
            @endif
        </div>
        @endif

        {{-- Sheet Links History --}}
        @if ($showLinkSection)
        <div class="rounded-lg border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-900 p-4">
            <h4 class="text-sm font-semibold text-gray-900 dark:text-gray-100 mb-4">🔗 Sheet Links</h4>

            @php $linkTotal = $linkHistories->count() + $legacyLinks->count(); $linkIdx = 0; @endphp
            <ol class="relative ml-3 border-s-2 border-gray-200 dark:border-gray-700 space-y-5">
                @foreach ($linkHistories as $h)
                    @php $linkIdx++; $isLast = $linkIdx === $linkTotal; @endphp
                    <li class="ms-5 relative">
                        <span class="absolute -start-[27px] top-1.5 flex h-3 w-3 items-center justify-center rounded-full bg-primary-500 ring-4 ring-white dark:ring-gray-900"></span>
                        <div class="flex items-start justify-between gap-3">
                            <div class="min-w-0 flex-1 space-y-1.5" style="text-align: left;">
                                <a href="{{ $h->link_url }}" target="_blank" rel="noopener"
                                   class="inline-flex items-center gap-1.5 text-sm font-medium text-primary-700 hover:underline dark:text-primary-300 break-all">
                                    <x-heroicon-m-arrow-top-right-on-square class="h-4 w-4 shrink-0" />
                                    <span class="truncate">{{ $h->link_url }}</span>
                                </a>
                                @include('filament.forms.components._brief-meta', ['createdAt' => $h->created_at, 'uploaderName' => $h->uploader?->name])
                                @if ($h->notes)
                                    <div class="text-sm text-gray-700 dark:text-gray-300 mt-1"><span class="font-semibold">Note:</span> <span class="whitespace-pre-wrap">{{ $h->notes }}</span></div>
                                @endif
                            </div>
                        </div>
                    </li>
                @endforeach

                @foreach ($legacyLinks as $link)
                    @php $linkIdx++; @endphp
                    <li class="ms-5 relative">
                        <span class="absolute -start-[27px] top-1.5 flex h-3 w-3 items-center justify-center rounded-full bg-gray-300 ring-4 ring-white dark:bg-gray-600 dark:ring-gray-900"></span>
                        <div class="flex items-start justify-between gap-3">
                            <div class="min-w-0 flex-1 space-y-1.5" style="text-align: left;">
                                <a href="{{ $link['url'] }}" target="_blank" rel="noopener"
                                   class="inline-flex items-center gap-1.5 text-sm font-medium text-gray-600 hover:underline dark:text-gray-300 break-all">
                                    <x-heroicon-m-arrow-top-right-on-square class="h-4 w-4 shrink-0" />
                                    <span class="truncate">{{ $link['label'] }} — {{ $link['url'] }}</span>
                                </a>
                                @include('filament.forms.components._brief-meta', ['isArchive' => true])
                            </div>
                        </div>
                    </li>
                @endforeach
            </ol>
        </div>
        @endif

        {{-- File Attachments History --}}
        @if ($showFileSection)
        <div class="rounded-lg border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-900 p-4">
            <h4 class="text-sm font-semibold text-gray-900 dark:text-gray-100 mb-4">📎 Lampiran Dokumen</h4>

            <ol class="relative ml-3 border-s-2 border-gray-200 dark:border-gray-700 space-y-5">
                @foreach ($fileHistories as $h)
                    @php $att = $mapAttachment($h->file_path); @endphp
                    <li class="ms-5 relative">
                        <span class="absolute -start-[27px] top-1.5 flex h-3 w-3 items-center justify-center rounded-full bg-primary-500 ring-4 ring-white dark:ring-gray-900"></span>
                        <div class="flex items-start justify-between gap-3">
                            <div class="min-w-0 flex-1 space-y-1.5" style="text-align: left;">
                                <div class="flex items-center gap-2 min-w-0">
                                    <x-heroicon-o-document class="h-5 w-5 text-gray-400 shrink-0" />
                                    <span class="text-sm font-medium text-gray-900 dark:text-gray-100 truncate">{{ $att['name'] }}</span>
                                    <span class="text-xs uppercase text-gray-400">.{{ $att['ext'] }}</span>
                                </div>
                                @include('filament.forms.components._brief-meta', ['createdAt' => $h->created_at, 'uploaderName' => $h->uploader?->name])
                                @if ($h->notes)
                                    <div class="text-sm text-gray-700 dark:text-gray-300 mt-1"><span class="font-semibold">Note:</span> <span class="whitespace-pre-wrap">{{ $h->notes }}</span></div>
                                @endif
                            </div>
                            <div class="flex items-center gap-1 shrink-0">
                                @if ($att['previewable'])
                                    <button type="button"
                                            @click="openPreview('{{ $att['url'] }}', '{{ $att['name'] }}', {{ $att['ext'] === 'pdf' ? 'true' : 'false' }})"
                                            class="inline-flex items-center justify-center rounded-md p-1.5 text-info-600 hover:bg-info-50 dark:hover:bg-info-500/10"
                                            title="Preview">
                                        <x-heroicon-m-eye class="h-5 w-5" />
                                    </button>
                                @endif
                                <a href="{{ $att['url'] }}" target="_blank" rel="noopener"
                                   class="inline-flex items-center justify-center rounded-md p-1.5 text-gray-600 hover:bg-gray-50 dark:text-gray-300 dark:hover:bg-gray-800"
                                   title="Download">
                                    <x-heroicon-m-arrow-down-tray class="h-5 w-5" />
                                </a>
                            </div>
                        </div>
                    </li>
                @endforeach

                @foreach ($legacyFiles as $path)
                    @php $att = $mapAttachment($path); @endphp
                    <li class="ms-5 relative">
                        <span class="absolute -start-[27px] top-1.5 flex h-3 w-3 items-center justify-center rounded-full bg-gray-300 ring-4 ring-white dark:bg-gray-600 dark:ring-gray-900"></span>
                        <div class="flex items-start justify-between gap-3">
                            <div class="min-w-0 flex-1 space-y-1.5" style="text-align: left;">
                                <div class="flex items-center gap-2 min-w-0">
                                    <x-heroicon-o-document class="h-5 w-5 text-gray-400 shrink-0" />
                                    <span class="text-sm text-gray-900 dark:text-gray-100 truncate">{{ $att['name'] }}</span>
                                    <span class="text-xs uppercase text-gray-400">.{{ $att['ext'] }}</span>
                                </div>
                                @include('filament.forms.components._brief-meta', ['isArchive' => true])
                            </div>
                            <div class="flex items-center gap-1 shrink-0">
                                @if ($att['previewable'])
                                    <button type="button"
                                            @click="openPreview('{{ $att['url'] }}', '{{ $att['name'] }}', {{ $att['ext'] === 'pdf' ? 'true' : 'false' }})"
                                            class="inline-flex items-center justify-center rounded-md p-1.5 text-info-600 hover:bg-info-50 dark:hover:bg-info-500/10"
                                            title="Preview">
                                        <x-heroicon-m-eye class="h-5 w-5" />
                                    </button>
                                @endif
                                <a href="{{ $att['url'] }}" target="_blank" rel="noopener"
                                   class="inline-flex items-center justify-center rounded-md p-1.5 text-gray-600 hover:bg-gray-50 dark:text-gray-300 dark:hover:bg-gray-800"
                                   title="Download">
                                    <x-heroicon-m-arrow-down-tray class="h-5 w-5" />
                                </a>
                            </div>
                        </div>
                    </li>
                @endforeach
            </ol>
        </div>
        @endif
    @endif

    {{-- Preview Modal --}}
    <div x-show="open" x-cloak
         x-transition.opacity
         @keydown.escape.window="closePreview()"
         class="fixed inset-0 z-50 flex items-center justify-center bg-black/60 p-4"
         @click.self="closePreview()">
        <div class="bg-white dark:bg-gray-900 rounded-lg shadow-xl w-full max-w-5xl h-[90vh] flex flex-col overflow-hidden">
            <div class="flex items-center justify-between border-b border-gray-200 dark:border-gray-700 px-4 py-2">
                <p class="text-sm font-medium text-gray-900 dark:text-gray-100 truncate" x-text="name"></p>
                <button type="button" @click="closePreview()"
                        class="rounded-md p-1 text-gray-500 hover:bg-gray-100 dark:hover:bg-gray-800">
                    <x-heroicon-m-x-mark class="h-5 w-5" />
                </button>
            </div>
            <div class="flex-1 bg-gray-50 dark:bg-gray-950 overflow-hidden">
                <template x-if="isPdf">
                    <iframe :src="src" class="w-full h-full" frameborder="0"></iframe>
                </template>
                <template x-if="!isPdf">
                    <div class="w-full h-full flex items-center justify-center p-4 overflow-auto">
                        <img :src="src" :alt="name" class="max-w-full max-h-full object-contain" />
                    </div>
                </template>
            </div>
        </div>
    </div>
</div>
