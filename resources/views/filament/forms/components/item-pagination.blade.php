{{--
    Kontrol daftar Budget Items. Tombolnya memanggil method di EditInternalBudget
    langsung, bukan lewat state form — halaman & mode tampilan bukan data budget.

    Dua mode: daftar ringkas (satu baris per KOL, dipaginasi) dan rincian SOW
    satu KOL. Tidak ada wire:confirm di mana pun: semua suntingan di daftar ini
    (Status KOL, Approve, Reject, Nego, Ganti KOL) sudah tersimpan seketika,
    jadi berpindah tidak membuang apa pun.
--}}
@php
    $rincian = $this->kolFokus !== null;
    $total = $this->totalItem();
    $perPage = $this->itemPerPage;
    $halaman = $this->itemPage;
    $totalHalaman = $this->totalHalamanItem();
    $dari = $total === 0 ? 0 : (($halaman - 1) * $perPage) + 1;
    $sampai = min($halaman * $perPage, $total);
@endphp

@if ($rincian)
    <div class="mpk-pager">
        <span class="mpk-info">
            Rincian SOW <strong>{{ $this->namaKolFokus() }}</strong>
        </span>

        <button type="button" wire:click="tutupDetailKol" class="mpk-btn">
            &larr; Kembali ke daftar KOL
        </button>
    </div>
@else
<div class="mpk-pager">
    <span class="mpk-info">
        Menampilkan <strong>{{ $dari }}–{{ $sampai }}</strong> dari <strong>{{ number_format($total) }}</strong> KOL
    </span>

    <span class="mpk-group">
        <span class="mpk-label">Per halaman</span>
        @foreach (\App\Filament\Resources\InternalBudgets\Pages\EditInternalBudget::ITEM_PER_PAGE as $n)
            <button type="button" wire:click="aturItemPerPage({{ $n }})"
                    @class(['mpk-btn', 'mpk-btn-aktif' => $perPage === $n])>{{ $n }}</button>
        @endforeach
    </span>

    <span class="mpk-group">
        <button type="button" wire:click="gantiHalamanItem({{ $halaman - 1 }})"
                class="mpk-btn" @disabled($halaman <= 1)>&larr; Sebelumnya</button>
        <span class="mpk-label">Halaman {{ $halaman }} / {{ $totalHalaman }}</span>
        <button type="button" wire:click="gantiHalamanItem({{ $halaman + 1 }})"
                class="mpk-btn" @disabled($halaman >= $totalHalaman)>Berikutnya &rarr;</button>
    </span>
</div>
@endif

@push('styles')
    <style>
        /* Ditulis manual: kelas Tailwind baru tidak ikut ter-compile tanpa npm run build. */
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
