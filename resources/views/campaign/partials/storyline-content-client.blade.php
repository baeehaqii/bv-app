{{-- Konten terbaru dari tim KOL + riwayat revisinya, untuk halaman approval client. $s = CampaignStoryline --}}
@php
    $latest = $s->latestContent();
    $images = $latest?->images ?: ($s->images ?? []);
    $link = $latest?->content_link ?: $s->content_link;
    $history = $s->contents()->where('revision_number', '<', $latest?->revision_number ?? 0)
        ->reorder()->orderByDesc('revision_number')->get();
@endphp

@if ($images || $link)
    <div class="mt-4">
        <div class="flex items-center gap-2 mb-2">
            <span class="text-xs font-semibold text-gray-400 uppercase">Konten</span>
            @if ($latest && $latest->revision_number > 0)
                <span class="text-[10px] font-medium px-2 py-0.5 rounded-full bg-amber-100 text-amber-800">
                    {{ $latest->label }} dari {{ \App\Models\CampaignStoryline::MAX_REVISIONS }}
                </span>
            @endif
        </div>

        @if ($images)
            <div class="grid grid-cols-3 sm:grid-cols-5 gap-2">
                @foreach ($images as $image)
                    <a href="{{ Storage::disk('public')->url($image) }}" target="_blank" rel="noopener noreferrer">
                        <img src="{{ Storage::disk('public')->url($image) }}" alt="Konten"
                             class="h-24 w-full object-cover rounded-lg border border-gray-200 hover:opacity-90 transition">
                    </a>
                @endforeach
            </div>
        @endif

        @if ($link)
            <p class="mt-2 text-sm">
                <a href="{{ $link }}" target="_blank" rel="noopener noreferrer"
                   class="text-indigo-600 hover:text-indigo-800 hover:underline break-all">{{ $link }} ↗</a>
            </p>
        @endif
    </div>
@endif

@if ($history->isNotEmpty())
    <details class="mt-3">
        <summary class="cursor-pointer text-xs font-semibold text-indigo-600 select-none">
            Riwayat perbaikan ({{ $history->count() }} versi sebelumnya)
        </summary>
        <div class="mt-2 space-y-3">
            @foreach ($history as $old)
                <div class="border border-gray-100 rounded-xl p-3 bg-gray-50">
                    <div class="flex items-center justify-between gap-2 flex-wrap">
                        <p class="text-xs font-semibold text-gray-700">{{ $old->label }}</p>
                        <p class="text-[10px] text-gray-400">
                            {{ $old->submitted_at?->translatedFormat('d M Y, H:i') }}
                        </p>
                    </div>

                    @if (!empty($old->images))
                        <div class="mt-2 flex flex-wrap gap-1.5">
                            @foreach ($old->images as $image)
                                <a href="{{ Storage::disk('public')->url($image) }}" target="_blank" rel="noopener noreferrer">
                                    <img src="{{ Storage::disk('public')->url($image) }}" alt="Konten lama"
                                         class="h-14 w-14 object-cover rounded-md border border-gray-200">
                                </a>
                            @endforeach
                        </div>
                    @endif

                    @if ($old->content_link)
                        <p class="mt-1.5 text-xs">
                            <a href="{{ $old->content_link }}" target="_blank" rel="noopener noreferrer"
                               class="text-indigo-600 hover:underline break-all">{{ $old->content_link }} ↗</a>
                        </p>
                    @endif

                    @if ($old->client_feedback)
                        <p class="mt-1.5 text-xs text-gray-600 italic">“{{ $old->client_feedback }}”</p>
                    @endif
                </div>
            @endforeach
        </div>
    </details>
@endif
