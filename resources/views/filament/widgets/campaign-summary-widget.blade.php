<x-filament-widgets::widget>
    <x-filament::section>
        <x-slot name="heading">
            Campaign Summary
        </x-slot>

        <x-slot name="description">
            Take a quick look at how your campaign's doing, all in one place.
        </x-slot>

        {{-- Row 1: 4 cards --}}
        <div style="display: grid; grid-template-columns: repeat(4, 1fr); gap: 1rem; margin-bottom: 1rem;">
            @foreach(array_slice($this->getStats(), 0, 4) as $stat)
                <div style="background: white; border-radius: 12px; border: 1px solid #e5e7eb; padding: 1rem;">
                    <div style="display: flex; align-items: center; gap: 0.5rem; margin-bottom: 0.5rem;">
                        <x-dynamic-component 
                            :component="$stat['icon']" 
                            style="width: 20px; height: 20px; color: #6366f1;"
                        />
                        <span style="font-size: 12px; font-weight: 500; color: #6b7280; text-transform: uppercase;">
                            {{ $stat['label'] }}
                        </span>
                    </div>
                    <div style="font-size: 1.5rem; font-weight: 700; color: #111827;">
                        {{ $stat['value'] }}
                    </div>
                </div>
            @endforeach
        </div>

        {{-- Row 2: 4 cards --}}
        <div style="display: grid; grid-template-columns: repeat(4, 1fr); gap: 1rem;">
            @foreach(array_slice($this->getStats(), 4, 4) as $stat)
                <div style="background: white; border-radius: 12px; border: 1px solid #e5e7eb; padding: 1rem;">
                    <div style="display: flex; align-items: center; gap: 0.5rem; margin-bottom: 0.5rem;">
                        <x-dynamic-component 
                            :component="$stat['icon']" 
                            style="width: 20px; height: 20px; color: #6366f1;"
                        />
                        <span style="font-size: 12px; font-weight: 500; color: #6b7280; text-transform: uppercase;">
                            {{ $stat['label'] }}
                        </span>
                    </div>
                    <div style="font-size: 1.5rem; font-weight: 700; color: #111827;">
                        {{ $stat['value'] }}
                    </div>
                    @if(isset($stat['description']))
                        <p style="font-size: 12px; color: #3b82f6; margin-top: 0.25rem;">
                            {{ $stat['description'] }}
                        </p>
                    @endif
                </div>
            @endforeach
        </div>
    </x-filament::section>
</x-filament-widgets::widget>