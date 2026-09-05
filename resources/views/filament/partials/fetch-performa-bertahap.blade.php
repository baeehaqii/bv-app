{{--
    Penggerak + progres "Fetch All Performance" yang bertahap.

    Perulangannya di sisi klien: tiap putaran satu request pendek, sampai
    fetchChunk() mengembalikan true. Dipakai bersama oleh Campaign Summary dan
    tab KOL Performance lewat trait MenarikPerformaBertahap.
--}}
<div
    x-data="{
        berjalan: false,
        async jalan() {
            if (this.berjalan) return;
            this.berjalan = true;
            try {
                while (! await $wire.fetchChunk()) { /* potongan berikutnya */ }
            } finally {
                this.berjalan = false;
            }
        }
    }"
    {{-- $this, bukan Trait::KONSTANTA — PHP melarang akses konstanta trait
         secara langsung; harus lewat kelas yang memakainya. --}}
    x-on:{{ $this::FETCH_EVENT }}.window="jalan()"
>
    @if ($this->fetching || $this->fetchFinished)
        <div class="fpb">
            <div class="fpb-judul">{{ $this->fetchFinished ? 'Fetch selesai' : 'Menarik performa…' }}</div>

            <div class="fpb-bar"><span style="width:{{ $this->fetchPersen }}%"></span></div>

            <div class="fpb-stat">
                <div><span>Diproses</span><b>{{ $this->fetchProcessed }}/{{ $this->fetchTotal }}</b></div>
                <div><span>Berhasil</span><b>{{ $this->fetchSuccess }}</b></div>
                <div><span>Gagal</span><b>{{ $this->fetchFailed }}</b></div>
            </div>

            @unless ($this->fetchFinished)
                <p class="fpb-catatan">Jangan tutup tab selama proses berjalan.</p>
            @endunless

            @if ($this->fetchErrors)
                <div class="fpb-log">
                    @foreach ($this->fetchErrors as $galat)
                        <div>{{ $galat }}</div>
                    @endforeach
                </div>
            @endif
        </div>

        {{-- CSS manual ber-prefix .fpb- : kelas Tailwind baru tidak ikut
             ter-compile sampai `npm run build`. --}}
        @once
            <style>
                .fpb{margin:.75rem 0;padding:.9rem 1rem;border-radius:.75rem;
                    border:1px solid rgba(128,128,128,.25)}
                .fpb-judul{font-size:.9rem;font-weight:700;margin-bottom:.6rem}
                .fpb-bar{height:.5rem;border-radius:9999px;background:rgba(128,128,128,.2);overflow:hidden}
                .fpb-bar span{display:block;height:100%;background:#16a34a;transition:width .25s}
                .fpb-stat{display:flex;gap:1.5rem;flex-wrap:wrap;margin-top:.6rem;font-size:.8rem}
                .fpb-stat span{opacity:.6}
                .fpb-stat b{display:block;font-size:1rem}
                .fpb-catatan{margin-top:.6rem;font-size:.78rem;opacity:.6}
                .fpb-log{max-height:9rem;overflow:auto;margin-top:.7rem;font-size:.75rem;
                    border:1px solid rgba(128,128,128,.25);border-radius:.5rem;padding:.5rem}
            </style>
        @endonce
    @endif
</div>
