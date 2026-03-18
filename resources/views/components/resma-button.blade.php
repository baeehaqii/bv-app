@props([
    'type' => 'button',
    'color' => 'primary', // primary, secondary
    'class' => '',
])

@php
    $baseClasses = 'rounded-full px-6 py-2.5 font-semibold transition-all duration-300 ease-in-out border outline-none overflow-hidden';

    // Theme Custom Resma (Sesuai dengan button.css dari resma vendor)
    $colorClasses = match ($color) {
        'primary' => 'bg-[#48009f] text-white border-transparent hover:shadow-[0px_3px_2px_2px_rgb(216,254,0)] hover:-translate-y-[2px] active:bg-[#e8e3ee] active:text-[#48009f]',
        'secondary' => 'bg-[#48009f] text-white border-[#48009f] font-medium hover:text-white hover:shadow-[0px_3px_2px_2px_rgb(216,254,0)] hover:-translate-y-[2px] active:bg-[#e8e3ee] active:text-[#48009f]',
        default => 'bg-[#48009f] text-white border-transparent hover:shadow-[0px_3px_2px_2px_rgb(216,254,0)]',
    };
@endphp

<button
    type="{{ $type }}"
    {{ $attributes->merge(['class' => "$baseClasses $colorClasses $class"]) }}
>
    {{ $slot }}
</button>
