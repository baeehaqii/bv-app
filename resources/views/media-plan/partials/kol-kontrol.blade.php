{{--
    Kotak pencarian + jumlah per halaman + penghitung pilihan.

    Menempel di atas (sticky) supaya client tidak perlu menggulir balik ke sini
    setiap kali mau mencari KOL berikutnya.
--}}
<div class="sticky top-[57px] z-10 -mx-4 sm:-mx-6 mb-4 border-y border-gray-100 bg-gray-50/95 px-4 sm:px-6 py-3 backdrop-blur">
    <div class="flex flex-wrap items-center gap-x-4 gap-y-2">
        <input id="kol-cari" type="search" autocomplete="off"
               placeholder="Cari nama KOL / username…"
               class="min-w-[12rem] flex-1 rounded-lg border border-gray-200 bg-white px-3 py-1.5 text-sm outline-none focus:border-indigo-400 focus:ring-1 focus:ring-indigo-400">

        <div class="flex items-center gap-1.5">
            <span class="text-xs text-gray-400">Per halaman</span>
            @foreach ([10, 20, 50] as $n)
                <button type="button" data-per-halaman="{{ $n }}"
                        class="rounded-lg border px-2.5 py-1 text-xs font-semibold transition
                               {{ $n === 10 ? 'border-indigo-600 bg-indigo-600 text-white' : 'border-gray-200 text-gray-600 hover:bg-white' }}">
                    {{ $n }}
                </button>
            @endforeach
        </div>

        {{-- Dengan paginasi, KOL yang belum dipilih tidak lagi terlihat sambil
             menggulir. Penghitung ini yang menggantikan peran itu. --}}
        <p class="text-xs text-gray-500">
            <span id="kol-terpilih" class="font-semibold text-gray-900">0</span>
            dari {{ count($groupedItems) }} KOL sudah dipilih
        </p>
    </div>
</div>
