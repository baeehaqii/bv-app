{{--
    Paginasi KOL List. Tombolnya memanggil method di EditMediaPlan langsung,
    bukan lewat state form — jumlah baris per halaman bukan data media plan.

    Tidak ada wire:confirm: pindah halaman cuma menukar isi daftar KOL. Suntingan
    yang belum disimpan dititipkan di memori dan dipasang lagi saat halamannya
    dibuka kembali, serta ikut tertulis saat Save Changes ditekan.
--}}
@php
    $total = $this->totalKol();
    $perPage = $this->kolPerPage;
    $halaman = $this->kolPage;
    $totalHalaman = $this->totalHalamanKol();
    $dari = $total === 0 ? 0 : (($halaman - 1) * $perPage) + 1;
    $sampai = min($halaman * $perPage, $total);
@endphp

<div class="mpk-pager">
    <span class="mpk-info">
        Menampilkan <strong>{{ $dari }}–{{ $sampai }}</strong> dari <strong>{{ number_format($total) }}</strong> KOL
    </span>

    <span class="mpk-group">
        <span class="mpk-label">Per halaman</span>
        @foreach (\App\Filament\Resources\MediaPlans\Pages\EditMediaPlan::KOL_PER_PAGE as $n)
            <button type="button" wire:click="aturKolPerPage({{ $n }})"
                    @class(['mpk-btn', 'mpk-btn-aktif' => $perPage === $n])>{{ $n }}</button>
        @endforeach
    </span>

    <span class="mpk-group">
        <button type="button" wire:click="gantiHalamanKol({{ $halaman - 1 }})"
                class="mpk-btn" @disabled($halaman <= 1)>&larr; Sebelumnya</button>
        <span class="mpk-label">Halaman {{ $halaman }} / {{ $totalHalaman }}</span>
        <button type="button" wire:click="gantiHalamanKol({{ $halaman + 1 }})"
                class="mpk-btn" @disabled($halaman >= $totalHalaman)>Berikutnya &rarr;</button>
    </span>
</div>

@push('styles')
    <style>
        /* Ditulis manual: kelas Tailwind baru tidak ikut ter-compile tanpa
           npm run build. Lihat memori project_filament_theme_build_gotcha. */
        .mpk-pager{display:flex;align-items:center;gap:1.25rem;flex-wrap:wrap;
            padding:.6rem .75rem;border:1px solid rgba(128,128,128,.25);border-radius:.6rem;margin-top:.75rem}
        .mpk-info,.mpk-label{font-size:.8rem;color:rgba(128,128,128,.95)}
        .mpk-group{display:flex;align-items:center;gap:.35rem}
        .mpk-btn{padding:.2rem .6rem;border:1px solid rgba(128,128,128,.3);border-radius:.4rem;
            font-size:.8rem;font-weight:600;line-height:1.6}
        .mpk-btn:disabled{opacity:.4;cursor:not-allowed}
        .mpk-btn-aktif{background:var(--primary-500,#7c3aed);color:#fff;border-color:transparent}
    </style>
@endpush
