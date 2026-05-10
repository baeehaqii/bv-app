@props([
    'label' => '',
    'value' => null,
])

<div>
    <p class="text-xs text-gray-400 dark:text-gray-500 uppercase tracking-wide font-medium mb-1">{{ $label }}</p>
    @if(isset($slot) && $slot->isNotEmpty())
        {{ $slot }}
    @elseif($value !== null && $value !== '')
        <p class="text-sm font-medium text-gray-800 dark:text-gray-200">{{ $value }}</p>
    @else
        <p class="text-sm text-gray-400 dark:text-gray-500">—</p>
    @endif
</div>
