{{--
    Migrasi Data Client — tempel link, preview, lalu migrasi per-chunk.

    CSS ditulis manual dengan prefix .mig- : kelas Tailwind yang belum pernah
    dipakai di project ini tidak ikut ter-compile sampai `npm run build`, dan
    halamannya akan tampil tanpa gaya. Lihat memori project_filament_theme_build_gotcha.
--}}
@php
    $kolom = $this->profil()->previewColumns();
    $persen = $totalItems > 0 ? (int) round($processed / $totalItems * 100) : 0;
@endphp

<x-filament-panels::page>
    @push('styles')
        <style>
            .mig-card{border:1px solid rgba(128,128,128,.2);border-radius:.75rem;padding:1rem;background:var(--bg,transparent)}
            .mig-muted{color:rgba(128,128,128,.9);font-size:.85rem}
            .mig-stat{display:flex;gap:1.5rem;flex-wrap:wrap;margin-top:.5rem}
            .mig-stat b{display:block;font-size:1.35rem;line-height:1.1}
            .mig-table{width:100%;border-collapse:collapse;font-size:.8rem}
            .mig-table th{text-align:left;padding:.4rem .5rem;border-bottom:1px solid rgba(128,128,128,.25);white-space:nowrap}
            .mig-table td{padding:.35rem .5rem;border-bottom:1px solid rgba(128,128,128,.12);white-space:nowrap}
            .mig-warn{background:rgba(251,191,36,.12)}
            .mig-bar{height:.6rem;border-radius:9999px;background:rgba(128,128,128,.2);overflow:hidden}
            .mig-bar span{display:block;height:100%;background:var(--primary-500,#7c3aed);transition:width .2s}
            .mig-log{max-height:14rem;overflow:auto;font-family:ui-monospace,monospace;font-size:.75rem;line-height:1.5}
            .mig-chip{display:inline-block;padding:.1rem .5rem;border-radius:9999px;background:rgba(128,128,128,.18);font-size:.72rem;margin:0 .25rem .25rem 0}
        </style>
    @endpush

    <div
        x-data="{
            running: false,
            async run() {
                if (this.running) return;
                this.running = true;
                try {
                    while (! await $wire.processChunk()) { /* chunk berikutnya */ }
                } finally {
                    this.running = false;
                }
            }
        }"
        x-on:migrasi-client-run.window="run()"
        class="space-y-6"
    >
        @if ($errorMessage)
            <div class="mig-card" style="border-color:rgba(239,68,68,.5)">
                <strong>Gagal.</strong> {{ $errorMessage }}
            </div>
        @endif

        {{-- 1. Sumber --}}
        <div class="mig-card">
            <h3 style="font-weight:600;margin-bottom:.25rem">1. Sumber Spreadsheet</h3>
            <p class="mig-muted" style="margin-bottom:.75rem">
                Sheet privat dibaca lewat service account — tidak perlu Apps Script di file-nya.
                Pilih dari daftar spreadsheet yang sudah di-share ke service account, atau tempel
                link-nya langsung.
            </p>

            {{ $this->form }}

            <div style="margin-top:1rem;display:flex;gap:.5rem;align-items:center;flex-wrap:wrap">
                <x-filament::button wire:click="preview" wire:loading.attr="disabled">
                    Preview Data
                </x-filament::button>

                @if (($this->data['sumber'] ?? 'paste') === 'daftar')
                    <x-filament::button color="gray" wire:click="muatDaftarSpreadsheet(true)" wire:loading.attr="disabled">
                        Muat Ulang Daftar
                    </x-filament::button>
                @endif

                <span wire:loading wire:target="preview,muatDaftarSpreadsheet" class="mig-muted">Menghubungi Google…</span>
            </div>
        </div>

        {{-- 2. Preview --}}
        @if ($previewed)
            <div class="mig-card">
                <h3 style="font-weight:600">2. Preview</h3>

                <div class="mig-stat">
                    <div><span class="mig-muted">Baris terbaca</span><b>{{ number_format($totalItems) }}</b></div>
                    <div><span class="mig-muted">Perlu perhatian</span><b>{{ number_format($warnCount) }}</b></div>
                    <div><span class="mig-muted">Ditampilkan</span><b>{{ number_format(count($previewRows)) }}</b></div>
                </div>

                @if ($diabaikan)
                    <p class="mig-muted" style="margin-top:.75rem">
                        Kolom yang <strong>sengaja dilewati</strong> — angka turunan yang dihitung sistem,
                        kolom duplikat, atau yang belum punya padanan:
                        @foreach ($diabaikan as $judul)
                            <span class="mig-chip">{{ $judul }}</span>
                        @endforeach
                    </p>
                @endif

                @if ($unmapped)
                    <p class="mig-muted" style="margin-top:.5rem">
                        Kolom yang <strong>tidak dikenali</strong> dan tidak ikut dimigrasi:
                        @foreach ($unmapped as $judul)
                            <span class="mig-chip">{{ $judul }}</span>
                        @endforeach
                    </p>
                @endif

                <div style="overflow-x:auto;margin-top:.75rem">
                    <table class="mig-table">
                        <thead>
                            <tr>
                                <th>Baris</th>
                                @foreach ($kolom as $c)
                                    <th>{{ str($c)->replace('_', ' ')->title() }}</th>
                                @endforeach
                                <th>Catatan</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($previewRows as $row)
                                <tr @class(['mig-warn' => filled($row['_note'] ?? null)])>
                                    <td>{{ $row['_row'] }}</td>
                                    @foreach ($kolom as $c)
                                        <td>{{ $row[$c] ?? '—' }}</td>
                                    @endforeach
                                    <td class="mig-muted">{{ $row['_note'] ?? '' }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                @if ($totalItems > count($previewRows))
                    <p class="mig-muted" style="margin-top:.5rem">
                        Hanya {{ count($previewRows) }} baris pertama yang ditampilkan; yang dimigrasi tetap semuanya.
                    </p>
                @endif
            </div>

            {{-- 3. Migrasi --}}
            <div class="mig-card">
                <h3 style="font-weight:600">3. Migrasi</h3>
                <p class="mig-muted" style="margin-bottom:.75rem">
                    Menjalankan ulang memperbarui baris yang sama — bukan menggandakan. Sel kosong
                    di sheet tidak menimpa data yang sudah ada.
                    <strong>Jangan tutup tab</strong> selama migrasi berjalan.
                </p>

                @if (! $migrating && ! $finished)
                    <x-filament::button wire:click="startMigration" color="warning">
                        Migrasi Sekarang ({{ number_format($totalItems) }} baris)
                    </x-filament::button>
                @endif

                @if ($migrating || $finished)
                    <div class="mig-bar"><span style="width:{{ $persen }}%"></span></div>
                    <div class="mig-stat">
                        <div><span class="mig-muted">Diproses</span><b>{{ number_format($processed) }}/{{ number_format($totalItems) }}</b></div>
                        <div><span class="mig-muted">Tersimpan</span><b>{{ number_format($success) }}</b></div>
                        <div><span class="mig-muted">Dilewati</span><b>{{ number_format($skipped) }}</b></div>
                        <div><span class="mig-muted">Gagal</span><b>{{ number_format($failed) }}</b></div>
                    </div>

                    @if ($finished)
                        <p style="margin-top:.75rem;font-weight:600">Selesai.</p>
                    @endif
                @endif

                @if ($notes)
                    <div class="mig-log" style="margin-top:.75rem">
                        @foreach ($notes as $note)
                            <div>{{ $note }}</div>
                        @endforeach
                    </div>
                @endif
            </div>
        @endif
    </div>
</x-filament-panels::page>
