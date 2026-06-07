<x-filament-panels::page>
    @php
        $record = $this->getRecord();

        $statusLabel = match($record->status) {
            'draft'     => 'Draft',
            'submitted' => 'Submitted',
            'reviewed'  => 'Reviewed',
            'approved'  => 'Approved',
            'revision'  => 'Perlu Revisi',
            default     => ucfirst($record->status),
        };

        $statusColor = match($record->status) {
            'draft'     => ['bg' => 'bg-gray-100 dark:bg-gray-700', 'text' => 'text-gray-600 dark:text-gray-300', 'dot' => 'bg-gray-400'],
            'submitted' => ['bg' => 'bg-blue-50 dark:bg-blue-900/30', 'text' => 'text-blue-700 dark:text-blue-300', 'dot' => 'bg-blue-500'],
            'reviewed'  => ['bg' => 'bg-amber-50 dark:bg-amber-900/30', 'text' => 'text-amber-700 dark:text-amber-300', 'dot' => 'bg-amber-500'],
            'approved'  => ['bg' => 'bg-green-50 dark:bg-green-900/30', 'text' => 'text-green-700 dark:text-green-300', 'dot' => 'bg-green-500'],
            'revision'  => ['bg' => 'bg-red-50 dark:bg-red-900/30', 'text' => 'text-red-700 dark:text-red-300', 'dot' => 'bg-red-500'],
            default     => ['bg' => 'bg-gray-100 dark:bg-gray-700', 'text' => 'text-gray-600 dark:text-gray-300', 'dot' => 'bg-gray-400'],
        };

        // --- Campaign status (dari BvSales / Sales Activity Tracker)
        $salesStatus      = $record->bvSales?->status; // SalesStatus enum atau null
        $campaignStatusLabel = $salesStatus?->getLabel();

        $campaignStatusColor = match($salesStatus?->getColor()) {
            'warning' => 'bg-amber-50 text-amber-700 dark:bg-amber-900/30 dark:text-amber-300',
            'success' => 'bg-green-50 text-green-700 dark:bg-green-900/30 dark:text-green-300',
            'info'    => 'bg-blue-50 text-blue-700 dark:bg-blue-900/30 dark:text-blue-300',
            'danger'  => 'bg-red-50 text-red-700 dark:bg-red-900/30 dark:text-red-300',
            'purple'  => 'bg-violet-50 text-violet-700 dark:bg-violet-900/30 dark:text-violet-300',
            'orange'  => 'bg-orange-50 text-orange-700 dark:bg-orange-900/30 dark:text-orange-300',
            default   => 'bg-gray-100 text-gray-600 dark:bg-gray-700 dark:text-gray-300',
        };

        // --- PIC Sales (sales person yang handle campaign)
        $picSales = $record->bvSales?->salesList?->nama_sales;

        // --- PIC Agent (dari DataClient jika type = agency atau punya agency)
        $client    = $record->client ?? $record->bvSales?->client;
        $picAgent  = null;

        if ($client) {
            // pic_clients = array of object {name, role, email, ...} → ambil nama-nya saja
            $extractPicNames = fn ($pics) => is_array($pics)
                ? collect($pics)->map(fn ($p) => is_array($p) ? ($p['name'] ?? $p['nama_pic'] ?? null) : $p)->filter()->implode(', ')
                : $pics;

            if ($client->type === 'agency' && !empty($client->pic_clients)) {
                // Client sendiri adalah agency → pic_clients = kontak di agency tsb
                $picAgent = $extractPicNames($client->pic_clients);
            } elseif ($client->agency_client_id) {
                // Direct brand yang dihandle agency → ambil pic_clients dari agency-nya
                $picAgent = $extractPicNames($client->agency?->pic_clients ?? []);
            }
        }

        $budgetMainKol  = $record->budget_main_kol  ? 'Rp ' . number_format((float)$record->budget_main_kol, 0, ',', '.') : null;
        $budgetMacroKol = $record->budget_macro_kol ? 'Rp ' . number_format((float)$record->budget_macro_kol, 0, ',', '.') : null;

        $submittedAt = $record->submitted_at
            ? $record->submitted_at->translatedFormat('d M Y — H:i')
            : null;
    @endphp

    <div class="space-y-6">

        {{-- ═══════════════════════════════════════════════════════ --}}
        {{-- HEADER SUMMARY CARD                                     --}}
        {{-- ═══════════════════════════════════════════════════════ --}}
        <div class="rounded-2xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-900 overflow-hidden shadow-sm">
            {{-- Accent stripe --}}
            <div class="h-1.5 bg-gradient-to-r from-violet-500 via-purple-500 to-indigo-500"></div>
            <div class="px-6 py-5 flex flex-col sm:flex-row sm:items-center gap-4">
                {{-- Left: title info --}}
                <div class="flex-1 min-w-0">
                    <div class="flex flex-wrap items-center gap-2 mb-1">
                        {{-- Status badge --}}
                        <span class="inline-flex items-center gap-1.5 rounded-full px-3 py-1 text-xs font-semibold {{ $statusColor['bg'] }} {{ $statusColor['text'] }}">
                            <span class="w-1.5 h-1.5 rounded-full {{ $statusColor['dot'] }}"></span>
                            {{ $statusLabel }}
                        </span>
                        @if($campaignStatusLabel)
                            <span class="inline-flex items-center rounded-full px-3 py-1 text-xs font-medium {{ $campaignStatusColor }}">
                                {{ $campaignStatusLabel }}
                            </span>
                        @endif
                    </div>
                    <h2 class="text-xl font-bold text-gray-900 dark:text-white truncate">
                        {{ $record->brand ?: ($record->campaign_name ?: 'Form Brief') }}
                    </h2>
                    @if($record->campaign_name)
                        <p class="text-sm text-gray-500 dark:text-gray-400 mt-0.5">{{ $record->campaign_name }}</p>
                    @endif
                </div>

                {{-- Right: key stats --}}
                <div class="flex flex-wrap gap-4 sm:gap-6 sm:flex-shrink-0">
                    @if($record->deadline)
                        <div class="text-center">
                            <p class="text-xs text-gray-400 dark:text-gray-500 uppercase tracking-wide font-medium mb-0.5">Deadline</p>
                            <p class="text-sm font-semibold text-gray-800 dark:text-gray-200">{{ $record->deadline }}</p>
                        </div>
                    @endif
                    @if($record->pic || $picSales)
                        <div class="text-center">
                            <p class="text-xs text-gray-400 dark:text-gray-500 uppercase tracking-wide font-medium mb-0.5">PIC Sales</p>
                            <p class="text-sm font-semibold text-gray-800 dark:text-gray-200">{{ $picSales ?? $record->pic }}</p>
                        </div>
                    @endif
                    @if($submittedAt)
                        <div class="text-center">
                            <p class="text-xs text-gray-400 dark:text-gray-500 uppercase tracking-wide font-medium mb-0.5">Disubmit</p>
                            <p class="text-sm font-semibold text-gray-800 dark:text-gray-200">{{ $submittedAt }}</p>
                        </div>
                    @endif
                </div>
            </div>

            {{-- Campaign Objective (di bawah stats row) --}}
            @if($record->campaign_objective)
                <div class="px-6 pb-5 border-t border-gray-100 dark:border-gray-800 pt-4">
                    <p class="text-xs text-gray-400 dark:text-gray-500 uppercase tracking-wide font-medium mb-1">Campaign Objective</p>
                    <p class="text-sm text-gray-800 dark:text-gray-200">{{ $record->campaign_objective }}</p>
                </div>
            @endif
        </div>

        {{-- ═══════════════════════════════════════════════════════ --}}
        {{-- ROW 1.5: Detail Campaign (format brief baru)           --}}
        {{-- ═══════════════════════════════════════════════════════ --}}
        @if($record->product || $record->background || $record->cta || $record->target_audience || $record->key_messages || $record->delivery_date)
            <x-bv.card title="Detail Campaign" icon="heroicon-o-megaphone">
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-x-8 gap-y-5">
                    <x-bv.field label="Product" :value="$record->product" />
                    <x-bv.field label="Target Audience" :value="$record->target_audience" />
                    <x-bv.field label="Call to Action" :value="$record->cta" />
                    <x-bv.field label="Delivery Date" :value="$record->delivery_date?->translatedFormat('d M Y')" />
                </div>
                @if($record->background)
                    <div class="mt-5 pt-5 border-t border-gray-100 dark:border-gray-800">
                        <p class="text-xs text-gray-400 dark:text-gray-500 uppercase tracking-wide font-medium mb-1">Background</p>
                        <div class="prose prose-sm dark:prose-invert max-w-none text-gray-700 dark:text-gray-300 leading-relaxed">{!! nl2br(e($record->background)) !!}</div>
                    </div>
                @endif
                @if($record->key_messages)
                    <div class="mt-5 pt-5 border-t border-gray-100 dark:border-gray-800">
                        <p class="text-xs text-gray-400 dark:text-gray-500 uppercase tracking-wide font-medium mb-1">Key Messages</p>
                        <div class="prose prose-sm dark:prose-invert max-w-none text-gray-700 dark:text-gray-300 leading-relaxed">{!! nl2br(e($record->key_messages)) !!}</div>
                    </div>
                @endif
            </x-bv.card>
        @endif

        {{-- ═══════════════════════════════════════════════════════ --}}
        {{-- ROW 1.6: Request KOL & Guideline (format brief baru)   --}}
        {{-- ═══════════════════════════════════════════════════════ --}}
        @if($record->request_kol || $record->persona_kol || $record->brief_do || $record->brief_dont || $record->kpi)
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                <x-bv.card title="Request & Persona KOL" icon="heroicon-o-users">
                    @if($record->request_kol)
                        <p class="text-xs text-gray-400 dark:text-gray-500 uppercase tracking-wide font-medium mb-1">Request KOL</p>
                        <div class="prose prose-sm dark:prose-invert max-w-none text-gray-700 dark:text-gray-300 leading-relaxed mb-4">{!! nl2br(e($record->request_kol)) !!}</div>
                    @endif
                    @if($record->persona_kol)
                        <p class="text-xs text-gray-400 dark:text-gray-500 uppercase tracking-wide font-medium mb-1">Persona KOL</p>
                        <div class="prose prose-sm dark:prose-invert max-w-none text-gray-700 dark:text-gray-300 leading-relaxed">{!! nl2br(e($record->persona_kol)) !!}</div>
                    @endif
                    @if(!$record->request_kol && !$record->persona_kol)
                        <p class="text-gray-400 dark:text-gray-500 text-sm italic">Belum diisi</p>
                    @endif
                </x-bv.card>

                <x-bv.card title="Guideline & KPI" icon="heroicon-o-check-badge">
                    @if($record->brief_do)
                        <p class="text-xs text-gray-400 dark:text-gray-500 uppercase tracking-wide font-medium mb-1">Do</p>
                        <div class="prose prose-sm dark:prose-invert max-w-none text-gray-700 dark:text-gray-300 leading-relaxed mb-4">{!! nl2br(e($record->brief_do)) !!}</div>
                    @endif
                    @if($record->brief_dont)
                        <p class="text-xs text-gray-400 dark:text-gray-500 uppercase tracking-wide font-medium mb-1">Don'ts</p>
                        <div class="prose prose-sm dark:prose-invert max-w-none text-gray-700 dark:text-gray-300 leading-relaxed mb-4">{!! nl2br(e($record->brief_dont)) !!}</div>
                    @endif
                    @if($record->kpi)
                        <p class="text-xs text-gray-400 dark:text-gray-500 uppercase tracking-wide font-medium mb-1">KPI</p>
                        <div class="prose prose-sm dark:prose-invert max-w-none text-gray-700 dark:text-gray-300 leading-relaxed">{!! nl2br(e($record->kpi)) !!}</div>
                    @endif
                    @if(!$record->brief_do && !$record->brief_dont && !$record->kpi)
                        <p class="text-gray-400 dark:text-gray-500 text-sm italic">Belum diisi</p>
                    @endif
                </x-bv.card>
            </div>
        @endif

        {{-- ═══════════════════════════════════════════════════════ --}}
        {{-- ROW 2: Criteria KOL  |  SOW                            --}}
        {{-- ═══════════════════════════════════════════════════════ --}}
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">

            <x-bv.card title="Criteria of KOL" icon="heroicon-o-user-group">
                @if($record->criteria_of_kol)
                    <div class="prose prose-sm dark:prose-invert max-w-none text-gray-700 dark:text-gray-300 leading-relaxed">
                        {!! nl2br(e($record->criteria_of_kol)) !!}
                    </div>
                @else
                    <p class="text-gray-400 dark:text-gray-500 text-sm italic">Belum diisi</p>
                @endif
            </x-bv.card>

            <x-bv.card title="SOW (Scope of Work)" icon="heroicon-o-clipboard-document-list">
                @if($record->sow)
                    <div class="prose prose-sm dark:prose-invert max-w-none text-gray-700 dark:text-gray-300 leading-relaxed">
                        {!! nl2br(e($record->sow)) !!}
                    </div>
                @else
                    <p class="text-gray-400 dark:text-gray-500 text-sm italic">Belum diisi</p>
                @endif
            </x-bv.card>

        </div>

        {{-- ═══════════════════════════════════════════════════════ --}}
        {{-- ROW 3: Budget  |  Status & Detail                      --}}
        {{-- ═══════════════════════════════════════════════════════ --}}
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">

            {{-- Budget --}}
            <x-bv.card title="Budget" icon="heroicon-o-banknotes">
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-x-8 gap-y-5">
                    <div>
                        <p class="text-xs text-gray-400 dark:text-gray-500 uppercase tracking-wide font-medium mb-1">Budget Main KOL</p>
                        @if($budgetMainKol)
                            <p class="text-lg font-bold text-violet-600 dark:text-violet-400">{{ $budgetMainKol }}</p>
                        @else
                            <p class="text-gray-400 dark:text-gray-500">—</p>
                        @endif
                    </div>
                    <div>
                        <p class="text-xs text-gray-400 dark:text-gray-500 uppercase tracking-wide font-medium mb-1">Budget Macro KOL</p>
                        @if($budgetMacroKol)
                            <p class="text-lg font-bold text-violet-600 dark:text-violet-400">{{ $budgetMacroKol }}</p>
                        @else
                            <p class="text-gray-400 dark:text-gray-500">—</p>
                        @endif
                    </div>
                </div>
                @if($budgetMainKol && $budgetMacroKol)
                    <div class="mt-4 pt-4 border-t border-gray-100 dark:border-gray-800">
                        <p class="text-xs text-gray-400 dark:text-gray-500 uppercase tracking-wide font-medium mb-1">Total Budget</p>
                        @php
                            $total = (float)($record->budget_main_kol ?? 0) + (float)($record->budget_macro_kol ?? 0);
                        @endphp
                        <p class="text-lg font-bold text-gray-900 dark:text-white">Rp {{ number_format($total, 0, ',', '.') }}</p>
                    </div>
                @endif
            </x-bv.card>

            {{-- Status & Detail --}}
            <x-bv.card title="Status & Detail" icon="heroicon-o-link">
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-x-8 gap-y-5">
                    <x-bv.field label="Deadline" :value="$record->deadline" />
                    <div>
                        <p class="text-xs text-gray-400 dark:text-gray-500 uppercase tracking-wide font-medium mb-1.5">Status</p>
                        <span class="inline-flex items-center gap-1.5 rounded-full px-3 py-1 text-xs font-semibold {{ $statusColor['bg'] }} {{ $statusColor['text'] }}">
                            <span class="w-1.5 h-1.5 rounded-full {{ $statusColor['dot'] }}"></span>
                            {{ $statusLabel }}
                        </span>
                    </div>
                </div>

                <div class="mt-5 pt-5 border-t border-gray-100 dark:border-gray-800 grid grid-cols-1 sm:grid-cols-2 gap-x-8 gap-y-5">
                    <div>
                        <p class="text-xs text-gray-400 dark:text-gray-500 uppercase tracking-wide font-medium mb-1">Sheet Link Internal</p>
                        @if($record->sheet_link_internal)
                            <a href="{{ $record->sheet_link_internal }}" target="_blank"
                               class="text-sm text-violet-600 dark:text-violet-400 hover:underline truncate block max-w-full">
                                Buka Link ↗
                            </a>
                        @else
                            <p class="text-gray-400 dark:text-gray-500">—</p>
                        @endif
                    </div>
                    <div>
                        <p class="text-xs text-gray-400 dark:text-gray-500 uppercase tracking-wide font-medium mb-1">Sheet Link External</p>
                        @if($record->sheet_link_external)
                            <a href="{{ $record->sheet_link_external }}" target="_blank"
                               class="text-sm text-violet-600 dark:text-violet-400 hover:underline truncate block max-w-full">
                                Buka Link ↗
                            </a>
                        @else
                            <p class="text-gray-400 dark:text-gray-500">—</p>
                        @endif
                    </div>
                    <div>
                        <p class="text-xs text-gray-400 dark:text-gray-500 uppercase tracking-wide font-medium mb-1">Supporting Doc</p>
                        @if($record->supporting_doc)
                            <a href="{{ $record->supporting_doc }}" target="_blank"
                               class="text-sm text-violet-600 dark:text-violet-400 hover:underline truncate block max-w-full">
                                Buka Link ↗
                            </a>
                        @else
                            <p class="text-gray-400 dark:text-gray-500">—</p>
                        @endif
                    </div>
                </div>
            </x-bv.card>

        </div>

        {{-- ═══════════════════════════════════════════════════════ --}}
        {{-- ROW 4: Submission Info (full width)                    --}}
        {{-- ═══════════════════════════════════════════════════════ --}}
        <x-bv.card title="Submission Info" icon="heroicon-o-user-circle">
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-x-8 gap-y-5">
                <x-bv.field label="Disubmit Oleh" :value="$record->submitted_by_name" />
                <x-bv.field label="Email" :value="$record->submitted_by_email" />
                <x-bv.field label="Tanggal Submit" :value="$submittedAt" />
            </div>

            @if($record->review_notes)
                <div class="mt-5 pt-5 border-t border-gray-100 dark:border-gray-800">
                    <p class="text-xs text-gray-400 dark:text-gray-500 uppercase tracking-wide font-medium mb-2">Catatan Review</p>
                    <div class="rounded-lg bg-amber-50 dark:bg-amber-900/20 border border-amber-100 dark:border-amber-800/40 px-4 py-3">
                        <p class="text-sm text-amber-900 dark:text-amber-200 leading-relaxed">{{ $record->review_notes }}</p>
                    </div>
                </div>
            @endif
        </x-bv.card>

    </div>
</x-filament-panels::page>
