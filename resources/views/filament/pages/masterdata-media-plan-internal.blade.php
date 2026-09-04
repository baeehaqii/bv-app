<x-filament-panels::page>
    <form wire:submit="simpan">
        {{ $this->form }}
    </form>

    @php($p = $this->pratinjau)

    <x-filament::section>
        <x-slot name="heading">Pratinjau — 1 baris SOW rate Rp 2.000.000</x-slot>
        <x-slot name="description">
            Dihitung dengan setelan di atas, tipe pajak default dari Master PPH, dan margin dari Master Margin.
        </x-slot>

        <div class="grid grid-cols-2 gap-4 sm:grid-cols-4">
            @foreach ([
                'Subtotal' => $p['rate'],
                'Cost (MU PPh)' => $p['cost'],
                'Harga sebelum dibulatkan' => $p['mu_target'],
                'Harga jual' => $p['rounded'],
            ] as $label => $value)
                <div>
                    <div class="text-sm text-gray-500 dark:text-gray-400">{{ $label }}</div>
                    <div class="text-lg font-semibold" style="font-variant-numeric: tabular-nums">
                        Rp {{ number_format($value, 0, ',', '.') }}
                    </div>
                </div>
            @endforeach
        </div>

        <div class="mt-4 text-sm text-gray-500 dark:text-gray-400">
            Margin aktual: <span class="font-semibold">{{ number_format($p['margin'], 2, ',', '.') }}%</span>
        </div>
    </x-filament::section>
</x-filament-panels::page>
