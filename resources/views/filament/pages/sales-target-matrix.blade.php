{{--
    Sales Target Matrix — bulan sebagai kolom, seperti sheet "2026 Sales Target".

    CSS-nya manual dengan prefix .stm- dan CSS variable Filament, BUKAN kelas
    Tailwind baru: kelas Tailwind yang belum pernah dipakai di project ini tidak
    ikut ter-compile sampai `npm run build` dijalankan. Lihat memori
    project_filament_theme_build_gotcha.
--}}
@php
    $columns = $this->columns();
    $rows = $this->rows();
    $canEdit = $this->canEdit();

    $angka = fn($n) => number_format((float) $n, 0, ',', '.');
    $persen = fn($n) => number_format((float) $n, 2, ',', '.') . '%';
@endphp

<x-filament-panels::page>
    @push('styles')
        <style>
            .stm-toolbar{display:flex;flex-wrap:wrap;align-items:flex-end;gap:.75rem;margin-bottom:.25rem}
            .stm-field{display:flex;flex-direction:column;gap:.25rem}
            .stm-label{font-size:.72rem;text-transform:uppercase;letter-spacing:.04em;opacity:.6;font-weight:600}
            .stm-select{padding:.4rem .6rem;border-radius:.5rem;border:1px solid rgba(128,128,128,.35);
                background:transparent;color:inherit;font-size:.85rem}
            .stm-scroll{overflow-x:auto;border:1px solid rgba(128,128,128,.18);border-radius:.75rem;
                background:var(--gray-50,#f9fafb)}
            .dark .stm-scroll{background:rgba(255,255,255,.02)}
            .stm-table{border-collapse:separate;border-spacing:0;font-size:.8rem;white-space:nowrap;min-width:100%}
            .stm-table th,.stm-table td{padding:.45rem .6rem;border-bottom:1px solid rgba(128,128,128,.15)}
            .stm-table thead th{font-size:.7rem;text-transform:uppercase;letter-spacing:.03em;font-weight:700;
                text-align:right;background:var(--primary-600,#48009f);color:#fff;position:sticky;top:0;z-index:2}
            .stm-head-row{text-align:left !important}
            .stm-quarter{background:rgba(245,158,11,.16) !important}
            .stm-total{background:rgba(16,185,129,.16) !important}
            .stm-table thead th.stm-quarter,.stm-table thead th.stm-total{background:#5b1bb5 !important}
            .stm-row-label{position:sticky;left:0;z-index:1;text-align:left;font-weight:600;
                background:var(--gray-50,#f9fafb);min-width:220px}
            .dark .stm-row-label{background:#1f2027}
            .stm-num{text-align:right;font-variant-numeric:tabular-nums}
            .stm-sales td{background:rgba(59,130,246,.06)}
            .dark .stm-sales td{background:rgba(59,130,246,.08)}
            .stm-sales .stm-row-label{padding-left:1.4rem;font-weight:500}
            .stm-input{width:100%;min-width:104px;padding:.25rem .4rem;border-radius:.35rem;
                border:1px solid rgba(128,128,128,.3);background:transparent;color:inherit;
                text-align:right;font-size:.78rem;font-variant-numeric:tabular-nums}
            .stm-input:focus{outline:2px solid var(--primary-500,#7c3aed);outline-offset:-1px}
            .stm-good{color:rgb(5,150,105);font-weight:600}
            .stm-bad{color:rgb(220,38,38);font-weight:600}
            .stm-muted{opacity:.45}
            .stm-note{font-size:.78rem;opacity:.65;margin-top:.5rem;line-height:1.5}
            .stm-divider td{border-top:2px solid rgba(128,128,128,.3)}
        </style>
    @endpush

    <div class="stm-toolbar">
        <div class="stm-field">
            <span class="stm-label">Tahun</span>
            <select class="stm-select" wire:model.live="year">
                @foreach ($this->yearOptions() as $value => $label)
                    <option value="{{ $value }}">{{ $label }}</option>
                @endforeach
            </select>
        </div>

        @if ($canEdit)
            <x-filament::button wire:click="save" wire:loading.attr="disabled" icon="heroicon-m-check">
                Simpan Target Sales
            </x-filament::button>
        @endif
    </div>

    <div class="stm-scroll">
        <table class="stm-table">
            <thead>
                <tr>
                    <th class="stm-row-label stm-head-row">Booked</th>
                    @foreach ($columns as $column)
                        <th class="{{ $column['kind'] === 'quarter' ? 'stm-quarter' : ($column['kind'] === 'year' ? 'stm-total' : '') }}">
                            {{ $column['label'] }}
                        </th>
                    @endforeach
                </tr>
            </thead>
            <tbody>
                @foreach ($rows as $row)
                    <tr
                        wire:key="stm-row-{{ $loop->index }}"
                        class="{{ $row['kind'] === 'input' ? 'stm-sales' : '' }} {{ $row['label'] === 'Total Target Sales' ? 'stm-divider' : '' }}"
                    >
                        <td class="stm-row-label">{{ $row['label'] }}</td>

                        @foreach ($columns as $column)
                            @php
                                $key = $column['key'];
                                $value = $row['values'][$key] ?? 0;
                                $compare = $row['compare'][$key] ?? null;
                                $cellClass = $column['kind'] === 'quarter'
                                    ? 'stm-quarter'
                                    : ($column['kind'] === 'year' ? 'stm-total' : '');

                                // Nilai pembanding: hijau kalau sudah pas/melewati, merah kalau di bawah.
                                $verdict = null;
                                if ($compare !== null && ($value > 0 || $compare > 0)) {
                                    $verdict = $value >= $compare ? 'stm-good' : 'stm-bad';
                                }
                            @endphp

                            <td class="stm-num {{ $cellClass }} {{ $verdict }}">
                                @if ($row['kind'] === 'input' && $column['kind'] === 'month')
                                    @if ($canEdit)
                                        <input
                                            type="text"
                                            inputmode="numeric"
                                            class="stm-input"
                                            wire:model="cells.{{ $row['sales_id'] }}.{{ $column['month'] }}"
                                            placeholder="0"
                                        />
                                    @else
                                        <span class="{{ $value > 0 ? '' : 'stm-muted' }}">{{ $angka($value) }}</span>
                                    @endif
                                @elseif ($row['kind'] === 'percent')
                                    <span class="{{ $value > 0 ? '' : 'stm-muted' }}">{{ $persen($value) }}</span>
                                @else
                                    <span class="{{ $value > 0 ? '' : 'stm-muted' }}">{{ $angka($value) }}</span>
                                @endif
                            </td>
                        @endforeach
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    <p class="stm-note">
        Yang bisa diedit di sini hanya baris target tiap sales; kolom Q1–Q4 dan kolom tahun ikut
        terhitung setelah disimpan.
        <strong>Booked Revenue</strong> dan <strong>Booked GP Target</strong> diambil dari menu
        Finance &rarr; Target Finance (GP = revenue &times; benchmark margin).
        <strong>Actual</strong> dihitung dari deal berstatus Campaign Live s/d Paid berdasarkan close date.
        Baris <strong>Total Target Sales</strong> berwarna merah kalau distribusi target ke sales
        belum menyamai Booked Revenue.
        <strong>% of Sales Target Achievement</strong> memakai Total Target Sales sebagai pembagi —
        Booked Revenue dari Finance hanya dipakai untuk bulan yang target salesnya masih kosong.
    </p>
</x-filament-panels::page>
