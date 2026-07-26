{{-- Riwayat versi konten storyline + feedback client. $storyline = CampaignStoryline --}}
@php
    $contents = $storyline->contents()->reorder()->orderByDesc('revision_number')->get();
    $max = \App\Models\CampaignStoryline::MAX_REVISIONS;
@endphp

<div class="space-y-4">
    <p class="text-xs text-gray-500">
        Revisi terpakai: <strong>{{ $storyline->revisionCount() }}/{{ $max }}</strong>
        · Sisa {{ $storyline->remainingRevisions() }}x
    </p>

    @forelse ($contents as $content)
        @php
            $badge = match ($content->client_choice) {
                'approved' => ['✓ Disetujui client', 'background:#dcfce7;color:#166534;'],
                'revision' => ['↻ Diminta revisi', 'background:#fee2e2;color:#991b1b;'],
                default => ['● Menunggu review client', 'background:#fef3c7;color:#92400e;'],
            };
        @endphp
        <div style="border:1px solid #e5e7eb;border-radius:12px;padding:14px;">
            <div style="display:flex;justify-content:space-between;align-items:center;gap:8px;flex-wrap:wrap;">
                <p style="font-size:13px;font-weight:600;color:#111827;">{{ $content->label }}</p>
                <span style="font-size:11px;font-weight:600;border-radius:9999px;padding:2px 8px;{{ $badge[1] }}">{{ $badge[0] }}</span>
            </div>
            <p style="font-size:11px;color:#9ca3af;margin-top:2px;">
                Dikirim {{ $content->submitted_at?->translatedFormat('d M Y, H:i') ?? '—' }}
                @if ($content->reviewed_at) · Direview {{ $content->reviewed_at->translatedFormat('d M Y, H:i') }} @endif
            </p>

            @if (!empty($content->images))
                <div style="display:flex;flex-wrap:wrap;gap:6px;margin-top:10px;">
                    @foreach ($content->images as $image)
                        <a href="{{ Storage::disk('public')->url($image) }}" target="_blank" rel="noopener noreferrer">
                            <img src="{{ Storage::disk('public')->url($image) }}" alt="Konten"
                                 style="height:76px;width:76px;object-fit:cover;border-radius:8px;border:1px solid #e5e7eb;">
                        </a>
                    @endforeach
                </div>
            @endif

            @if ($content->content_link)
                <p style="margin-top:10px;font-size:12px;">
                    <a href="{{ $content->content_link }}" target="_blank" rel="noopener noreferrer"
                       style="color:#4f46e5;text-decoration:underline;word-break:break-all;">{{ $content->content_link }} ↗</a>
                </p>
            @endif

            @if ($content->caption_draft)
                <p style="margin-top:10px;font-size:12px;color:#374151;white-space:pre-line;">{{ $content->caption_draft }}</p>
            @endif

            @if ($content->client_feedback)
                <div style="margin-top:10px;background:#f9fafb;border-radius:8px;padding:8px 10px;">
                    <p style="font-size:11px;font-weight:600;color:#6b7280;">Feedback client</p>
                    <p style="font-size:12px;color:#374151;font-style:italic;">“{{ $content->client_feedback }}”</p>
                </div>
            @endif
        </div>
    @empty
        <p class="text-sm text-gray-500">Belum ada konten yang dikirim ke client.</p>
    @endforelse
</div>
