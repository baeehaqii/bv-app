{{--
    Paginasi daftar KOL — seluruhnya di sisi client.

    Ini bukan pilihan malas, ini yang benar: barisnya DISEMBUNYIKAN, bukan tidak
    dirender. Input yang tersembunyi tetap ikut terkirim, jadi client bisa
    memilih di halaman 1, mencari nama lain, pindah halaman, lalu menekan Submit
    sekali dan semuanya tersimpan. Paginasi sisi server akan membuang pilihan
    yang belum disubmit setiap kali halaman berganti.
--}}
<div class="mt-4 flex flex-wrap items-center justify-between gap-3">
    <p id="kol-info" class="text-xs text-gray-500"></p>

    <div class="flex items-center gap-2">
        <button type="button" id="kol-prev"
                class="rounded-lg border border-gray-200 px-3 py-1.5 text-xs font-semibold text-gray-600 transition hover:bg-white disabled:opacity-40">
            &larr; Sebelumnya
        </button>
        <span id="kol-halaman" class="text-xs text-gray-500"></span>
        <button type="button" id="kol-next"
                class="rounded-lg border border-gray-200 px-3 py-1.5 text-xs font-semibold text-gray-600 transition hover:bg-white disabled:opacity-40">
            Berikutnya &rarr;
        </button>
    </div>
</div>

<script>
(function () {
    const daftar = document.getElementById('kol-daftar');
    if (! daftar) return;

    const baris   = [...daftar.querySelectorAll('[data-kol-baris]')];
    const kosong  = document.getElementById('kol-kosong');
    const info    = document.getElementById('kol-info');
    const prev    = document.getElementById('kol-prev');
    const next    = document.getElementById('kol-next');
    const nomor   = document.getElementById('kol-halaman');
    const cari    = document.getElementById('kol-cari');
    const dipilih = document.getElementById('kol-terpilih');
    const tombolPer = [...document.querySelectorAll('[data-per-halaman]')];

    let perHalaman = 10, halaman = 1, kueri = '';

    const cocok = () => kueri ? baris.filter(b => b.dataset.cari.includes(kueri)) : baris;

    function render() {
        const hasil = cocok();
        const totalHalaman = Math.max(1, Math.ceil(hasil.length / perHalaman));
        halaman = Math.min(Math.max(1, halaman), totalHalaman);
        const mulai = (halaman - 1) * perHalaman;

        // Disembunyikan, bukan dibuang — lihat catatan di atas.
        baris.forEach(b => b.classList.add('hidden'));
        hasil.slice(mulai, mulai + perHalaman).forEach(b => b.classList.remove('hidden'));

        kosong.classList.toggle('hidden', hasil.length > 0);
        info.textContent = hasil.length === 0
            ? 'Tidak ada hasil'
            : `Menampilkan ${mulai + 1}–${Math.min(mulai + perHalaman, hasil.length)} dari ${hasil.length} KOL`;
        nomor.textContent = `${halaman} / ${totalHalaman}`;
        prev.disabled = halaman <= 1;
        next.disabled = halaman >= totalHalaman;
    }

    function hitungTerpilih() {
        dipilih.textContent = baris.filter(b => b.querySelector('input[type=radio]:checked')).length;
    }

    cari.addEventListener('input', e => {
        kueri = e.target.value.trim().toLowerCase();
        halaman = 1;
        render();
    });

    prev.addEventListener('click', () => { halaman--; render(); });
    next.addEventListener('click', () => { halaman++; render(); });

    tombolPer.forEach(tombol => tombol.addEventListener('click', () => {
        perHalaman = Number(tombol.dataset.perHalaman);
        halaman = 1;
        tombolPer.forEach(t => t.className = t.className
            .replace('border-indigo-600 bg-indigo-600 text-white', 'border-gray-200 text-gray-600 hover:bg-white'));
        tombol.className = tombol.className
            .replace('border-gray-200 text-gray-600 hover:bg-white', 'border-indigo-600 bg-indigo-600 text-white');
        render();
    }));

    daftar.addEventListener('change', hitungTerpilih);

    render();
    hitungTerpilih();
})();
</script>
