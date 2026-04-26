<div class="space-y-6 p-1">

    {{-- ── Section 1: Detail KOL ── --}}
    <div>
        <h3 class="text-sm font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider mb-3">Detail KOL</h3>
        <div class="grid grid-cols-2 gap-4 sm:grid-cols-3 lg:grid-cols-5">
            <div>
                <p class="text-xs text-gray-500 dark:text-gray-400">Channel</p>
                <p class="mt-1 text-sm font-medium text-gray-900 dark:text-white">{{ $channel }}</p>
            </div>
            <div>
                <p class="text-xs text-gray-500 dark:text-gray-400">KOL Name</p>
                <p class="mt-1 text-sm font-medium text-gray-900 dark:text-white">{{ $name }}</p>
            </div>
            <div>
                <p class="text-xs text-gray-500 dark:text-gray-400">Domisili</p>
                <p class="mt-1 text-sm font-medium text-gray-900 dark:text-white">{{ $domisili }}</p>
            </div>
            <div>
                <p class="text-xs text-gray-500 dark:text-gray-400">Links</p>
                <p class="mt-1 text-sm font-medium text-gray-900 dark:text-white break-all">{{ $links }}</p>
            </div>
            <div>
                <p class="text-xs text-gray-500 dark:text-gray-400">Golongan Pajak</p>
                <p class="mt-1 text-sm font-medium text-gray-900 dark:text-white">{{ $tipe_pajak_kol }}</p>
            </div>
        </div>
    </div>

    <hr class="border-gray-200 dark:border-gray-700">

    {{-- ── Section 2: Performance ── --}}
    <div>
        <h3 class="text-sm font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider mb-3">Performance
        </h3>
        <div class="grid grid-cols-2 gap-4 sm:grid-cols-3 lg:grid-cols-5">
            <div>
                <p class="text-xs text-gray-500 dark:text-gray-400">Followers</p>
                <p class="mt-1 text-sm font-medium text-gray-900 dark:text-white">{{ $followers }}</p>
            </div>
            <div>
                <p class="text-xs text-gray-500 dark:text-gray-400">Tier</p>
                <p class="mt-1 text-sm font-medium text-gray-900 dark:text-white">{{ $tier }}</p>
            </div>
            <div>
                <p class="text-xs text-gray-500 dark:text-gray-400">ER%</p>
                <p class="mt-1 text-sm font-medium text-gray-900 dark:text-white">{{ $er_percent }}</p>
            </div>
            <div>
                <p class="text-xs text-gray-500 dark:text-gray-400">Impression</p>
                <p class="mt-1 text-sm font-medium text-gray-900 dark:text-white">{{ $impression }}</p>
            </div>
            <div>
                <p class="text-xs text-gray-500 dark:text-gray-400">Engagement</p>
                <p class="mt-1 text-sm font-medium text-gray-900 dark:text-white">{{ $engagement }}</p>
            </div>
            <div>
                <p class="text-xs text-gray-500 dark:text-gray-400">CPI/CPV</p>
                <p class="mt-1 text-sm font-medium text-gray-900 dark:text-white">{{ $cpi_cpv }}</p>
            </div>
            <div>
                <p class="text-xs text-gray-500 dark:text-gray-400">CPE</p>
                <p class="mt-1 text-sm font-medium text-gray-900 dark:text-white">{{ $cpe }}</p>
            </div>
            <div class="col-span-2">
                <p class="text-xs text-gray-500 dark:text-gray-400">Scope of Work</p>
                <p class="mt-1 text-sm font-medium text-gray-900 dark:text-white">{{ $scope_items ?: '—' }}</p>
            </div>
            <div>
                <p class="text-xs text-gray-500 dark:text-gray-400">Rate</p>
                <p class="mt-1 text-sm font-medium text-gray-900 dark:text-white">{{ $rate }}</p>
            </div>
        </div>
    </div>

    <hr class="border-gray-200 dark:border-gray-700">

    {{-- ── Section 3: Jadwal Bayar ── --}}
    <div>
        <h3 class="text-sm font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider mb-3">Jadwal Bayar
        </h3>
        <div class="grid grid-cols-2 gap-4">
            <div>
                <p class="text-xs text-gray-500 dark:text-gray-400">After Nego</p>
                <p class="mt-1 text-sm font-medium text-gray-900 dark:text-white">{{ $after_nego }}</p>
            </div>
            <div>
                <p class="text-xs text-gray-500 dark:text-gray-400">Jadwal Payment</p>
                <p class="mt-1 text-sm font-medium text-gray-900 dark:text-white">{{ $payment_date }}</p>
            </div>
        </div>
    </div>

    <hr class="border-gray-200 dark:border-gray-700">

    {{-- ── Section 4: Select Quotation ── --}}
    <div>
        <h3 class="text-sm font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider mb-3">Select
            Quotation</h3>
        <div class="grid grid-cols-3 gap-4">
            <div>
                <p class="text-xs text-gray-500 dark:text-gray-400">Select for Quotation</p>
                <p class="mt-1 text-sm font-medium text-gray-900 dark:text-white">{{ $is_selected }}</p>
            </div>
            <div>
                <p class="text-xs text-gray-500 dark:text-gray-400">Status</p>
                <p class="mt-1 text-sm font-medium text-gray-900 dark:text-white">{{ $status }}</p>
            </div>
            <div>
                <p class="text-xs text-gray-500 dark:text-gray-400">PIC</p>
                <p class="mt-1 text-sm font-medium text-gray-900 dark:text-white">{{ $pic }}</p>
            </div>
        </div>
    </div>

</div>