{{--
    Campaign Summary — angka gabungan seluruh postingan KOL satu campaign.

    CSS ditulis manual dengan prefix .cs- : kelas Tailwind yang belum pernah
    dipakai di project ini tidak ikut ter-compile sampai `npm run build`, dan
    halamannya akan tampil tanpa gaya. Lihat memori project_filament_theme_build_gotcha.
--}}
@php
    $s = $this->summary;
    $campaign = $this->campaign;
    $sentiment = $s?->sentiment() ?? ['total' => 0, 'counts' => [], 'percentages' => [], 'score' => 0];
    $buckets = config('sentiment.buckets');

    $angka = fn($n) => number_format((int) $n, 0, ',', '.');
    $selisih = function ($sekarang, $sebelum) use ($angka) {
        $delta = (int) $sekarang - (int) $sebelum;
        if ($delta === 0) return null;
        return ['naik' => $delta > 0, 'teks' => ($delta > 0 ? '↑ ' : '↓ ') . $angka(abs($delta))];
    };
@endphp

<x-filament-panels::page>
    @push('styles')
        <style>
            .cs-card{background:rgba(128,128,128,.06);border:1px solid rgba(128,128,128,.18);
                border-radius:.75rem;padding:.9rem 1rem}
            .cs-grid{display:grid;gap:.75rem;grid-template-columns:repeat(2,minmax(0,1fr))}
            .cs-label{font-size:.68rem;text-transform:uppercase;letter-spacing:.04em;opacity:.6;font-weight:700}
            .cs-value{font-size:1.35rem;font-weight:700;line-height:1.25;margin-top:.15rem}
            .cs-hint{font-size:.68rem;opacity:.5;margin-top:.1rem}
            .cs-muted{opacity:.6;font-size:.8rem}
            .cs-section{margin-top:1.5rem}
            .cs-h{font-size:1rem;font-weight:700;margin-bottom:.75rem}
            .cs-badge{display:inline-block;padding:.25rem .7rem;border-radius:9999px;
                font-size:.72rem;font-weight:700;color:#0b3b1e}
            .cs-chip{display:inline-block;padding:.15rem .55rem;border-radius:9999px;
                font-size:.72rem;background:rgba(128,128,128,.15);margin:0 .25rem .25rem 0}
            .cs-score{font-size:2rem;font-weight:800;line-height:1}
            .cs-bar-row{display:flex;align-items:center;gap:.6rem;margin-bottom:.4rem;font-size:.8rem}
            .cs-bar-name{flex:0 0 30%;overflow:hidden;text-overflow:ellipsis;white-space:nowrap}
            .cs-bar-track{flex:1;height:.5rem;border-radius:9999px;background:rgba(128,128,128,.18)}
            .cs-bar-fill{height:100%;border-radius:9999px;background:var(--primary-500,#7c3aed)}
            .cs-empty{padding:1.1rem;text-align:center;font-size:.8rem;opacity:.6;
                border:1px dashed rgba(128,128,128,.3);border-radius:.6rem}
            /* Kisi penuh seperti spreadsheet: 14 kolom angka tanpa garis tegak
               membuat mata gampang loncat baris saat menggeser ke kanan. */
            .cs-table{--cs-garis:rgba(128,128,128,.3);--cs-kepala:rgba(128,128,128,.12);
                --cs-belang:rgba(128,128,128,.06);--cs-latar:#fff;
                width:100%;border-collapse:collapse;font-size:.82rem;white-space:nowrap}
            .dark .cs-table{--cs-latar:#18181b}
            .cs-table th,.cs-table td{border:1px solid var(--cs-garis);padding:.45rem .6rem}
            .cs-table th{text-align:left;font-size:.68rem;text-transform:uppercase;
                font-weight:700;opacity:.75;background:var(--cs-kepala)}
            .cs-table tbody tr:nth-child(even) td{background:var(--cs-belang)}
            /* Kolom tanggal tetap menempel saat digeser — tanpa penanda baris,
               deretan angkanya tidak bisa dibaca lagi. */
            .cs-table th:first-child,.cs-table td:first-child{position:sticky;left:0;
                background:var(--cs-latar);box-shadow:1px 0 0 var(--cs-garis)}
            .cs-table th:first-child{background:var(--cs-kepala)}
            .cs-num{text-align:right;font-variant-numeric:tabular-nums}
            .cs-up{color:#16a34a;font-size:.68rem;display:block}
            .cs-down{color:#dc2626;font-size:.68rem;display:block}
            .cs-scroll{overflow-x:auto;border-radius:.5rem}
            /* Keterangan satu kalimat — jangan pakai cs-label yang huruf besar semua. */
            .cs-note{font-size:.78rem;opacity:.6;line-height:1.5}
            .cs-pager{display:flex;align-items:center;gap:.6rem;flex-wrap:wrap;
                margin-top:1rem;padding-top:.9rem;border-top:1px solid rgba(128,128,128,.18);
                font-size:.78rem}
            .cs-pager-info{opacity:.6}
            .cs-pager-sisa{margin-left:auto;display:flex;align-items:center;gap:.4rem}
            .cs-pager button{padding:.3rem .7rem;border-radius:.45rem;font-weight:600;
                border:1px solid rgba(128,128,128,.3);background:transparent}
            .cs-pager button:disabled{opacity:.35;cursor:not-allowed}
            .cs-pager select{padding:.25rem 1.5rem .25rem .5rem;border-radius:.45rem;
                border:1px solid rgba(128,128,128,.3);background:transparent;font-size:.78rem}
            /* Satu blok per postingan — Retrieve History menampilkan semuanya
               sekaligus, jadi tiap blok butuh pemisah yang jelas. */
            .cs-riwayat{margin-top:1rem;padding-top:.9rem;border-top:1px solid rgba(128,128,128,.18)}
            .cs-riwayat:first-of-type{margin-top:0;padding-top:0;border-top:0}
            .cs-riwayat-judul{display:flex;align-items:center;gap:.5rem;flex-wrap:wrap;
                font-weight:700;font-size:.85rem;margin-bottom:.5rem}
            .cs-riwayat-platform{padding:.1rem .5rem;border-radius:9999px;font-size:.68rem;
                font-weight:700;background:rgba(128,128,128,.15)}
            .cs-riwayat-link{font-size:.72rem;font-weight:600;opacity:.75;text-decoration:underline}
            .cs-riwayat-jumlah{margin-left:auto;font-size:.68rem;font-weight:600;opacity:.5}
            .cs-split{display:grid;gap:1.25rem}
            @media(min-width:640px){.cs-grid{grid-template-columns:repeat(3,minmax(0,1fr))}}
            @media(min-width:1024px){.cs-grid{grid-template-columns:repeat(6,minmax(0,1fr))}
                .cs-split{grid-template-columns:auto auto 1fr;align-items:start}}
        </style>
    @endpush

    @if (! $campaign)
        <p class="cs-muted">
            Campaign internal yang sudah jalan. Klik satu baris untuk melihat ringkasan performa
            dan sentimen seluruh postingan KOL-nya.
        </p>

        {{ $this->table }}
    @else
    {{-- 1. Kartu metrik gabungan seluruh postingan KOL --}}
    <div class="cs-grid">
        @foreach ($s->cards() as $card)
            <div class="cs-card">
                <div class="cs-label">{{ $card['label'] }}</div>
                <div class="cs-value">{{ $card['value'] }}</div>
                @if ($card['hint'])
                    <div class="cs-hint">{{ $card['hint'] }}</div>
                @endif
            </div>
        @endforeach
    </div>

    {{-- Ringkasan AI. Hanya muncul setelah tombol "Ringkasan AI" diklik —
         tiap panggilan berbayar, jadi tidak pernah jalan otomatis. --}}
    @if ($campaign->ai_summary)
        <div class="cs-card cs-section">
            <div class="cs-h">Ringkasan AI</div>
            <p style="white-space:pre-line;font-size:.9rem;margin-top:.4rem">{{ $campaign->ai_summary }}</p>
            <div class="cs-hint" style="margin-top:.5rem">
                Ditulis {{ $campaign->ai_summary_at?->diffForHumans() }} oleh
                {{ config('ai.providers.' . config('ai.default') . '.models.text.default', config('ai.default')) }}.
                Klik "Ringkasan AI" di header untuk menulis ulang setelah performa di-refresh.
            </div>
        </div>
    @endif

    @include('filament.partials.fetch-performa-bertahap')

    {{-- 3. Campaign Performance --}}
    <div class="cs-card cs-section">
        <div class="cs-h">Campaign Performance</div>
        <div class="cs-split">
            <div style="min-width:8rem">
                <div class="cs-label">Success Metrics</div>
                @php $sukses = $s->successScore(); @endphp
                <div class="cs-score" style="margin-top:.3rem">
                    {{ $sukses['score'] }}<span style="font-size:1rem;opacity:.5">/{{ $sukses['max'] }}</span>
                </div>
                <div class="cs-hint">metrik yang lolos benchmark</div>
            </div>

            <div style="min-width:11rem">
                <div class="cs-label">Top 3 Creator</div>
                @forelse ($s->topCreators() as $creator)
                    <div style="font-size:.85rem;margin-top:.3rem">
                        &#64;{{ $creator->username ?: $creator->creator_name }}
                        <span class="cs-muted">· {{ $angka($creator->total_engagement) }}</span>
                    </div>
                @empty
                    <div class="cs-muted" style="margin-top:.3rem">Belum ada data.</div>
                @endforelse
            </div>

            <div>
                <div class="cs-label">Metrics Overview</div>
                <div style="display:flex;flex-wrap:wrap;gap:.4rem;margin-top:.4rem">
                    @foreach ($s->metricsOverview() as $m)
                        @php
                            $warna = match ($m['verdict']) {
                                'excellent' => '#86efac',
                                'good' => '#fde68a',
                                'bad' => '#fca5a5',
                                default => 'rgba(128,128,128,.25)',
                            };
                        @endphp
                        <span class="cs-badge" style="background:{{ $warna }}"
                              title="{{ ucfirst($m['verdict']) }}">
                            {{ $m['label'] }} {{ $m['value'] }}
                        </span>
                    @endforeach
                </div>
                <div class="cs-hint" style="margin-top:.5rem">
                    <span class="cs-chip" style="background:#86efac;color:#0b3b1e">Excellent</span>
                    <span class="cs-chip" style="background:#fde68a;color:#0b3b1e">Good</span>
                    <span class="cs-chip" style="background:#fca5a5;color:#0b3b1e">Bad</span>
                    Ambangnya di <code>config/kol.php</code>.
                </div>
            </div>
        </div>
    </div>

    {{-- 2. Campaign Sentiments Summary --}}
    <div class="cs-card cs-section">
        <div class="cs-h">Campaign Sentiments Summary</div>

        @if ($sentiment['total'] === 0)
            <div class="cs-empty">
                Komentar belum pernah diambil. Klik <strong>Analisis Sentimen</strong> di header —
                tiap postingan memakai kredit API, jadi tidak berjalan otomatis.
            </div>
        @else
            <div class="cs-split">
                <div style="min-width:8rem">
                    <div class="cs-label">Sentiments Score</div>
                    <div class="cs-score" style="margin-top:.3rem">
                        {{ $sentiment['score'] }}<span style="font-size:1rem;opacity:.5">/5</span>
                    </div>
                    <div class="cs-hint">{{ $angka($sentiment['total']) }} komentar</div>
                </div>

                <div style="min-width:11rem;max-width:18rem">
                    <div class="cs-label">Top 10 Buzz Word</div>
                    <div style="margin-top:.4rem">
                        @forelse ($s->buzzWords() as $kata => $jumlah)
                            <span class="cs-chip">{{ $kata }} <strong>{{ $jumlah }}</strong></span>
                        @empty
                            <span class="cs-muted">Tidak ada kata yang menonjol.</span>
                        @endforelse
                    </div>
                </div>

                <div>
                    <div class="cs-label">Sebaran Sentimen</div>
                    <div style="margin-top:.4rem">
                        @foreach ($buckets as $kunci => $bucket)
                            @php $persen = $sentiment['percentages'][$kunci] ?? 0; @endphp
                            <div class="cs-bar-row">
                                <span class="cs-bar-name">{{ $bucket['label'] }}</span>
                                <span class="cs-bar-track">
                                    <span class="cs-bar-fill"
                                          style="width:{{ min(100, $persen) }}%;background:{{ $bucket['color'] }}"></span>
                                </span>
                                <span class="cs-num" style="flex:0 0 6.5rem">
                                    {{ $angka($sentiment['counts'][$kunci] ?? 0) }} ({{ number_format($persen, 2) }}%)
                                </span>
                            </div>
                        @endforeach
                    </div>
                    <div class="cs-hint" style="margin-top:.4rem">
                        Klasifikasi leksikon bahasa Indonesia (<code>config/sentiment.php</code>), bukan NLP —
                        sarkasme tidak tertangkap.
                        @if ($s->commentsFetchedAt())
                            Terakhir diambil {{ $s->commentsFetchedAt()->diffForHumans() }}.
                        @endif
                    </div>
                </div>
            </div>
        @endif
    </div>

    {{-- 5. Content List --}}
    <div class="cs-section">
        <div class="cs-h">Content List</div>
        {{ $this->table }}
    </div>

    {{-- 6. Retrieve History --}}
    <div class="cs-card cs-section">
        <div class="cs-h">Retrieve History</div>

        @if ($s->published()->isEmpty())
            <div class="cs-empty">Belum ada postingan yang tayang.</div>
        @else
            <p class="cs-note mb-4">
                Riwayat seluruh {{ $s->published()->count() }} postingan. Satu baris tercatat setiap kali
                performanya di-fetch — tidak ada histori harian dari sumber data, jadi datanya terkumpul dari sekarang.
            </p>

            {{-- Semua postingan dirender sekaligus, bukan lewat dropdown: satu KOL
                 bisa punya beberapa postingan di platform berbeda, dan dropdown
                 menyembunyikan yang lain sehingga terlihat seolah cuma satu yang
                 punya riwayat. Snapshot-nya di-eager-load di CampaignSummary. --}}
            @foreach ($this->historyPageItems as $kol)
                @php $rows = $kol->snapshots; @endphp

                <div class="cs-riwayat">
                    <div class="cs-riwayat-judul">
                        <span>{{ $kol->creator_name }}</span>
                        <span class="cs-riwayat-platform">
                            {{ \App\Models\BvCampaignKol::PLATFORMS[$kol->platform] ?? $kol->platform }}
                        </span>
                        @if ($kol->post_url)
                            <a href="{{ $kol->post_url }}" target="_blank" rel="noopener" class="cs-riwayat-link">Buka ↗</a>
                        @endif
                        <span class="cs-riwayat-jumlah">{{ $rows->count() }} baris</span>
                    </div>

                    @if ($rows->isEmpty())
                        <div class="cs-empty">Belum pernah di-fetch.</div>
                    @else
                        <div class="cs-scroll">
                            <table class="cs-table">
                                <thead>
                                    <tr>
                                        <th>Date</th><th class="cs-num">Followers</th><th class="cs-num">Cost</th>
                                        <th class="cs-num">Views</th><th class="cs-num">Engagement</th><th class="cs-num">Like</th>
                                        <th class="cs-num">Comment</th><th class="cs-num">Shared</th><th class="cs-num">Saved</th>
                                        <th class="cs-num">CPE</th><th class="cs-num">CPV</th>
                                        <th class="cs-num">ER</th><th class="cs-num">VTR</th><th class="cs-num">CPM</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($rows as $i => $row)
                                        @php $prev = $i > 0 ? $rows[$i - 1] : null; @endphp
                                        <tr>
                                            <td><strong>{{ $row->captured_on->translatedFormat('d F Y') }}</strong></td>
                                            <td class="cs-num">{{ $angka($row->followers) }}</td>
                                            <td class="cs-num">{{ $angka($row->cost) }}</td>
                                            @foreach (['views', 'engagement', 'likes', 'comments', 'shares', 'saves'] as $kolom)
                                                <td class="cs-num">
                                                    {{ $angka($row->{$kolom}) }}
                                                    @if ($prev && ($d = $selisih($row->{$kolom}, $prev->{$kolom})))
                                                        <span class="{{ $d['naik'] ? 'cs-up' : 'cs-down' }}">{{ $d['teks'] }}</span>
                                                    @endif
                                                </td>
                                            @endforeach
                                            <td class="cs-num">{{ $angka($row->cpe()) }}</td>
                                            <td class="cs-num">{{ $angka($row->cpv()) }}</td>
                                            <td class="cs-num">{{ number_format($row->engagementRate(), 2) }}%</td>
                                            <td class="cs-num">{{ number_format($row->vtr(), 2) }}%</td>
                                            <td class="cs-num">{{ $angka($row->cpm()) }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif
                </div>
            @endforeach

            @php
                $total = $s->published()->count();
                $dari = ($this->historyPage - 1) * $this->historyPerPage + 1;
                $sampai = min($this->historyPage * $this->historyPerPage, $total);
            @endphp

            <div class="cs-pager">
                <button type="button" wire:click="$set('historyPage', {{ max(1, $this->historyPage - 1) }})"
                        @disabled($this->historyPage <= 1)>← Sebelumnya</button>

                <span class="cs-pager-info">
                    Postingan {{ $dari }}–{{ $sampai }} dari {{ $total }}
                    (halaman {{ $this->historyPage }} dari {{ $this->historyPages }})
                </span>

                <button type="button" wire:click="$set('historyPage', {{ min($this->historyPages, $this->historyPage + 1) }})"
                        @disabled($this->historyPage >= $this->historyPages)>Berikutnya →</button>

                <span class="cs-pager-sisa">
                    <label for="cs-history-per">Per halaman</label>
                    <select id="cs-history-per" wire:model.live="historyPerPage">
                        @foreach ([5, 10, 25, 50] as $n)
                            <option value="{{ $n }}" @selected($this->historyPerPage === $n)>{{ $n }}</option>
                        @endforeach
                    </select>
                </span>
            </div>
        @endif
    </div>
    @endif
</x-filament-panels::page>
