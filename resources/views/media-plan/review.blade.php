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
                Mohon tandai KOL / SOW mana saja yang <strong>dipakai</strong> (✓) atau <strong>tidak dipakai</strong> (✗)
                untuk campaign ini. Tambahkan catatan / feedback bila ada, lalu klik <strong>Submit Review</strong> di bawah.
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
                <div class="px-6 py-4 border-b border-gray-100">
                    <h2 class="text-base font-semibold text-gray-900">Ringkasan Pilihan Anda</h2>
                </div>
                <div class="divide-y divide-gray-100">
                    @foreach ($groupedItems as $group)
                        <div class="px-6 py-4">
                            <p class="text-sm font-semibold text-gray-900">{{ $group['kol_name'] }}</p>
                            @include('media-plan.partials.kol-stats', ['kol' => $group['kol']])
                            <div class="mt-2"></div>
                            @foreach ($group['items'] as $item)
                                <div class="flex items-start justify-between gap-3 py-1.5">
                                    <div class="text-sm text-gray-600">
                                        {{ $item->scope_item }}
                                        <span class="text-gray-400">·</span>
                                        <span class="text-gray-900 font-medium">Rp {{ number_format($item->rounded ?? 0, 0, ',', '.') }}</span>
                                        @if ($item->client_feedback)
                                            <p class="text-xs text-gray-500 mt-0.5 italic">“{{ $item->client_feedback }}”</p>
                                        @endif
                                    </div>
                                    @php
                                        $choice = $item->client_choice;
                                        $badge = match ($choice) {
                                            'approved' => ['✓ Dipakai', 'bg-green-100 text-green-800'],
                                            'rejected' => ['✗ Tidak', 'bg-red-100 text-red-800'],
                                            default    => ['— Belum dipilih', 'bg-gray-100 text-gray-600'],
                                        };
                                    @endphp
                                    <span class="shrink-0 inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $badge[1] }}">
                                        {{ $badge[0] }}
                                    </span>
                                </div>
                            @endforeach
                        </div>
                    @endforeach
                </div>
            </div>
        @else
            {{-- Review form --}}
            <form method="POST" action="{{ route('media-plan-external.review.submit', ['token' => $budget->review_token]) }}">
                @csrf

                <div class="space-y-4">
                    @foreach ($groupedItems as $group)
                        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
                            <div class="px-6 py-3 border-b border-gray-100 bg-gray-50">
                                <p class="text-sm font-semibold text-gray-900">{{ $group['kol_name'] }}</p>
                                @include('media-plan.partials.kol-stats', ['kol' => $group['kol']])
                            </div>
                            <div class="divide-y divide-gray-100">
                                @foreach ($group['items'] as $item)
                                    <div class="px-6 py-4">
                                        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
                                            <div>
                                                <p class="text-sm font-medium text-gray-900">{{ $item->scope_item }}</p>
                                                <p class="text-xs text-gray-500 mt-0.5">
                                                    Harga: Rp {{ number_format($item->rounded ?? 0, 0, ',', '.') }}
                                                </p>
                                            </div>
                                            <div class="flex items-center gap-2">
                                                <label class="cursor-pointer">
                                                    <input type="radio" name="choices[{{ $item->id }}]" value="approved"
                                                           class="peer sr-only"
                                                           {{ $item->client_choice === 'approved' ? 'checked' : '' }}>
                                                    <span class="inline-flex items-center gap-1 px-3 py-1.5 rounded-lg border border-gray-200 text-sm text-gray-600 peer-checked:bg-green-600 peer-checked:text-white peer-checked:border-green-600 transition">
                                                        ✓ Dipakai
                                                    </span>
                                                </label>
                                                <label class="cursor-pointer">
                                                    <input type="radio" name="choices[{{ $item->id }}]" value="rejected"
                                                           class="peer sr-only"
                                                           {{ $item->client_choice === 'rejected' ? 'checked' : '' }}>
                                                    <span class="inline-flex items-center gap-1 px-3 py-1.5 rounded-lg border border-gray-200 text-sm text-gray-600 peer-checked:bg-red-600 peer-checked:text-white peer-checked:border-red-600 transition">
                                                        ✗ Tidak
                                                    </span>
                                                </label>
                                            </div>
                                        </div>
                                        <textarea name="feedback[{{ $item->id }}]" rows="1"
                                                  placeholder="Feedback / catatan (opsional)…"
                                                  class="mt-3 w-full text-sm rounded-lg border border-gray-200 px-3 py-2 focus:border-indigo-400 focus:ring-1 focus:ring-indigo-400 outline-none">{{ $item->client_feedback }}</textarea>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endforeach
                </div>

                <div class="mt-6 flex justify-end">
                    <button type="submit"
                            class="inline-flex items-center gap-2 bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-semibold rounded-xl px-6 py-3 transition shadow-sm">
                        Submit Review
                    </button>
                </div>
            </form>
        @endif

        <p class="text-center text-xs text-gray-400 pt-4">© {{ now()->year }} Beyond Viral Indonesia</p>
    </main>
</body>
</html>
