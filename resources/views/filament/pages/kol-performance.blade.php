<x-filament-panels::page>
    <div class="space-y-6">
        {{-- Campaign Info Card --}}
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm ring-1 ring-gray-950/5 dark:ring-white/10 p-6">
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <div>
                    <h2 class="text-lg font-semibold text-gray-900 dark:text-white">
                        {{ $this->record->campaign_name }}
                    </h2>
                    <p class="text-sm text-gray-500 dark:text-gray-400">
                        {{ $this->record->client?->nama_brand ?? 'No Brand' }} •
                        {{ $this->record->start_date?->format('d M Y') }} -
                        {{ $this->record->end_date?->format('d M Y') }}
                    </p>
                </div>
                <div class="flex flex-wrap gap-3">
                    {{-- Total Views --}}
                    <div
                        class="text-center px-4 py-2 bg-primary-50 dark:bg-primary-950 rounded-lg border-1 border-primary-600">
                        <div class="text-2xl font-bold text-primary-600 dark:text-primary-400">
                            {{ number_format($this->record->kols->sum('views')) }}
                        </div>
                        <div class="text-xs text-gray-500 dark:text-gray-400">Total Views</div>
                    </div>

                    {{-- Total Followers --}}
                    <div
                        class="text-center px-4 py-2 bg-violet-50 dark:bg-violet-950 rounded-lg border-1 border-violet-600">
                        <div class="text-2xl font-bold text-violet-600 dark:text-violet-400">
                            {{ number_format($this->record->kols->sum('followers_count')) }}
                        </div>
                        <div class="text-xs text-gray-500 dark:text-gray-400">Total Followers</div>
                    </div>

                    {{-- Total Likes --}}
                    <div class="text-center px-4 py-2 bg-rose-50 dark:bg-rose-950 rounded-lg border-1 border-rose-600">
                        <div class="text-2xl font-bold text-rose-600 dark:text-rose-400">
                            {{ number_format($this->record->kols->sum('likes')) }}
                        </div>
                        <div class="text-xs text-gray-500 dark:text-gray-400">Total Likes</div>
                    </div>

                    {{-- Total Comments --}}
                    <div
                        class="text-center px-4 py-2 bg-amber-50 dark:bg-amber-950 rounded-lg border-1 border-amber-600">
                        <div class="text-2xl font-bold text-amber-600 dark:text-amber-400">
                            {{ number_format($this->record->kols->sum('comments')) }}
                        </div>
                        <div class="text-xs text-gray-500 dark:text-gray-400">Total Comments</div>
                    </div>

                    {{-- Total Engagement --}}
                    <div
                        class="text-center px-4 py-2 bg-success-50 dark:bg-success-950 rounded-lg border-1 border-success-600">
                        <div class="text-2xl font-bold text-success-600 dark:text-success-400">
                            {{ number_format($this->record->kols->sum('likes') + $this->record->kols->sum('comments') + $this->record->kols->sum('shares') + $this->record->kols->sum('saves')) }}
                        </div>
                        <div class="text-xs text-gray-500 dark:text-gray-400">Total Engagement</div>
                    </div>

                    {{-- Average ER --}}
                    @php
                        $kols = $this->record->kols;
                        $kolsWithER = $kols->filter(fn($kol) => $kol->engagement_rate > 0);
                        $avgER = $kolsWithER->count() > 0
                            ? $kolsWithER->avg('engagement_rate')
                            : 0;
                    @endphp
                    <div class="text-center px-4 py-2 bg-teal-50 dark:bg-teal-950 rounded-lg border-1 border-teal-600">
                        <div class="text-2xl font-bold text-teal-600 dark:text-teal-400">
                            {{ number_format($avgER, 2) }}%
                        </div>
                        <div class="text-xs text-gray-500 dark:text-gray-400">Avg ER</div>
                    </div>

                    {{-- Total KOLs --}}
                    <div class="text-center px-4 py-2 bg-info-50 dark:bg-info-950 rounded-lg border-1 border-info-600">
                        <div class="text-2xl font-bold text-info-600 dark:text-info-400">
                            {{ $this->record->kols->count() }}
                        </div>
                        <div class="text-xs text-gray-500 dark:text-gray-400">Total KOLs</div>
                    </div>
                </div>
            </div>
        </div>

        {{-- KOL Performance Table --}}
        {{ $this->table }}
    </div>
</x-filament-panels::page>