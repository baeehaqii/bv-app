{{--
    Halaman e-SPK publik. $step: 1 = verifikasi, 2 = preview + tanda tangan, 4 = selesai.
    Step 3 (Sign) dibuka dari step 2 lewat tombol, bukan request baru — tidak ada state
    server tambahan yang perlu dijaga hanya untuk memindah panel.
--}}
@php
    $steps = [1 => 'Verifikasi', 2 => 'Preview', 3 => 'Tanda Tangan', 4 => 'Selesai'];
    // Panel Sign dibuka di klien, jadi untuk stepper langkah 2 & 3 sama-sama "sedang jalan".
    $activeStep = $step;
@endphp
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>e-SPK {{ $spk->spk_number }} | Beyond Viral</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        :root {
            /* Sama dengan primary panel office (OfficePanelProvider: '#48009f'). */
            --bv-purple: #48009F;
            --bv-lime: #DAFF00;
            /* Nilai glow persis dari button.css panel: rgb(216, 254, 0) — sedikit
               beda dari --bv-lime, dibiarkan apa adanya agar identik dengan panel. */
            --bv-glow: rgb(216, 254, 0);

            /*
             * Hijau gradasi SENGAJA bukan neon lime.
             * Ramp #48009F → #DAFF00 melewati cokelat-olive karena kanal biru jatuh
             * dari 159 ke 0 sementara hijau naik — titik tengahnya #918050 (chroma
             * OKLab cuma 0.018, praktis abu-abu). Spring green menahan kanal biru
             * tetap tinggi, jadi ramp-nya ungu → biru → teal → hijau dan chroma
             * bertahan di ~0.094. Neon lime tetap dipakai, tapi hanya sebagai warna
             * SOLID di atas ungu (label kecil), tidak pernah di-blend.
             */
            --bv-green: #00E58F;
            --bv-grad: linear-gradient(115deg, var(--bv-purple) 0%, #3B1B9C 45%, var(--bv-green) 100%);
        }

        body { font-family: 'Inter', sans-serif; }

        /*
         * Tombol menyalin resources/css/filament/theme/panel/button.css persis:
         * pill 9999px, ungu solid, glow lime, padding .625rem 1.5rem.
         * Catatan: hover di panel TIDAK mengubah warna latar — umpan baliknya
         * angkat translateY(-2px) + glow lebih rapat. Jangan diganti jadi
         * "ungu lebih terang", itu bukan perilaku panelnya.
         * Ukuran & radius ditaruh di sini (bukan utility Tailwind) supaya tidak
         * bergantung pada urutan stylesheet CDN.
         */
        .bv-btn,
        .bv-btn-secondary {
            border-radius: 9999px;
            background: var(--bv-purple);
            color: #ffffff;
            font-weight: 600;
            padding: 0.625rem 1.5rem;
            border: none;
            transition: all 0.3s ease;
        }

        .bv-btn { box-shadow: 0px 3px 3px 2px var(--bv-glow); }

        /* Sekunder (Download PDF, Batal): pill ungu berbingkai, glow baru muncul
           saat hover — sama seperti tombol cancel di modal panel. */
        .bv-btn-secondary {
            font-weight: 500;
            border: 2px solid var(--bv-purple);
        }

        .bv-btn:hover,
        .bv-btn-secondary:hover {
            background: var(--bv-purple);
            color: #ffffff;
            box-shadow: 0px 3px 2px 2px var(--bv-glow);
            transform: translateY(-2px);
        }

        .bv-btn:active,
        .bv-btn-secondary:active { transform: translateY(0); }

        .bv-btn:focus-visible,
        .bv-btn-secondary:focus-visible {
            outline: 2px solid var(--bv-purple);
            outline-offset: 3px;
        }

        .bv-band { background: var(--bv-grad); }

        /* Garis aksen 4px di atas halaman — tanpa teks, jadi bebas. */
        .bv-accent {
            height: 4px;
            background: linear-gradient(90deg, var(--bv-purple) 0%, var(--bv-green) 100%);
        }

        .bv-step-active { background: var(--bv-purple); color: #fff; }
        .bv-text { color: var(--bv-purple); }

        .bv-input:focus {
            border-color: var(--bv-purple);
            box-shadow: 0 0 0 1px var(--bv-purple);
            outline: none;
        }
    </style>
</head>
<body class="bg-gray-50 min-h-screen">

<div class="bv-accent"></div>

<header class="bg-white border-b border-gray-200 sticky top-0 z-10 shadow-sm">
    <div class="max-w-4xl mx-auto px-4 sm:px-6 py-4 flex items-center justify-between gap-3">
        <div class="flex items-center gap-3 min-w-0">
            <img src="https://res.cloudinary.com/dbr6xazzh/image/upload/v1763576028/01_Logo_Main_f2t5wp.avif"
                 alt="Beyond Viral" class="h-8 object-contain">
            <span class="text-gray-300">|</span>
            <span class="text-sm text-gray-500 font-medium truncate">e-SPK Tanda Tangan</span>
        </div>
        <span class="shrink-0 inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium
            {{ $step === 4 ? 'bg-green-100 text-green-800' : 'bg-amber-100 text-amber-800' }}">
            {{ $step === 4 ? 'Selesai' : 'Langkah ' . min($step, 3) . ': ' . $steps[min($step, 3)] }}
        </span>
    </div>
</header>

<main class="max-w-4xl mx-auto px-4 sm:px-6 py-8 space-y-6">

    {{-- ───────── Stepper ───────── --}}
    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 px-4 sm:px-8 py-5">
        <ol class="flex items-center justify-between gap-1 sm:gap-2">
            @foreach ($steps as $n => $label)
                @php
                    // Step 3 dianggap "sedang jalan" bersama step 2 karena satu halaman.
                    $done = $step === 4 ? $n < 4 : ($n < $activeStep || ($n === 3 && false));
                    $current = $step === 4 ? $n === 4 : ($n === $activeStep || ($activeStep === 2 && $n === 3));
                @endphp
                <li class="flex items-center gap-1.5 sm:gap-2 {{ $n < count($steps) ? 'flex-1' : '' }}">
                    <span class="shrink-0 w-7 h-7 rounded-full flex items-center justify-center text-xs font-semibold
                        {{ $done ? 'bg-green-100 text-green-700' : ($current ? 'bv-step-active' : 'bg-gray-100 text-gray-400') }}">
                        {{ $done ? '✓' : $n }}
                    </span>
                    <span class="text-xs font-medium hidden sm:inline
                        {{ $done ? 'text-green-700' : ($current ? 'text-gray-900' : 'text-gray-400') }}">{{ $label }}</span>
                    @if ($n < count($steps))
                        <span class="flex-1 h-px {{ $done ? 'bg-green-200' : 'bg-gray-200' }}"></span>
                    @endif
                </li>
            @endforeach
        </ol>
    </div>

    {{-- ───────── STEP 1: Verifikasi ───────── --}}
    @if ($step === 1)
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
            <div class="bv-band px-6 sm:px-8 py-5">
                <p class="text-[10px] font-semibold uppercase tracking-widest" style="color: var(--bv-lime)">e-SPK</p>
                <p class="text-xl font-bold text-white mt-0.5">Verifikasi Data SPK</p>
            </div>

            <form method="POST" action="{{ route('spk.public.verify', ['token' => $spk->public_token]) }}"
                  class="p-6 sm:p-8">
                @csrf

                <p class="text-sm text-gray-600 mb-6">
                    Halo! Sebelum menandatangani, mohon konfirmasi 3 data berikut agar sesuai dengan SPK Anda.
                </p>

                @if ($errors->any())
                    <div class="mb-5 bg-red-50 border border-red-200 rounded-xl p-4">
                        @foreach ($errors->all() as $error)
                            <p class="text-xs text-red-700">{{ $error }}</p>
                        @endforeach
                    </div>
                @endif

                <div class="space-y-5">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1.5">
                            No. SPK <span class="text-red-500">*</span>
                        </label>
                        <input type="text" name="spk_number" required value="{{ old('spk_number') }}"
                               placeholder="Masukkan nomor SPK..."
                               class="w-full text-sm rounded-xl border border-gray-200 px-4 py-3 bv-input">
                        <p class="text-xs text-gray-400 mt-1">Ada di pesan WhatsApp yang Anda terima.</p>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1.5">
                            Nama Lengkap <span class="text-red-500">*</span>
                        </label>
                        <input type="text" name="name" required value="{{ old('name') }}"
                               placeholder="Nama lengkap sesuai KTP..."
                               class="w-full text-sm rounded-xl border border-gray-200 px-4 py-3 bv-input">
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1.5">
                            Platform <span class="text-red-500">*</span>
                        </label>
                        <select name="platform" required
                                class="w-full text-sm rounded-xl border border-gray-200 px-4 py-3 bg-white bv-input">
                            <option value="">Pilih Platform</option>
                            @foreach ($platforms as $platform)
                                <option value="{{ $platform }}" @selected(old('platform') === $platform)>{{ $platform }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div class="mt-7 flex justify-end">
                    <button type="submit"
                            class="bv-btn inline-flex items-center gap-2 text-sm">
                        Verifikasi &amp; Lanjut
                    </button>
                </div>
            </form>
        </div>
    @endif

    {{-- ───────── STEP 2 & 3: Preview + Tanda Tangan ───────── --}}
    @if ($step === 2)
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6 sm:p-8">
            <p class="text-xs font-semibold text-gray-400 uppercase tracking-wide mb-2">Ringkasan</p>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 text-sm">
                <div>
                    <p class="text-xs text-gray-400">Campaign</p>
                    <p class="font-semibold text-gray-900">{{ $spk->nama_campaign ?: '-' }}</p>
                </div>
                <div>
                    <p class="text-xs text-gray-400">No. SPK</p>
                    <p class="font-semibold text-gray-900">{{ $spk->spk_number }}</p>
                </div>
                <div>
                    <p class="text-xs text-gray-400">Nama Akun</p>
                    <p class="font-semibold text-gray-900">{{ $spk->pihak_kedua_nama_akun ?: '-' }}</p>
                </div>
                <div>
                    <p class="text-xs text-gray-400">Nominal (di luar pajak)</p>
                    <p class="font-semibold text-gray-900">{{ $spk->formatted_nominal }}</p>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6 sm:p-8">
            <p class="text-sm text-gray-600 mb-4">
                Silakan baca dokumen. Jika sudah sesuai, tekan <strong>Lanjut Tanda Tangan</strong> di bawah ini.
            </p>

            <div class="rounded-xl border border-gray-200 overflow-hidden bg-gray-100">
                <iframe src="{{ route('spk.public.document', ['token' => $spk->public_token]) }}"
                        title="Dokumen SPK {{ $spk->spk_number }}"
                        class="w-full bg-white" style="height: 65vh; border: 0;"></iframe>
            </div>

            <div class="mt-5 flex flex-col sm:flex-row sm:justify-between gap-3">
                <a href="{{ route('spk.public.download', ['token' => $spk->public_token]) }}"
                   class="bv-btn-secondary inline-flex items-center justify-center gap-1.5 text-sm">
                    ⬇️ Download PDF
                </a>
                <button type="button" id="to-sign"
                        class="bv-btn inline-flex items-center justify-center gap-2 text-sm">
                    Lanjut Tanda Tangan
                </button>
            </div>
        </div>

        {{-- Panel tanda tangan (step 3) --}}
        <div id="sign-panel" class="hidden bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
            <div class="bv-band px-6 sm:px-8 py-5">
                <p class="text-[10px] font-semibold uppercase tracking-widest" style="color: var(--bv-lime)">
                    SPK · {{ $spk->pihak_kedua_nama_lengkap ?: 'Pihak Kedua' }}
                </p>
                <p class="text-xl font-bold text-white mt-0.5">Tanda Tangan</p>
            </div>

            <form method="POST" action="{{ route('spk.public.sign', ['token' => $spk->public_token]) }}"
                  class="p-6 sm:p-8" id="sign-form">
                @csrf

                @if ($errors->has('signature') || $errors->has('agree'))
                    <div class="mb-5 bg-red-50 border border-red-200 rounded-xl p-4">
                        @foreach ($errors->all() as $error)
                            <p class="text-xs text-red-700">{{ $error }}</p>
                        @endforeach
                    </div>
                @endif

                <div class="flex items-center justify-between mb-1.5">
                    <label class="block text-sm font-medium text-gray-700">
                        Bubuhkan tanda tangan <span class="text-red-500">*</span>
                    </label>
                    <button type="button" id="sign-clear"
                            class="text-xs font-medium text-red-500 hover:text-red-700 border border-red-100 hover:bg-red-50 rounded-lg px-2.5 py-1 transition">
                        ✕ Hapus
                    </button>
                </div>

                <canvas id="sign-pad" height="220"
                        class="w-full bg-gray-50 border-2 border-dashed border-gray-200 rounded-2xl touch-none cursor-crosshair"></canvas>
                <p class="text-xs text-gray-400 mt-1.5">Tanda tangan dengan mouse atau jari di area di atas.</p>
                <input type="hidden" name="signature" id="sign-data">

                <label class="mt-5 flex items-start gap-2.5 text-sm text-gray-700">
                    <input type="checkbox" name="agree" value="1" required class="mt-0.5 rounded">
                    <span>Saya menyetujui isi SPK ini dan tanda tangan ini sah secara elektronik.</span>
                </label>

                <button type="submit"
                        class="bv-btn mt-6 w-full inline-flex items-center justify-center gap-2 text-sm">
                    ✍️ Selesai Tanda Tangan
                </button>
            </form>
        </div>

        {{-- Modal konfirmasi --}}
        <div id="confirm-modal" class="hidden fixed inset-0 z-50 bg-gray-900/50 flex items-center justify-center p-4">
            <div class="bg-white rounded-2xl shadow-xl max-w-sm w-full p-6 sm:p-8 text-center">
                <div class="mx-auto w-14 h-14 rounded-full flex items-center justify-center mb-4" style="background: rgba(73,0,159,.1)">
                    <span class="text-2xl bv-text">ℹ️</span>
                </div>
                <p class="text-lg font-bold text-gray-900">Konfirmasi Tanda Tangan</p>
                <p class="text-sm text-gray-500 mt-2">
                    Apakah Anda yakin tanda tangan yang dibubuhkan sudah sesuai dan ingin mengirimkan dokumen ini?
                </p>
                <div class="mt-6 flex gap-3">
                    <button type="button" id="confirm-cancel"
                            class="bv-btn-secondary flex-1 text-sm">
                        Batal
                    </button>
                    <button type="button" id="confirm-ok"
                            class="bv-btn flex-1 text-sm">
                        Ya, Kirim
                    </button>
                </div>
            </div>
        </div>

        <script>
            (() => {
                const canvas = document.getElementById('sign-pad');
                const ctx = canvas.getContext('2d');
                const form = document.getElementById('sign-form');
                const modal = document.getElementById('confirm-modal');
                const panel = document.getElementById('sign-panel');
                let drawing = false, dirty = false;

                // Canvas butuh ukuran piksel eksplisit; ikuti lebar render + devicePixelRatio
                // biar hasil tanda tangan tidak buram di layar HiDPI.
                const resize = () => {
                    const ratio = window.devicePixelRatio || 1;
                    canvas.width = canvas.clientWidth * ratio;
                    canvas.height = 220 * ratio;
                    ctx.scale(ratio, ratio);
                    ctx.lineWidth = 2.5;
                    ctx.lineCap = 'round';
                    ctx.lineJoin = 'round';
                    ctx.strokeStyle = '#111827';
                };

                const pos = (e) => {
                    const r = canvas.getBoundingClientRect();
                    return [e.clientX - r.left, e.clientY - r.top];
                };

                document.getElementById('to-sign').addEventListener('click', () => {
                    panel.classList.remove('hidden');
                    // Canvas harus punya lebar terukur dulu, jadi resize SETELAH panel terlihat.
                    resize();
                    dirty = false;
                    panel.scrollIntoView({ behavior: 'smooth', block: 'start' });
                });

                window.addEventListener('resize', () => {
                    if (panel.classList.contains('hidden')) return;
                    resize();
                    dirty = false;
                });

                canvas.addEventListener('pointerdown', (e) => {
                    drawing = true; dirty = true;
                    ctx.beginPath();
                    ctx.moveTo(...pos(e));
                    canvas.setPointerCapture(e.pointerId);
                });
                canvas.addEventListener('pointermove', (e) => {
                    if (!drawing) return;
                    ctx.lineTo(...pos(e));
                    ctx.stroke();
                });
                canvas.addEventListener('pointerup', () => { drawing = false; });

                document.getElementById('sign-clear').addEventListener('click', () => {
                    ctx.clearRect(0, 0, canvas.width, canvas.height);
                    dirty = false;
                });

                form.addEventListener('submit', (e) => {
                    if (!form.dataset.confirmed) {
                        e.preventDefault();

                        if (!dirty) {
                            alert('Mohon bubuhkan tanda tangan terlebih dahulu.');
                            return;
                        }
                        if (!form.querySelector('[name=agree]').checked) {
                            alert('Mohon centang persetujuan terlebih dahulu.');
                            return;
                        }

                        modal.classList.remove('hidden');
                        return;
                    }

                    document.getElementById('sign-data').value = canvas.toDataURL('image/png');
                });

                document.getElementById('confirm-cancel').addEventListener('click', () => modal.classList.add('hidden'));
                document.getElementById('confirm-ok').addEventListener('click', () => {
                    modal.classList.add('hidden');
                    form.dataset.confirmed = '1';
                    form.requestSubmit();
                });
            })();
        </script>
    @endif

    {{-- ───────── STEP 4: Selesai ───────── --}}
    @if ($step === 4)
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6 sm:p-10 text-center">
            <div class="mx-auto w-16 h-16 rounded-full bg-green-100 flex items-center justify-center mb-5">
                <span class="text-3xl text-green-600">✓</span>
            </div>

            <p class="text-2xl font-bold text-gray-900">Tanda tangan berhasil!</p>
            <p class="text-sm text-gray-500 mt-2 max-w-md mx-auto">
                SPK Anda sudah tersimpan dan otomatis terkirim ke tim Beyond Viral. Tidak perlu upload apa pun.
            </p>

            <div class="mt-7 rounded-2xl border border-gray-100 bg-gray-50 divide-y divide-gray-100 text-left">
                <div class="flex items-center justify-between px-5 py-3.5">
                    <span class="text-sm text-gray-500">No. SPK</span>
                    <span class="text-sm font-semibold text-gray-900">{{ $spk->spk_number }}</span>
                </div>
                <div class="flex items-center justify-between px-5 py-3.5">
                    <span class="text-sm text-gray-500">Status</span>
                    <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-semibold bg-green-100 text-green-800">
                        SIGNED
                    </span>
                </div>
                <div class="flex items-center justify-between px-5 py-3.5">
                    <span class="text-sm text-gray-500">Ditandatangani</span>
                    <span class="text-sm font-semibold text-gray-900">
                        {{ $spk->signed_at?->translatedFormat('d M Y H.i') }}
                    </span>
                </div>
            </div>

            <a href="{{ route('spk.public.download', ['token' => $spk->public_token]) }}"
               class="bv-btn mt-6 w-full inline-flex items-center justify-center gap-2 text-sm">
                ⬇️ Download Dokumen Bertanda Tangan
            </a>

            <p class="text-xs text-gray-400 mt-5">
                Simpan No. SPK Anda untuk menanyakan status pembayaran ke tim Beyond Viral.
            </p>
        </div>
    @endif

    <p class="text-xs text-gray-300 text-center pb-4">
        Dokumen ini sah tanpa materai apabila ditandatangani secara digital oleh pihak berwenang.
    </p>
</main>

</body>
</html>
