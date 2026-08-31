<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $campaignName }} — Review Media Plan | Beyond Viral</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Inter', sans-serif; }
    </style>
</head>
<body class="bg-gray-50 min-h-screen">

    {{-- Header --}}
    <header class="bg-white border-b border-gray-200 sticky top-0 z-10 shadow-sm">
        <div class="max-w-4xl mx-auto px-4 sm:px-6 py-4 flex items-center gap-3">
            <img src="https://res.cloudinary.com/dbr6xazzh/image/upload/v1763576028/01_Logo_Main_f2t5wp.avif"
                 alt="Beyond Viral" class="h-8 object-contain">
            <span class="text-gray-300">|</span>
            <span class="text-sm text-gray-500 font-medium">Review Media Plan</span>
        </div>
    </header>

    <main class="max-w-4xl mx-auto px-4 sm:px-6 py-8 space-y-6">

        {{-- Document Header --}}
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6 sm:p-8">
            <p class="text-2xl font-bold text-gray-900">{{ $campaignName }}</p>
            <div class="mt-4 grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <p class="text-xs font-semibold text-gray-400 uppercase tracking-wide mb-1">Client</p>
                    <p class="text-sm font-semibold text-gray-900">{{ $clientName }}</p>
                </div>
                @if ($budget->mediaPlan?->bvSales?->start_date)
                    <div>
                        <p class="text-xs font-semibold text-gray-400 uppercase tracking-wide mb-1">Periode</p>
                        <p class="text-sm font-semibold text-gray-900">
                            {{ $budget->mediaPlan->bvSales->start_date?->format('d M Y') }}
                            — {{ $budget->mediaPlan->bvSales->end_date?->format('d M Y') ?? '-' }}
                        </p>
                    </div>
                @endif
            </div>

            <p class="mt-5 text-sm text-gray-600 leading-relaxed">
                Mohon tandai KOL mana saja yang <strong>dipakai</strong> (✓) atau <strong>tidak dipakai</strong> (✗)
                untuk campaign ini. Klik nama scope of work-nya untuk melihat rincian tiap KOL.
                Tambahkan catatan / feedback bila ada, lalu klik <strong>Submit Review</strong> di bawah.
            </p>
        </div>

        @if (session('submitted') || $alreadySubmitted)
            {{-- Submitted state (read-only) --}}
            <div class="bg-green-50 border border-green-200 rounded-2xl p-6 text-center">
                <p class="text-lg font-semibold text-green-800">✅ Review telah disubmit</p>
                <p class="text-sm text-green-700 mt-1">
                    Terima kasih. Pilihan Anda sudah kami terima
                    @if ($budget->review_submitted_at)
                        pada {{ $budget->review_submitted_at->format('d M Y, H:i') }}
                    @endif. Tim Beyond Viral akan menindaklanjuti.
                </p>
            </div>

            {{-- Ringkasan pilihan client --}}
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
                <div class="px-5 py-4 border-b border-gray-100">
                    <h2 class="text-base font-semibold text-gray-900">Ringkasan Pilihan Anda</h2>
                </div>
                <div class="divide-y divide-gray-100">
                    @foreach ($groupedItems as $group)
                        @php
                            $badge = match ($group['choice']) {
                                'approved' => ['✓ Dipakai', 'bg-green-100 text-green-800'],
                                'rejected' => ['✗ Tidak', 'bg-red-100 text-red-800'],
                                default    => ['— Belum dipilih', 'bg-gray-100 text-gray-600'],
                            };
                        @endphp
                        <div class="px-5 py-3 flex flex-wrap items-start gap-x-3 gap-y-2">
                            <div class="min-w-0 flex-1">
                                @include('media-plan.partials.kol-baris-kepala', ['group' => $group])
                                @if ($group['feedback'])
                                    <p class="mt-1 text-xs text-gray-500 italic">“{{ $group['feedback'] }}”</p>
                                @endif
                                @if ($group['replace'])
                                    <p class="mt-1 text-xs text-amber-700">
                                        <span class="font-semibold">Usulan KOL pengganti:</span> {{ $group['replace'] }}
                                    </p>
                                @endif
                            </div>
                            <div class="flex shrink-0 items-center gap-2">
                                @include('media-plan.partials.sow-tombol', ['group' => $group])
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $badge[1] }}">
                                    {{ $badge[0] }}
                                </span>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        @else
            {{-- Review form --}}
            <form method="POST" action="{{ route('media-plan-external.review.submit', ['token' => $budget->review_token]) }}">
                @csrf

                @include('media-plan.partials.kol-kontrol')

                <div id="kol-daftar" class="bg-white rounded-2xl shadow-sm border border-gray-100 divide-y divide-gray-100 overflow-hidden">
                    @foreach ($groupedItems as $group)
                        {{-- data-cari dipakai kotak pencarian; sudah huruf kecil supaya
                             pencocokannya tidak perlu memproses ulang tiap ketikan. --}}
                        <div data-kol-baris
                             data-cari="{{ strtolower($group['kol_name'] . ' ' . $group['username'] . ' ' . $group['sow_utama']) }}"
                             class="px-5 py-3">
                            <div class="flex flex-wrap items-start gap-x-3 gap-y-2">
                                @include('media-plan.partials.kol-baris-kepala', ['group' => $group])

                                <div class="ml-auto flex shrink-0 items-center gap-1.5">
                                    @include('media-plan.partials.sow-tombol', ['group' => $group])

                                    {{-- Satu keputusan untuk seluruh SOW milik KOL ini. --}}
                                    <label class="cursor-pointer">
                                        <input type="radio" name="choices[{{ $group['key'] }}]" value="approved"
                                               class="peer sr-only"
                                               {{ $group['choice'] === 'approved' ? 'checked' : '' }}>
                                        <span class="inline-flex items-center px-2.5 py-1 rounded-lg border border-gray-200 text-xs font-medium text-gray-600 peer-checked:bg-green-600 peer-checked:text-white peer-checked:border-green-600 transition">
                                            ✓ Dipakai
                                        </span>
                                    </label>
                                    <label class="cursor-pointer">
                                        <input type="radio" name="choices[{{ $group['key'] }}]" value="rejected"
                                               class="peer sr-only"
                                               {{ $group['choice'] === 'rejected' ? 'checked' : '' }}>
                                        <span class="inline-flex items-center px-2.5 py-1 rounded-lg border border-gray-200 text-xs font-medium text-gray-600 peer-checked:bg-red-600 peer-checked:text-white peer-checked:border-red-600 transition">
                                            ✗ Tidak
                                        </span>
                                    </label>

                                    {{-- Usulan KOL pengganti dari client. Kolomnya disembunyikan
                                         sampai diminta — kalau selalu tampil, 98 KOL jadi 98
                                         kotak isian lagi. --}}
                                    <button type="button" data-ganti-tombol
                                            onclick="this.closest('[data-kol-baris]').querySelector('[data-ganti-isian]').classList.toggle('hidden')"
                                            title="Usulkan KOL pengganti"
                                            class="inline-flex items-center rounded-lg border border-gray-200 px-2 py-1 text-xs text-gray-500 hover:border-amber-300 hover:bg-amber-50 hover:text-amber-700 transition">
                                        ⇄
                                    </button>
                                </div>
                            </div>

                            <textarea name="feedback[{{ $group['key'] }}]" rows="1"
                                      placeholder="Feedback atau catatan dari Anda…"
                                      class="mt-2 w-full text-sm rounded-lg border border-gray-200 px-3 py-1.5 focus:border-indigo-400 focus:ring-1 focus:ring-indigo-400 outline-none">{{ $group['feedback'] }}</textarea>

                            <div data-ganti-isian class="{{ $group['replace'] ? '' : 'hidden' }}">
                                <textarea name="replace[{{ $group['key'] }}]" rows="1"
                                          placeholder="Punya KOL sendiri? Tulis nama / akunnya di sini…"
                                          class="mt-2 w-full text-sm rounded-lg border border-amber-200 bg-amber-50/50 px-3 py-1.5 focus:border-amber-400 focus:ring-1 focus:ring-amber-400 outline-none">{{ $group['replace'] }}</textarea>
                            </div>
                        </div>
                    @endforeach
                </div>

                <p id="kol-kosong" class="hidden py-8 text-center text-sm text-gray-400">
                    Tidak ada KOL yang cocok dengan pencarian Anda.
                </p>

                @include('media-plan.partials.kol-pager')

                <div class="mt-6 flex justify-end">
                    <button type="submit"
                            class="inline-flex items-center gap-2 bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-semibold rounded-xl px-6 py-3 transition shadow-sm">
                        Submit Review
                    </button>
                </div>
            </form>
        @endif

        {{-- Modal rincian SOW, satu per KOL. Ditaruh di luar <form> supaya tombol
             tutupnya (method="dialog") tidak ikut men-submit review. --}}
        @foreach ($groupedItems as $group)
            @include('media-plan.partials.sow-modal', ['group' => $group])
        @endforeach

        <p class="text-center text-xs text-gray-400 pt-4">© {{ now()->year }} Beyond Viral Indonesia</p>
    </main>
</body>
</html>
