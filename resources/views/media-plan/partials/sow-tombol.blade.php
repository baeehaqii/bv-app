{{-- Tombol pembuka rincian SOW: "IG Reels +5". $group = satu elemen $groupedItems. --}}
@php $sisa = $group['jumlah_sow'] - 1; @endphp

<button type="button"
        onclick="document.getElementById('sow-{{ $group['key'] }}').showModal()"
        class="inline-flex items-center gap-1.5 rounded-lg border border-gray-200 px-2.5 py-1 text-xs font-medium text-gray-700 hover:border-indigo-300 hover:bg-indigo-50 hover:text-indigo-700 transition">
    <span class="max-w-[11rem] truncate">{{ $group['sow_utama'] }}</span>
    @if ($sisa > 0)
        <span class="rounded bg-indigo-100 px-1.5 py-0.5 text-[10px] font-bold text-indigo-700">+{{ $sisa }}</span>
    @endif
</button>
