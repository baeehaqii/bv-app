{{--
    Identitas KOL: nama + username, statistik ringkas, catatan internal, harga.
    $group = satu elemen $groupedItems.

    Harga sengaja di bawah catatan, bukan sebaris dengan tombol: yang dibaca
    client lebih dulu adalah siapa orangnya, baru berapa harganya.
--}}
@php
    $dataKol   = $group['kol']?->dataKol;

    // Sebagian data hasil migrasi menyimpan nama lengkap sebagai username.
    // "Bagus Gandhi @Bagus Gandhi" bukan informasi, cuma pengulangan.
    $username  = $group['username'];
    $samaSaja  = $username && strcasecmp(trim($username), trim($group['kol_name'])) === 0;
    $channel   = $group['kol']?->channel ?: $dataKol?->channel;
    $followers = (int) ($group['kol']?->followers ?: $dataKol?->followers ?: 0);
    $er        = (float) ($group['kol']?->er_percent ?: $dataKol?->engagement_rate ?: 0);

    $ringkas = array_filter([
        $channel ?: null,
        $followers > 0 ? number_format($followers, 0, ',', '.') . ' followers' : null,
        $er > 0 ? 'ER ' . number_format($er, 2, ',', '.') . '%' : null,
    ]);
@endphp

<div class="min-w-0">
    <p class="text-sm font-semibold text-gray-900">
        {{ $group['kol_name'] }}
        @if ($username && ! $samaSaja)
            <span class="ml-1 font-normal text-gray-400">{{ '@' . $username }}</span>
        @endif
    </p>

    @if ($ringkas)
        <p class="text-xs text-gray-500">{{ implode(' · ', $ringkas) }}</p>
    @endif

    @if ($group['notes'])
        <p class="mt-0.5 text-xs text-gray-500">
            <span class="font-semibold text-gray-400">Catatan:</span> {{ $group['notes'] }}
        </p>
    @endif

    {{-- Rp 0 dibaca client sebagai "gratis" dan strip telanjang tidak terbaca
         sebagai apa pun; sebagian KOL memang belum berharga di media plan. --}}
    @if ($group['total'] > 0)
        <p class="mt-1 text-sm font-bold text-gray-900">
            Rp {{ number_format($group['total'], 0, ',', '.') }}
        </p>
    @else
        <p class="mt-1 text-xs italic text-gray-400">Harga belum ditentukan</p>
    @endif
</div>
