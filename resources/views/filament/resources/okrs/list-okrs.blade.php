{{--
    Tata letak mengikuti template OKR Confluence: satu Objective per baris,
    dengan Key results, Owner, Partner with, skor, dan Current status per bulan.

    CSS ditulis di sini sebagai properti kustom, bukan kelas Tailwind baru:
    kelas yang belum pernah dipakai di project ini tidak ikut ter-compile sampai
    `npm run build` dijalankan, dan halamannya akan tampil tanpa gaya sama sekali.
--}}
<x-filament-panels::page>
    <style>
        .okr { --okr-garis: #e5e7eb; --okr-kepala: #f9fafb; --okr-redup: #6b7280; --okr-chip: #ecfdf5; --okr-chip-teks: #047857; }
        .dark .okr { --okr-garis: rgba(255,255,255,.1); --okr-kepala: rgba(255,255,255,.03); --okr-redup: #9ca3af; --okr-chip: rgba(16,185,129,.15); --okr-chip-teks: #6ee7b7; }

        .okr-meta { border: 1px solid var(--okr-garis); border-radius: .5rem; overflow: hidden; margin-bottom: 1.5rem; max-width: 44rem; }
        .okr-meta div { display: flex; border-bottom: 1px solid var(--okr-garis); }
        .okr-meta div:last-child { border-bottom: 0; }
        .okr-meta dt { flex: 0 0 11rem; padding: .625rem .875rem; font-weight: 600; background: var(--okr-kepala); border-right: 1px solid var(--okr-garis); }
        .okr-meta dd { padding: .625rem .875rem; color: var(--okr-redup); }

        .okr-filter { display: flex; flex-wrap: wrap; gap: .75rem; align-items: end; margin-bottom: 1rem; }
        .okr-filter label { display: block; font-size: .75rem; font-weight: 600; margin-bottom: .25rem; color: var(--okr-redup); }
        .okr-filter select { border: 1px solid var(--okr-garis); border-radius: .5rem; padding: .375rem 2rem .375rem .625rem; background-color: transparent; font-size: .875rem; }

        /* Tabelnya lebar; yang boleh menggeser cuma pembungkus ini, bukan halamannya. */
        .okr-gulung { overflow-x: auto; border: 1px solid var(--okr-garis); border-radius: .5rem; }
        .okr-tabel { width: 100%; border-collapse: collapse; font-size: .875rem; min-width: 60rem; }
        .okr-tabel th { text-align: left; font-weight: 700; padding: .75rem .875rem; background: var(--okr-kepala); border-bottom: 1px solid var(--okr-garis); vertical-align: bottom; }
        .okr-tabel td { padding: .875rem; border-bottom: 1px solid var(--okr-garis); vertical-align: top; }
        .okr-tabel tr:last-child td { border-bottom: 0; }

        .okr-objective { font-weight: 600; }
        .okr-baris-meta { margin-top: .75rem; font-size: .8125rem; color: var(--okr-redup); }
        .okr-baris-meta strong { color: inherit; font-weight: 700; }
        .okr-chip { display: inline-block; padding: .0625rem .375rem; border-radius: .25rem; background: var(--okr-chip); color: var(--okr-chip-teks); font-weight: 600; font-variant-numeric: tabular-nums; }
        .okr-kosong-chip { color: var(--okr-redup); font-weight: 400; }
        .okr-bulan { font-weight: 700; margin-bottom: .125rem; }
        .okr-bulan-blok + .okr-bulan-blok { margin-top: .875rem; }
        .okr-redup { color: var(--okr-redup); }
        .okr-nihil { padding: 2.5rem; text-align: center; color: var(--okr-redup); }
    </style>

    @php
        $ringkas = $this->ringkasan();
    @endphp

    <div class="okr">
        <dl class="okr-meta">
            <div>
                <dt>Periode</dt>
                <dd>Q{{ $this->quarter }} {{ $this->year }}</dd>
            </div>
            <div>
                <dt>Progress</dt>
                <dd>{{ $ringkas['selesai'] }} dari {{ $ringkas['total'] }} objective Done ({{ $ringkas['persen'] }}%)</dd>
            </div>
            <div>
                <dt>Cara mengisi</dt>
                <dd>Satu Objective per baris. Current status diperbarui sebelum weekly meeting, bukan saat rapat berlangsung.</dd>
            </div>
        </dl>

        <div class="okr-filter">
            <div>
                <label for="okr-tahun">Tahun</label>
                <select id="okr-tahun" wire:model.live="year">
                    @foreach ($this->pilihanTahun() as $tahun)
                        <option value="{{ $tahun }}">{{ $tahun }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label for="okr-kuartal">Kuartal</label>
                <select id="okr-kuartal" wire:model.live="quarter">
                    @foreach ([1, 2, 3, 4] as $k)
                        <option value="{{ $k }}">Q{{ $k }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label for="okr-pemilik">Pemilik</label>
                <select id="okr-pemilik" wire:model.live="owner">
                    <option value="">Semua</option>
                    @foreach ($this->pilihanPemilik() as $nama)
                        <option value="{{ $nama }}">{{ $nama }}</option>
                    @endforeach
                </select>
            </div>
        </div>

        <div class="okr-gulung">
            <table class="okr-tabel">
                <thead>
                    <tr>
                        <th style="width:24%">Objectives</th>
                        <th style="width:20%">Key results</th>
                        <th style="width:11%">Owner</th>
                        <th style="width:11%">Partner with</th>
                        <th style="width:9%">Expected EoQ key result score</th>
                        <th style="width:25%">Current status</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($this->baris() as $okr)
                        <tr wire:key="okr-{{ $okr->id }}">
                            <td>
                                <div class="okr-objective">{{ $okr->objective }}</div>

                                <div class="okr-baris-meta">
                                    <strong>Periode:</strong> {{ $okr->periode_label }}<br>
                                    <strong>Status:</strong> {{ $okr->status->getLabel() }}<br>
                                    <strong>End-of-quarter objective score:</strong>
                                    @if ($okr->objective_score !== null)
                                        <span class="okr-chip">{{ number_format((float) $okr->objective_score, 1) }}</span>
                                    @else
                                        <span class="okr-kosong-chip">belum dinilai</span>
                                    @endif
                                </div>

                                <div class="okr-baris-meta">
                                    <a href="{{ \App\Filament\Resources\Okrs\OkrResource::getUrl('edit', ['record' => $okr]) }}"
                                       class="fi-link" style="color:var(--primary-600,#7c3aed);font-weight:600">Edit</a>
                                </div>
                            </td>

                            <td>{!! nl2br(e($okr->key_results)) !!}</td>

                            <td>
                                {{ $okr->owner_name }}
                                @if ($okr->user)
                                    <div class="okr-redup" style="font-size:.75rem">{{ $okr->user->email }}</div>
                                @endif
                            </td>

                            <td>
                                @if ($okr->partner_with)
                                    {{ $okr->partner_with }}
                                @else
                                    <span class="okr-redup">—</span>
                                @endif
                            </td>

                            <td>
                                @if ($okr->expected_score !== null)
                                    <span class="okr-chip">{{ number_format((float) $okr->expected_score, 1) }}</span>
                                @else
                                    <span class="okr-redup">—</span>
                                @endif
                            </td>

                            <td>
                                @foreach ($okr->status_bulanan as $bulan)
                                    <div class="okr-bulan-blok">
                                        <div class="okr-bulan">Bulan {{ $bulan['nomor'] }} · {{ $bulan['nama'] }}</div>
                                        @if ($bulan['isi'])
                                            <div>{!! nl2br(e($bulan['isi'])) !!}</div>
                                        @else
                                            <div class="okr-redup">…</div>
                                        @endif
                                    </div>
                                @endforeach
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="okr-nihil">
                                Belum ada OKR di Q{{ $this->quarter }} {{ $this->year }}.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</x-filament-panels::page>
