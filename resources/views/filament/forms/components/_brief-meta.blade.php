@props(['createdAt' => null, 'uploaderName' => null, 'isArchive' => false])

<div class="flex flex-wrap items-center gap-1.5">
    @if ($createdAt)
        <span class="inline-flex items-center gap-1 rounded-full bg-gray-100 px-2 py-0.5 text-[11px] font-medium text-gray-700 dark:bg-gray-800 dark:text-gray-300">
            <x-heroicon-m-clock class="h-3 w-3" />
            {{ $createdAt->format('d M Y · H:i') }}
        </span>
    @endif
    @if ($uploaderName)
        <span class="inline-flex items-center gap-1 rounded-full bg-primary-50 px-2 py-0.5 text-[11px] font-medium text-primary-700 dark:bg-primary-500/10 dark:text-primary-300">
            <x-heroicon-m-user class="h-3 w-3" />
            {{ $uploaderName }}
        </span>
    @endif
    @if ($isArchive)
        <span class="inline-flex items-center gap-1 rounded-full bg-amber-50 px-2 py-0.5 text-[11px] font-medium uppercase text-amber-700 dark:bg-amber-500/10 dark:text-amber-300">
            <x-heroicon-m-archive-box class="h-3 w-3" />
            Archive
        </span>
    @endif
</div>
