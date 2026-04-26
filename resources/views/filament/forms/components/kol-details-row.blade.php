@php
    use Filament\Actions\Action;
    use Filament\Actions\ActionGroup;
@endphp

<div {{
    $attributes
        ->merge($getExtraAttributes(), escape: false)
        ->class(['fi-kol-details-row'])
    }}>
    <div class="fi-kol-details-groups-scroll">
        @foreach ($getChildSchema()->getComponents() as $group)
            @if ($group->isHidden())
                @continue
            @endif

            @if (($group instanceof Action) || ($group instanceof ActionGroup))
                <div>{{ $group }}</div>
            @else
                <div class="fi-kol-details-group">
                    {{ $group }}
                </div>
            @endif
        @endforeach
    </div>
</div>