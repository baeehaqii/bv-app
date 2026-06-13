<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $campaignName }} — Approval Konten | Beyond Viral</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Inter', sans-serif; }
    </style>
</head>
<body class="bg-gray-50 min-h-screen">

    <header class="bg-white border-b border-gray-200 sticky top-0 z-10 shadow-sm">
        <div class="max-w-4xl mx-auto px-4 sm:px-6 py-4 flex items-center gap-3">
            <img src="https://res.cloudinary.com/dbr6xazzh/image/upload/v1763576028/01_Logo_Main_f2t5wp.avif"
                 alt="Beyond Viral" class="h-8 object-contain">
            <span class="text-gray-300">|</span>
            <span class="text-sm text-gray-500 font-medium">Approval Konten</span>
        </div>
    </header>

    <main class="max-w-4xl mx-auto px-4 sm:px-6 py-8 space-y-6">

        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6 sm:p-8">
            <p class="text-2xl font-bold text-gray-900">{{ $campaignName }}</p>
            <div class="mt-4 grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <p class="text-xs font-semibold text-gray-400 uppercase tracking-wide mb-1">Client</p>
                    <p class="text-sm font-semibold text-gray-900">{{ $clientName }}</p>
                </div>
                @if ($campaign->start_date)
                    <div>
                        <p class="text-xs font-semibold text-gray-400 uppercase tracking-wide mb-1">Periode</p>
                        <p class="text-sm font-semibold text-gray-900">
                            {{ $campaign->start_date?->format('d M Y') }}
                            — {{ $campaign->end_date?->format('d M Y') ?? '-' }}
                        </p>
                    </div>
                @endif
            </div>
            <p class="mt-5 text-sm text-gray-600 leading-relaxed">
                Mohon review draft konten berikut. Tandai <strong>✓ Approve</strong> jika sudah sesuai,
                atau <strong>↻ Revisi</strong> bila perlu perubahan. Tambahkan catatan bila ada, lalu klik
                <strong>Kirim Approval</strong> di bawah.
            </p>
        </div>

        @if ((session('submitted') || $alreadySubmitted))
            <div class="bg-green-50 border border-green-200 rounded-2xl p-6 text-center">
                <p class="text-lg font-semibold text-green-800">✅ Approval telah dikirim</p>
                <p class="text-sm text-green-700 mt-1">
                    Terima kasih. Keputusan Anda sudah kami terima
                    @if ($campaign->content_review_submitted_at)
                        pada {{ $campaign->content_review_submitted_at->format('d M Y, H:i') }}
                    @endif. Tim Beyond Viral akan menindaklanjuti.
                </p>
            </div>

            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-100">
                    <h2 class="text-base font-semibold text-gray-900">Ringkasan Keputusan Anda</h2>
                </div>
                <div class="divide-y divide-gray-100">
                    @foreach ($storylines as $s)
                        <div class="px-6 py-4 flex items-start justify-between gap-3">
                            <div class="text-sm text-gray-600">
                                <span class="font-semibold text-gray-900">{{ $s->kol_name }}</span>
                                <span class="text-gray-400">·</span> {{ $s->sow ?: ucfirst($s->platform) }}
                                @if ($s->content_angle)
                                    <p class="text-xs text-gray-500 mt-0.5">{{ $s->content_angle }}</p>
                                @endif
                                @if ($s->client_feedback)
                                    <p class="text-xs text-gray-500 mt-0.5 italic">“{{ $s->client_feedback }}”</p>
                                @endif
                            </div>
                            @php
                                $badge = match ($s->client_choice) {
                                    'approved' => ['✓ Approve', 'bg-green-100 text-green-800'],
                                    'revision' => ['↻ Revisi', 'bg-red-100 text-red-800'],
                                    default    => ['— Belum', 'bg-gray-100 text-gray-600'],
                                };
                            @endphp
                            <span class="shrink-0 inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $badge[1] }}">
                                {{ $badge[0] }}
                            </span>
                        </div>
                    @endforeach
                </div>
            </div>
        @else
            <form method="POST" action="{{ route('campaign-internal.content-review.submit', ['token' => $campaign->content_review_token]) }}">
                @csrf
                <div class="space-y-4">
                    @forelse ($storylines as $s)
                        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
                            <div class="flex flex-col sm:flex-row sm:items-start sm:justify-between gap-3">
                                <div>
                                    <p class="text-sm font-semibold text-gray-900">{{ $s->kol_name }}</p>
                                    <p class="text-xs text-gray-500 mt-0.5">
                                        {{ \App\Models\CampaignStoryline::PLATFORMS[$s->platform] ?? ucfirst($s->platform) }}
                                        @if ($s->sow) · {{ $s->sow }} @endif
                                        @if ($s->posting_deadline) · Deadline {{ $s->posting_deadline->format('d M Y') }} @endif
                                    </p>
                                </div>
                                <div class="flex items-center gap-2">
                                    <label class="cursor-pointer">
                                        <input type="radio" name="choices[{{ $s->id }}]" value="approved" class="peer sr-only"
                                               {{ $s->client_choice === 'approved' ? 'checked' : '' }}>
                                        <span class="inline-flex items-center gap-1 px-3 py-1.5 rounded-lg border border-gray-200 text-sm text-gray-600 peer-checked:bg-green-600 peer-checked:text-white peer-checked:border-green-600 transition">
                                            ✓ Approve
                                        </span>
                                    </label>
                                    <label class="cursor-pointer">
                                        <input type="radio" name="choices[{{ $s->id }}]" value="revision" class="peer sr-only"
                                               {{ $s->client_choice === 'revision' ? 'checked' : '' }}>
                                        <span class="inline-flex items-center gap-1 px-3 py-1.5 rounded-lg border border-gray-200 text-sm text-gray-600 peer-checked:bg-red-600 peer-checked:text-white peer-checked:border-red-600 transition">
                                            ↻ Revisi
                                        </span>
                                    </label>
                                </div>
                            </div>

                            @if ($s->content_angle || $s->key_message || $s->caption_draft)
                                <div class="mt-4 space-y-2 text-sm">
                                    @if ($s->content_angle)
                                        <div><span class="text-xs font-semibold text-gray-400 uppercase">Angle</span><p class="text-gray-700">{{ $s->content_angle }}</p></div>
                                    @endif
                                    @if ($s->key_message)
                                        <div><span class="text-xs font-semibold text-gray-400 uppercase">Key Message</span><p class="text-gray-700">{{ $s->key_message }}</p></div>
                                    @endif
                                    @if ($s->caption_draft)
                                        <div><span class="text-xs font-semibold text-gray-400 uppercase">Caption Draft</span><p class="text-gray-700 whitespace-pre-line">{{ $s->caption_draft }}</p></div>
                                    @endif
                                </div>
                            @endif

                            <textarea name="feedback[{{ $s->id }}]" rows="1"
                                      placeholder="Feedback / catatan (opsional)…"
                                      class="mt-3 w-full text-sm rounded-lg border border-gray-200 px-3 py-2 focus:border-indigo-400 focus:ring-1 focus:ring-indigo-400 outline-none">{{ $s->client_feedback }}</textarea>
                        </div>
                    @empty
                        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-8 text-center text-gray-500">
                            Belum ada draft konten yang siap di-review.
                        </div>
                    @endforelse
                </div>

                @if ($storylines->isNotEmpty())
                    <div class="mt-6 flex justify-end">
                        <button type="submit"
                                class="inline-flex items-center gap-2 bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-semibold rounded-xl px-6 py-3 transition shadow-sm">
                            Kirim Approval
                        </button>
                    </div>
                @endif
            </form>
        @endif

        <p class="text-center text-xs text-gray-400 pt-4">© {{ now()->year }} Beyond Viral Indonesia</p>
    </main>
</body>
</html>
