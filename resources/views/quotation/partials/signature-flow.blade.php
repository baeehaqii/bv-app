{{-- Alur tanda tangan quotation: CEO → Business Development → Client. $quotation = BvQuotation --}}
@php
    $flow = \App\Models\BvQuotation::SIGN_FLOW;
    $signatures = $quotation->signatures ?? [];
    $next = $quotation->nextSigner();
@endphp

<div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6 sm:p-8">
    <p class="text-xs font-semibold text-gray-400 uppercase tracking-wide mb-2">Pengesahan</p>
    <p class="text-xs text-gray-400 mb-6">
        Ditandatangani berurutan: CEO → Business Development → Client. Dengan menandatangani dokumen ini,
        para pihak menyatakan menyetujui seluruh rincian penawaran di atas.
    </p>

    @if (session('signed') || $quotation->isFullySigned())
        <div class="bg-green-50 border border-green-200 rounded-xl p-4 mb-6 text-center">
            <p class="text-sm font-semibold text-green-800">✅ Quotation telah ditandatangani lengkap</p>
            <p class="text-xs text-green-700 mt-0.5">Terima kasih. Tim Beyond Viral akan menindaklanjuti.</p>
        </div>
    @endif

    <div class="grid grid-cols-1 sm:grid-cols-3 gap-8">
        @foreach ($flow as $role => $label)
            @php
                $sig = $signatures[$role] ?? null;
                $isSigned = filled($sig['at'] ?? null);
                $isNext = $next === $role;
                $badge = $isSigned
                    ? ['✓ Ditandatangani', 'bg-green-100 text-green-800']
                    : ($isNext ? ['● Menunggu tanda tangan', 'bg-amber-100 text-amber-800'] : ['— Belum', 'bg-gray-100 text-gray-500']);
            @endphp
            <div class="text-center">
                <p class="text-xs font-semibold text-gray-500 uppercase tracking-wide mb-3">{{ $label }}</p>
                <div class="border rounded-xl h-28 mb-3 flex items-center justify-center overflow-hidden {{ $isSigned ? 'border-gray-200' : 'border-dashed border-gray-300' }}">
                    @if (!empty($sig['image']))
                        <img src="{{ Storage::disk('public')->url($sig['image']) }}"
                             alt="Tanda Tangan {{ $label }}" class="max-h-full max-w-full object-contain p-2">
                    @elseif ($isSigned)
                        <span class="text-xs text-gray-400 italic">Ditandatangani digital</span>
                    @else
                        <span class="text-xs text-gray-300">Tanda Tangan</span>
                    @endif
                </div>
                <div class="border-t border-gray-200 pt-2">
                    <p class="text-xs font-semibold text-gray-800">{{ $sig['name'] ?? '___________________' }}</p>
                    <p class="text-xs text-gray-500 mt-0.5">{{ $sig['job_title'] ?? $label }}</p>
                    <p class="text-xs text-gray-400 mt-1">
                        {{ $isSigned ? \Illuminate\Support\Carbon::parse($sig['at'])->translatedFormat('d M Y, H:i') : 'Tanggal: _______________' }}
                    </p>
                    <span class="mt-2 inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-medium {{ $badge[1] }}">
                        {{ $badge[0] }}
                    </span>
                </div>
            </div>
        @endforeach
    </div>

    @if ($next === 'client')
        {{-- Form tanda tangan client (canvas, tanpa library) --}}
        <form method="POST" action="{{ route('quotation.public.sign', ['token' => $quotation->public_token]) }}"
              class="mt-8 border-t border-gray-100 pt-6 no-print" id="sign-form">
            @csrf
            <p class="text-sm font-semibold text-gray-900 mb-4">Tanda Tangan Client</p>

            @if ($errors->any())
                <div class="mb-4 bg-red-50 border border-red-200 rounded-lg p-3">
                    @foreach ($errors->all() as $error)
                        <p class="text-xs text-red-700">{{ $error }}</p>
                    @endforeach
                </div>
            @endif

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-medium text-gray-600 mb-1">Nama Penanda Tangan *</label>
                    <input type="text" name="name" required value="{{ old('name', $quotation->client_name) }}"
                           class="bv-input w-full text-sm rounded-lg border border-gray-200 px-3 py-2 outline-none">
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-600 mb-1">Jabatan</label>
                    <input type="text" name="job_title" value="{{ old('job_title') }}" placeholder="Mis. Marketing Manager"
                           class="bv-input w-full text-sm rounded-lg border border-gray-200 px-3 py-2 outline-none">
                </div>
            </div>

            <div class="mt-4">
                <div class="flex items-center justify-between mb-1">
                    <label class="block text-xs font-medium text-gray-600">Gambar Tanda Tangan *</label>
                    <button type="button" id="sign-clear" class="text-xs text-gray-500 hover:text-gray-800 underline">Hapus</button>
                </div>
                <canvas id="sign-pad" height="160"
                        class="w-full bg-white border border-dashed border-gray-300 rounded-xl touch-none cursor-crosshair"></canvas>
                <p class="text-xs text-gray-400 mt-1">Tanda tangan dengan mouse atau jari di area di atas.</p>
                <input type="hidden" name="signature" id="sign-data">
            </div>

            <label class="mt-4 flex items-start gap-2 text-xs text-gray-600">
                <input type="checkbox" name="agree" value="1" required class="mt-0.5">
                <span>Saya menyetujui seluruh rincian penawaran pada quotation ini.</span>
            </label>

            <div class="mt-5 flex justify-end">
                <button type="submit" class="bv-btn inline-flex items-center gap-2 text-sm">
                    Setuju & Tanda Tangani
                </button>
            </div>
        </form>

        <script>
            (() => {
                const canvas = document.getElementById('sign-pad');
                const ctx = canvas.getContext('2d');
                let drawing = false, dirty = false;

                // Canvas butuh ukuran piksel eksplisit; ikuti lebar render + devicePixelRatio biar tidak buram.
                const resize = () => {
                    const ratio = window.devicePixelRatio || 1;
                    const w = canvas.clientWidth;
                    canvas.width = w * ratio;
                    canvas.height = 160 * ratio;
                    ctx.scale(ratio, ratio);
                    ctx.lineWidth = 2;
                    ctx.lineCap = 'round';
                    ctx.strokeStyle = '#111827';
                };
                resize();
                window.addEventListener('resize', () => { resize(); dirty = false; });

                const pos = (e) => {
                    const r = canvas.getBoundingClientRect();
                    return [e.clientX - r.left, e.clientY - r.top];
                };

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

                document.getElementById('sign-form').addEventListener('submit', (e) => {
                    if (!dirty) {
                        e.preventDefault();
                        alert('Mohon isi gambar tanda tangan terlebih dahulu.');
                        return;
                    }
                    document.getElementById('sign-data').value = canvas.toDataURL('image/png');
                });
            })();
        </script>
    @elseif ($next)
        <div class="mt-8 border-t border-gray-100 pt-6 text-center">
            <p class="text-sm text-gray-600">
                Menunggu tanda tangan <strong>{{ $flow[$next] }}</strong> dari pihak Beyond Viral.
                Form tanda tangan client akan muncul di halaman ini setelahnya.
            </p>
        </div>
    @endif

    <p class="text-xs text-gray-300 text-center mt-6">
        Dokumen ini sah tanpa materai apabila ditandatangani secara digital oleh pihak berwenang.
    </p>
</div>
