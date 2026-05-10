@php
    $record = $getRecord();
    $sales  = $record?->bvSales;
    $brief  = $sales?->formBrief;
    $histories = $sales?->briefHistories ?? collect();

    $linkHistories = $histories->where('type', 'link')->values();
    $fileHistories = $histories->where('type', 'file')->values();

    // All links: from history + legacy fields
    $allLinks = collect();
    foreach ($linkHistories as $h) {
        $allLinks->push(['label' => 'Brief Link', 'url' => $h->link_url, 'notes' => $h->notes, 'created_at' => $h->created_at, 'uploader' => $h->uploader?->name, 'archive' => false]);
    }
    foreach ([
        ['label' => 'Sheet Internal', 'url' => $brief?->sheet_link_internal],
        ['label' => 'Sheet External', 'url' => $brief?->sheet_link_external],
        ['label' => 'Brief Link (Sales)', 'url' => $sales?->brief_link],
    ] as $l) {
        if (!empty($l['url'])) $allLinks->push(['label' => $l['label'], 'url' => $l['url'], 'notes' => null, 'created_at' => null, 'uploader' => null, 'archive' => true]);
    }

    // All files: from history + legacy fields
    $allFilePaths = collect();
    foreach ($fileHistories as $h) {
        $allFilePaths->push(['path' => $h->file_path, 'notes' => $h->notes, 'created_at' => $h->created_at, 'uploader' => $h->uploader?->name, 'archive' => false]);
    }
    foreach (collect($brief?->attachments ?? [])->merge($sales?->brief_files ?? [])->unique() as $path) {
        $allFilePaths->push(['path' => $path, 'notes' => null, 'created_at' => null, 'uploader' => null, 'archive' => true]);
    }

    $mapAttachment = function ($item) {
        $path = is_string($item) ? $item : $item['path'];
        $ext  = strtolower(pathinfo($path, PATHINFO_EXTENSION));
        return array_merge(is_array($item) ? $item : [], [
            'path'        => $path,
            'name'        => basename($path),
            'ext'         => $ext,
            'url'         => route('brief-file.view', ['path' => $path]),
            'previewable' => in_array($ext, ['pdf', 'png', 'jpg', 'jpeg', 'webp', 'gif']),
        ]);
    };

    $allFiles = $allFilePaths->map($mapAttachment);

    $hasBrief      = (bool) $brief;
    $hasLinks      = $allLinks->isNotEmpty();
    $hasFiles      = $allFiles->isNotEmpty();
    $hasAnyContent = $hasBrief || $hasLinks || $hasFiles;

    // Budget format helper
    $fmtBudget = fn($v) => $v ? 'Rp ' . number_format((float) preg_replace('/[^0-9.]/', '', $v), 0, ',', '.') : null;
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
    }" class="space-y-5">

    {{-- Empty State --}}
    @if (!$hasAnyContent)
        <div class="rounded-2xl border-2 border-dashed border-gray-200 dark:border-gray-700 py-12 text-center">
            <x-heroicon-o-document-text class="mx-auto h-12 w-12 text-gray-300 dark:text-gray-600" />
            <p class="mt-3 text-sm font-medium text-gray-500 dark:text-gray-400">Belum ada brief yang terhubung</p>
            @if ($record?->bv_sales_id)
                <p class="mt-1 text-xs text-gray-400 dark:text-gray-600">Tambah brief dari Sales Activity terkait, lalu refresh halaman ini.</p>
            @endif
        </div>
    @else

        {{-- ─── BRIEF HEADER CARD ──────────────────────────────────────────── --}}
        @if ($hasBrief)
        <div x-data="{ briefOpen: false }" class="overflow-hidden rounded-2xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-900 shadow-sm">

            {{-- Gradient stripe --}}
            <div class="h-1.5 w-full bg-gradient-to-r from-violet-500 via-purple-500 to-indigo-500"></div>

            {{-- Clickable header (always visible) --}}
            <button type="button" @click="briefOpen = !briefOpen"
                    class="w-full px-6 py-5 text-left focus:outline-none">
                <div class="flex flex-wrap items-start justify-between gap-3">
                    <div>
                        <p class="text-xs font-medium uppercase tracking-widest text-gray-400 dark:text-gray-500 mb-1">Form Brief</p>
                        <h2 class="text-xl font-bold text-gray-900 dark:text-gray-100 leading-tight">{{ $brief->title }}</h2>
                        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                            {{ $brief->brand ?? '-' }}
                            @if ($brief->campaign_name)
                                <span class="mx-1 text-gray-300 dark:text-gray-600">·</span>
                                {{ $brief->campaign_name }}
                            @endif
                        </p>
                    </div>
                    <div class="flex items-center gap-2 shrink-0">
                        <span @class([
                            'inline-flex items-center gap-1.5 rounded-full px-3 py-1 text-xs font-semibold',
                            'bg-gray-100 text-gray-700 dark:bg-gray-800 dark:text-gray-300' => $brief->status === 'draft',
                            'bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-300' => $brief->status === 'submitted',
                            'bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-300' => $brief->status === 'reviewed',
                            'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-300' => $brief->status === 'approved',
                            'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-300' => $brief->status === 'revision',
                        ])>
                            <span class="h-1.5 w-1.5 rounded-full {{ match($brief->status) {
                                'submitted' => 'bg-blue-500',
                                'reviewed'  => 'bg-amber-500',
                                'approved'  => 'bg-green-500',
                                'revision'  => 'bg-red-500',
                                default     => 'bg-gray-400',
                            } }}"></span>
                            {{ $brief->status_label }}
                        </span>
                        <x-heroicon-m-chevron-down class="h-5 w-5 text-gray-400 transition-transform duration-200"
                            ::class="{ 'rotate-180': briefOpen }" />
                    </div>
                </div>

                {{-- Submission info (always visible) --}}
                @if ($brief->submitted_by_name || $brief->submitted_at)
                <div class="mt-3 flex flex-wrap items-center gap-2">
                    @if ($brief->submitted_by_name)
                        <span class="inline-flex items-center gap-1 rounded-full bg-violet-50 dark:bg-violet-900/20 px-2.5 py-0.5 text-xs font-medium text-violet-700 dark:text-violet-300">
                            <x-heroicon-m-user class="h-3 w-3" />
                            Disubmit oleh: {{ $brief->submitted_by_name }}
                            @if ($brief->submitted_by_email)
                                <span class="text-violet-400">({{ $brief->submitted_by_email }})</span>
                            @endif
                        </span>
                    @endif
                    @if ($brief->submitted_at)
                        <span class="inline-flex items-center gap-1 rounded-full bg-gray-100 dark:bg-gray-800 px-2.5 py-0.5 text-xs font-medium text-gray-600 dark:text-gray-400">
                            <x-heroicon-m-clock class="h-3 w-3" />
                            {{ $brief->submitted_at->format('d M Y, H:i') }}
                        </span>
                    @endif
                </div>
                @endif
            </button>

            {{-- Collapsible body --}}
            <div x-show="briefOpen" x-collapse>

            {{-- ─── Info Grid ──────────────────────────────────────────────── --}}
            <div class="border-t border-gray-100 dark:border-gray-800 px-6 py-5">
                <div class="grid grid-cols-2 gap-x-6 gap-y-4 sm:grid-cols-4">
                    <div>
                        <p class="text-[11px] font-semibold uppercase tracking-widest text-gray-400 dark:text-gray-500">Deadline</p>
                        <p class="mt-1 text-sm font-medium text-gray-900 dark:text-gray-100">{{ $brief->deadline ?: '—' }}</p>
                    </div>
                    <div>
                        <p class="text-[11px] font-semibold uppercase tracking-widest text-gray-400 dark:text-gray-500">Timeline</p>
                        <p class="mt-1 text-sm font-medium text-gray-900 dark:text-gray-100">{{ $brief->timeline ?: '—' }}</p>
                    </div>
                    <div>
                        <p class="text-[11px] font-semibold uppercase tracking-widest text-gray-400 dark:text-gray-500">PIC Client</p>
                        <p class="mt-1 text-sm font-medium text-gray-900 dark:text-gray-100">{{ $brief->pic ?: '—' }}</p>
                    </div>
                    <div>
                        <p class="text-[11px] font-semibold uppercase tracking-widest text-gray-400 dark:text-gray-500">Client Status</p>
                        <p class="mt-1 text-sm font-medium text-gray-900 dark:text-gray-100">{{ $brief->client_status ?: '—' }}</p>
                    </div>
                </div>
            </div>

            {{-- ─── Campaign Objective ─────────────────────────────────────── --}}
            @if ($brief->campaign_objective)
            <div class="border-t border-gray-100 dark:border-gray-800 px-6 py-5">
                <p class="text-[11px] font-semibold uppercase tracking-widest text-gray-400 dark:text-gray-500 mb-2">Campaign Objective</p>
                <p class="text-sm text-gray-800 dark:text-gray-200 whitespace-pre-wrap leading-relaxed">{{ $brief->campaign_objective }}</p>
            </div>
            @endif

            {{-- ─── Criteria of KOL ────────────────────────────────────────── --}}
            @if ($brief->criteria_of_kol)
            <div class="border-t border-gray-100 dark:border-gray-800 px-6 py-5">
                <p class="text-[11px] font-semibold uppercase tracking-widest text-gray-400 dark:text-gray-500 mb-2">Criteria of KOL</p>
                <div class="prose prose-sm dark:prose-invert max-w-none text-gray-700 dark:text-gray-300 leading-relaxed brief-content">
                    {!! $brief->criteria_of_kol !!}
                </div>
            </div>
            @endif

            {{-- ─── SOW ────────────────────────────────────────────────────── --}}
            @if ($brief->sow)
            <div class="border-t border-gray-100 dark:border-gray-800 px-6 py-5">
                <p class="text-[11px] font-semibold uppercase tracking-widest text-gray-400 dark:text-gray-500 mb-2">Scope of Work (SOW)</p>
                <div class="prose prose-sm dark:prose-invert max-w-none text-gray-700 dark:text-gray-300 leading-relaxed brief-content">
                    {!! $brief->sow !!}
                </div>
            </div>
            @endif

            {{-- ─── Budget ─────────────────────────────────────────────────── --}}
            @if ($brief->budget_main_kol || $brief->budget_macro_kol)
            <div class="border-t border-gray-100 dark:border-gray-800 px-6 py-5">
                <p class="text-[11px] font-semibold uppercase tracking-widest text-gray-400 dark:text-gray-500 mb-3">Budget</p>
                <div class="flex flex-wrap gap-3">
                    @if ($brief->budget_main_kol)
                    <div class="flex items-center gap-2 rounded-xl bg-violet-50 dark:bg-violet-900/20 px-4 py-2.5">
                        <x-heroicon-m-currency-dollar class="h-5 w-5 text-violet-500" />
                        <div>
                            <p class="text-[10px] font-semibold uppercase tracking-widest text-violet-400">Main KOL</p>
                            <p class="text-sm font-bold text-violet-700 dark:text-violet-300">{{ $fmtBudget($brief->budget_main_kol) ?? $brief->budget_main_kol }}</p>
                        </div>
                    </div>
                    @endif
                    @if ($brief->budget_macro_kol)
                    <div class="flex items-center gap-2 rounded-xl bg-indigo-50 dark:bg-indigo-900/20 px-4 py-2.5">
                        <x-heroicon-m-currency-dollar class="h-5 w-5 text-indigo-500" />
                        <div>
                            <p class="text-[10px] font-semibold uppercase tracking-widest text-indigo-400">Macro KOL</p>
                            <p class="text-sm font-bold text-indigo-700 dark:text-indigo-300">{{ $fmtBudget($brief->budget_macro_kol) ?? $brief->budget_macro_kol }}</p>
                        </div>
                    </div>
                    @endif
                </div>
            </div>
            @endif

            {{-- ─── Additional Notes ───────────────────────────────────────── --}}
            @if ($brief->additional_notes)
            <div class="border-t border-gray-100 dark:border-gray-800 px-6 py-5">
                <p class="text-[11px] font-semibold uppercase tracking-widest text-gray-400 dark:text-gray-500 mb-2">Catatan Tambahan</p>
                <p class="text-sm text-gray-700 dark:text-gray-300 whitespace-pre-wrap leading-relaxed">{{ $brief->additional_notes }}</p>
            </div>
            @endif

            {{-- ─── Review Notes ───────────────────────────────────────────── --}}
            @if ($brief->review_notes)
            <div class="border-t border-amber-100 dark:border-amber-900/30 bg-amber-50 dark:bg-amber-900/10 px-6 py-4">
                <p class="text-[11px] font-semibold uppercase tracking-widest text-amber-600 dark:text-amber-400 mb-1">Catatan Review</p>
                <p class="text-sm text-amber-800 dark:text-amber-200 whitespace-pre-wrap leading-relaxed">{{ $brief->review_notes }}</p>
            </div>
            @endif

            </div>{{-- end x-collapse body --}}
        </div>
        @endif

        {{-- ─── LINKS ──────────────────────────────────────────────────────── --}}
        @if ($hasLinks)
        <div class="overflow-hidden rounded-2xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-900 shadow-sm">
            <div class="flex items-center gap-2 border-b border-gray-100 dark:border-gray-800 px-6 py-4">
                <x-heroicon-m-link class="h-5 w-5 text-blue-500" />
                <h3 class="text-sm font-semibold text-gray-900 dark:text-gray-100">Brief Links</h3>
                <span class="ml-auto inline-flex items-center rounded-full bg-blue-50 dark:bg-blue-900/20 px-2 py-0.5 text-xs font-medium text-blue-700 dark:text-blue-300">{{ $allLinks->count() }}</span>
            </div>
            <div class="divide-y divide-gray-100 dark:divide-gray-800">
                @foreach ($allLinks as $link)
                <div class="flex items-start gap-4 px-6 py-4">
                    <div class="mt-0.5 flex h-8 w-8 shrink-0 items-center justify-center rounded-lg {{ $link['archive'] ? 'bg-gray-100 dark:bg-gray-800' : 'bg-blue-50 dark:bg-blue-900/20' }}">
                        <x-heroicon-m-arrow-top-right-on-square class="h-4 w-4 {{ $link['archive'] ? 'text-gray-400' : 'text-blue-500' }}" />
                    </div>
                    <div class="min-w-0 flex-1">
                        <div class="flex flex-wrap items-center gap-2">
                            <span class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">{{ $link['label'] }}</span>
                            @if ($link['archive'])
                                <span class="inline-flex items-center gap-1 rounded-full bg-amber-50 dark:bg-amber-900/20 px-1.5 py-0.5 text-[10px] font-medium text-amber-600 dark:text-amber-400">
                                    <x-heroicon-m-archive-box class="h-2.5 w-2.5" />archive
                                </span>
                            @endif
                            @if ($link['created_at'])
                                <span class="text-[11px] text-gray-400 dark:text-gray-500">{{ $link['created_at']->format('d M Y · H:i') }}</span>
                            @endif
                            @if ($link['uploader'])
                                <span class="text-[11px] text-gray-400 dark:text-gray-500">oleh {{ $link['uploader'] }}</span>
                            @endif
                        </div>
                        <a href="{{ $link['url'] }}" target="_blank" rel="noopener"
                           class="mt-1 inline-flex max-w-full items-center gap-1 truncate text-sm font-medium text-blue-600 hover:text-blue-800 hover:underline dark:text-blue-400 break-all">
                            {{ $link['url'] }}
                        </a>
                        @if ($link['notes'])
                            <p class="mt-1 text-xs text-gray-500 dark:text-gray-400 whitespace-pre-wrap">{{ $link['notes'] }}</p>
                        @endif
                    </div>
                </div>
                @endforeach
            </div>
        </div>
        @endif

        {{-- ─── FILE ATTACHMENTS ───────────────────────────────────────────── --}}
        @if ($hasFiles)
        <div class="overflow-hidden rounded-2xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-900 shadow-sm">
            <div class="flex items-center gap-2 border-b border-gray-100 dark:border-gray-800 px-6 py-4">
                <x-heroicon-m-paper-clip class="h-5 w-5 text-emerald-500" />
                <h3 class="text-sm font-semibold text-gray-900 dark:text-gray-100">Lampiran Dokumen</h3>
                <span class="ml-auto inline-flex items-center rounded-full bg-emerald-50 dark:bg-emerald-900/20 px-2 py-0.5 text-xs font-medium text-emerald-700 dark:text-emerald-300">{{ $allFiles->count() }}</span>
            </div>
            <div class="divide-y divide-gray-100 dark:divide-gray-800">
                @foreach ($allFiles as $file)
                <div class="flex items-center gap-4 px-6 py-4">
                    <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg {{ $file['archive'] ? 'bg-gray-100 dark:bg-gray-800' : 'bg-emerald-50 dark:bg-emerald-900/20' }}">
                        @if (in_array($file['ext'], ['jpg','jpeg','png','gif','webp']))
                            <x-heroicon-m-photo class="h-5 w-5 {{ $file['archive'] ? 'text-gray-400' : 'text-emerald-500' }}" />
                        @elseif ($file['ext'] === 'pdf')
                            <x-heroicon-m-document class="h-5 w-5 {{ $file['archive'] ? 'text-gray-400' : 'text-red-500' }}" />
                        @else
                            <x-heroicon-m-document-text class="h-5 w-5 {{ $file['archive'] ? 'text-gray-400' : 'text-emerald-500' }}" />
                        @endif
                    </div>
                    <div class="min-w-0 flex-1">
                        <div class="flex flex-wrap items-center gap-2">
                            <span class="text-sm font-medium text-gray-900 dark:text-gray-100 truncate">{{ $file['name'] }}</span>
                            <span class="inline-flex items-center rounded px-1.5 py-0.5 text-[10px] font-bold uppercase {{ $file['ext'] === 'pdf' ? 'bg-red-100 text-red-600 dark:bg-red-900/20 dark:text-red-400' : 'bg-gray-100 text-gray-500 dark:bg-gray-800 dark:text-gray-400' }}">
                                {{ $file['ext'] }}
                            </span>
                            @if ($file['archive'] ?? false)
                                <span class="inline-flex items-center gap-1 rounded-full bg-amber-50 dark:bg-amber-900/20 px-1.5 py-0.5 text-[10px] font-medium text-amber-600 dark:text-amber-400">
                                    <x-heroicon-m-archive-box class="h-2.5 w-2.5" />archive
                                </span>
                            @endif
                        </div>
                        <div class="mt-0.5 flex flex-wrap items-center gap-2">
                            @if ($file['created_at'] ?? null)
                                <span class="text-[11px] text-gray-400 dark:text-gray-500">{{ $file['created_at']->format('d M Y · H:i') }}</span>
                            @endif
                            @if ($file['uploader'] ?? null)
                                <span class="text-[11px] text-gray-400 dark:text-gray-500">oleh {{ $file['uploader'] }}</span>
                            @endif
                            @if ($file['notes'] ?? null)
                                <span class="text-[11px] text-gray-500 dark:text-gray-400 italic">{{ $file['notes'] }}</span>
                            @endif
                        </div>
                    </div>
                    <div class="flex shrink-0 items-center gap-1">
                        @if ($file['previewable'])
                            <button type="button"
                                    @click="openPreview('{{ $file['url'] }}', '{{ $file['name'] }}', {{ $file['ext'] === 'pdf' ? 'true' : 'false' }})"
                                    class="inline-flex items-center justify-center rounded-lg p-2 text-blue-600 hover:bg-blue-50 dark:hover:bg-blue-900/20 transition-colors"
                                    title="Preview">
                                <x-heroicon-m-eye class="h-4 w-4" />
                            </button>
                        @endif
                        <a href="{{ $file['url'] }}" target="_blank" rel="noopener"
                           class="inline-flex items-center justify-center rounded-lg p-2 text-gray-500 hover:bg-gray-100 dark:hover:bg-gray-800 transition-colors"
                           title="Download">
                            <x-heroicon-m-arrow-down-tray class="h-4 w-4" />
                        </a>
                    </div>
                </div>
                @endforeach
            </div>
        </div>
        @endif

    @endif

    {{-- ─── Preview Modal ──────────────────────────────────────────────────── --}}
    <div x-show="open" x-cloak
         x-transition.opacity
         @keydown.escape.window="closePreview()"
         class="fixed inset-0 z-50 flex items-center justify-center bg-black/60 p-4"
         @click.self="closePreview()">
        <div class="bg-white dark:bg-gray-900 rounded-2xl shadow-xl w-full max-w-5xl h-[90vh] flex flex-col overflow-hidden">
            <div class="flex items-center justify-between border-b border-gray-200 dark:border-gray-700 px-5 py-3">
                <p class="text-sm font-medium text-gray-900 dark:text-gray-100 truncate" x-text="name"></p>
                <button type="button" @click="closePreview()"
                        class="rounded-lg p-1.5 text-gray-500 hover:bg-gray-100 dark:hover:bg-gray-800 transition-colors">
                    <x-heroicon-m-x-mark class="h-5 w-5" />
                </button>
            </div>
            <div class="flex-1 bg-gray-50 dark:bg-gray-950 overflow-hidden">
                <template x-if="isPdf">
                    <iframe :src="src" class="w-full h-full" frameborder="0"></iframe>
                </template>
                <template x-if="!isPdf">
                    <div class="w-full h-full flex items-center justify-center p-6 overflow-auto">
                        <img :src="src" :alt="name" class="max-w-full max-h-full object-contain rounded-lg shadow-md" />
                    </div>
                </template>
            </div>
        </div>
    </div>
</div>

<style>
    .brief-content p { margin-bottom: 0.5rem; }
    .brief-content p:last-child { margin-bottom: 0; }
</style>
