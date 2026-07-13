@php
    $steps = $this->getSteps();
    $summary = $this->getSummary();
    $remaining = $summary['requiredTotal'] - $summary['requiredDone'];
@endphp

@push('styles')
    <style>
        /* Modal auto-open ini render sebagai "absolute positioning context"
           (position:static, z-index:auto) sehingga topbar (z-40) menimpa
           bagian atasnya. Paksa jadi overlay fixed di atas topbar. */
        .fi-modal.fi-absolute-positioning-context {
            position: fixed !important;
            inset: 0 !important;
            z-index: 50 !important;
            overflow-y: auto !important;
        }
        /* geser window ke bawah topbar (tinggi 80px) supaya heading tak tertutup */
        .fi-modal.fi-absolute-positioning-context .fi-modal-window {
            margin-top: 6rem !important;
        }

        .sbv-card {
            border-radius: 0.75rem;
            background: #fff;
            padding: 1.5rem;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.08);
            ring: 1px solid rgba(0, 0, 0, 0.05);
        }
        .dark .sbv-card { background: var(--gray-900); }

        .sbv-head {
            display: flex;
            flex-direction: column;
            gap: 1rem;
        }
        @media (min-width: 640px) {
            .sbv-head { flex-direction: row; align-items: center; justify-content: space-between; }
        }
        .sbv-head h2 { font-size: 1.125rem; font-weight: 600; color: var(--gray-950); }
        .dark .sbv-head h2 { color: #fff; }
        .sbv-head p { margin-top: 0.25rem; font-size: 0.875rem; color: var(--gray-500); }

        .sbv-status {
            display: inline-flex;
            align-items: center;
            gap: 0.375rem;
            border-radius: 0.375rem;
            padding: 0.25rem 0.625rem;
            font-size: 0.875rem;
            font-weight: 500;
        }
        .sbv-status--ok { background: color-mix(in srgb, var(--success-500) 12%, transparent); color: var(--success-600); }
        .sbv-status--warn { background: color-mix(in srgb, var(--warning-500) 15%, transparent); color: var(--warning-600); }

        .sbv-steps {
            display: flex;
            flex-direction: column;
            /* daftar panjang scroll di dalam modal, heading & footer tetap terlihat.
               dvh - 18rem menyisakan ruang untuk heading + footer + padding modal,
               jadi total modal tidak pernah lebih tinggi dari layar (top tak ke-clip). */
            max-height: calc(100dvh - 22rem);
            overflow-y: auto;
            padding-right: 0.5rem;
        }
        .sbv-step { position: relative; display: flex; gap: 1rem; padding-bottom: 0.85rem; }
        .sbv-step:last-child { padding-bottom: 0; }
        .sbv-rail {
            position: absolute;
            left: 1rem;
            top: 2.25rem;
            bottom: 0;
            width: 1px;
            background: var(--gray-200);
        }
        .dark .sbv-rail { background: rgba(255, 255, 255, 0.1); }

        .sbv-badge {
            position: relative;
            z-index: 1;
            display: flex;
            height: 2rem;
            width: 2rem;
            flex-shrink: 0;
            align-items: center;
            justify-content: center;
            border-radius: 9999px;
            font-size: 0.875rem;
            font-weight: 600;
        }
        .sbv-badge--done { background: var(--success-500); color: #fff; }
        .sbv-badge--todo {
            background: var(--gray-100);
            color: var(--gray-500);
            box-shadow: inset 0 0 0 1px var(--gray-200);
        }
        .dark .sbv-badge--todo { background: rgba(255, 255, 255, 0.05); color: var(--gray-400); box-shadow: inset 0 0 0 1px rgba(255, 255, 255, 0.1); }

        .sbv-body {
            display: flex;
            flex: 1 1 0;
            flex-direction: column;
            gap: 0.5rem;
        }
        @media (min-width: 640px) {
            .sbv-body { flex-direction: row; align-items: center; justify-content: space-between; }
        }
        .sbv-title { display: flex; align-items: center; gap: 0.5rem; font-weight: 500; color: var(--gray-950); }
        .dark .sbv-title { color: #fff; }
        .sbv-desc { margin-top: 0.125rem; font-size: 0.875rem; color: var(--gray-500); }
        .sbv-ico { height: 1rem; width: 1rem; color: var(--gray-400); }
        .sbv-pill {
            border-radius: 0.25rem;
            background: var(--gray-100);
            padding: 0.125rem 0.375rem;
            font-size: 0.75rem;
            font-weight: 500;
            color: var(--gray-500);
        }
        .dark .sbv-pill { background: rgba(255, 255, 255, 0.05); color: var(--gray-400); }
    </style>
@endpush

<x-filament-panels::page>
    {{-- Ringkasan + tombol buka wizard (fallback saat modal ditutup) --}}
    <div class="sbv-card">
        <div class="sbv-head">
            <div>
                <h2>Setup awal sistem</h2>
                <p>
                    {{ $summary['requiredDone'] }}/{{ $summary['requiredTotal'] }} langkah wajib selesai
                    &middot; {{ $summary['done'] }}/{{ $summary['total'] }} total.
                </p>
            </div>

            <div style="display:flex;align-items:center;gap:0.75rem;">
                @if ($summary['ready'])
                    <span class="sbv-status sbv-status--ok">
                        <x-filament::icon icon="heroicon-m-check-circle" style="height:1rem;width:1rem;" />
                        Sistem siap
                    </span>
                @else
                    <span class="sbv-status sbv-status--warn">{{ $remaining }} langkah wajib tersisa</span>
                @endif

                <x-filament::button x-on:click="$dispatch('open-modal', { id: 'setup-bv-system' })" icon="heroicon-m-rocket-launch">
                    Buka Setup
                </x-filament::button>
            </div>
        </div>
    </div>

    {{-- Auto-buka modal saat halaman dimuat --}}
    <div x-data x-init="$nextTick(() => $dispatch('open-modal', { id: 'setup-bv-system' }))"></div>

    <x-filament::modal id="setup-bv-system" width="5xl" :close-by-clicking-away="true" icon="heroicon-o-rocket-launch">
        <x-slot name="heading">Setup Beyond Viral System</x-slot>
        <x-slot name="description">
            Lengkapi langkah wajib berikut agar alur Sales &rarr; Media Plan &rarr; Campaign berjalan.
            Urutan bebas. Struktur Organisasi opsional.
        </x-slot>

        <ol class="sbv-steps">
            @foreach ($steps as $i => $step)
                <li class="sbv-step">
                    @unless ($loop->last)
                        <span class="sbv-rail"></span>
                    @endunless

                    <span class="sbv-badge {{ $step['done'] ? 'sbv-badge--done' : 'sbv-badge--todo' }}">
                        @if ($step['done'])
                            <x-filament::icon icon="heroicon-m-check" style="height:1.25rem;width:1.25rem;" />
                        @else
                            {{ $i + 1 }}
                        @endif
                    </span>

                    <div class="sbv-body">
                        <div style="min-width:0;">
                            <div class="sbv-title">
                                <x-filament::icon :icon="$step['icon']" class="sbv-ico" />
                                <span>{{ $step['label'] }}</span>
                                @unless ($step['required'])
                                    <span class="sbv-pill">Opsional</span>
                                @endunless
                            </div>
                            <p class="sbv-desc">{{ $step['desc'] }}</p>
                        </div>

                        <div style="flex-shrink:0;">
                            <x-filament::button
                                tag="a"
                                :href="$step['url']"
                                size="sm"
                                :color="$step['done'] ? 'gray' : 'primary'"
                                :outlined="$step['done']"
                                :icon="$step['done'] ? 'heroicon-m-pencil-square' : 'heroicon-m-arrow-right'"
                                icon-position="after"
                            >
                                {{ $step['done'] ? 'Ubah' : 'Isi Data' }}
                            </x-filament::button>
                        </div>
                    </div>
                </li>
            @endforeach
        </ol>

        <x-slot name="footerActions">
            @if ($summary['ready'])
                <x-filament::button tag="a" :href="filament()->getUrl()" icon="heroicon-m-check-circle">
                    Selesai, masuk Dashboard
                </x-filament::button>
            @else
                <x-filament::button color="gray" disabled>
                    {{ $remaining }} langkah wajib tersisa
                </x-filament::button>
            @endif
        </x-slot>
    </x-filament::modal>
</x-filament-panels::page>
