@props([
    'title' => null,
    'icon'  => null,
])

<div {{ $attributes->class([
    'rounded-2xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-900 shadow-sm overflow-hidden',
]) }}>
    @if($title)
        <div class="flex items-center gap-2.5 px-6 py-4 border-b border-gray-100 dark:border-gray-800">
            @if($icon)
                <x-filament::icon
                    :icon="$icon"
                    class="w-5 h-5 text-violet-500 dark:text-violet-400 flex-shrink-0"
                />
            @endif
            <h3 class="text-sm font-semibold text-gray-700 dark:text-gray-200 tracking-wide">
                {{ $title }}
            </h3>
        </div>
    @endif

    <div class="px-6 py-5">
        {{ $slot }}
    </div>
</div>
