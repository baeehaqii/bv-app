{{--
    KOL Analyzer.

    CSS-nya ditulis manual dengan prefix .kolz- dan CSS variable Filament, BUKAN
    kelas Tailwind baru: kelas Tailwind yang belum pernah dipakai di project ini
    tidak ikut ter-compile sampai `npm run build` dijalankan, dan halaman akan
    tampil tanpa gaya. Lihat memori project_filament_theme_build_gotcha.
--}}
@php
    $channel = $this->channel;
    $latest = $this->latestStats;
    $growth = $this->growth;
    $hashtags = $this->topHashtags;
    $audience = $channel?->audience_countries ?? [];

    $angka = fn($n) => number_format((int) $n, 0, ',', '.');
@endphp

<x-filament-panels::page>
    @push('styles')
        <style>
            .kolz-card{background:var(--gray-50,#f9fafb);border:1px solid rgba(128,128,128,.18);border-radius:.75rem;padding:1rem}
            .dark .kolz-card{background:rgba(255,255,255,.03)}
            .kolz-grid{display:grid;gap:1rem}
            .kolz-label{font-size:.72rem;text-transform:uppercase;letter-spacing:.04em;opacity:.6;font-weight:600}
            .kolz-value{font-size:1.4rem;font-weight:700;line-height:1.2}
            .kolz-muted{opacity:.6;font-size:.8rem}
            .kolz-avatar{width:88px;height:88px;border-radius:9999px;object-fit:cover;flex:0 0 auto;
                background:rgba(128,128,128,.15)}
            .kolz-bar-row{display:flex;align-items:center;gap:.6rem;margin-bottom:.4rem;font-size:.82rem}
            .kolz-bar-name{flex:0 0 34%;overflow:hidden;text-overflow:ellipsis;white-space:nowrap}
            .kolz-bar-track{flex:1;height:.55rem;border-radius:9999px;background:rgba(128,128,128,.18)}
            .kolz-bar-fill{height:100%;border-radius:9999px;background:var(--primary-500,#7c3aed)}
            .kolz-bar-num{flex:0 0 auto;font-variant-numeric:tabular-nums;opacity:.75}
            .kolz-empty{padding:1.25rem;text-align:center;font-size:.82rem;opacity:.6;
                border:1px dashed rgba(128,128,128,.3);border-radius:.6rem}
            .kolz-table{width:100%;border-collapse:collapse;font-size:.85rem}
            .kolz-table th{text-align:left;font-size:.72rem;text-transform:uppercase;opacity:.6;padding:.5rem .6rem}
            .kolz-table td{padding:.55rem .6rem;border-top:1px solid rgba(128,128,128,.15)}
            .kolz-chip{display:inline-block;padding:.1rem .5rem;border-radius:9999px;font-size:.72rem;
                background:rgba(128,128,128,.15)}
            .kolz-post{border:1px solid rgba(128,128,128,.18);border-radius:.6rem;overflow:hidden;
                display:flex;flex-direction:column}
            .kolz-post img{width:100%;aspect-ratio:1;object-fit:cover;background:rgba(128,128,128,.15)}
            .kolz-post-body{padding:.55rem;font-size:.75rem;display:flex;flex-direction:column;gap:.3rem}
            .kolz-spark{display:flex;align-items:flex-end;gap:.35rem;height:120px}
            .kolz-spark-col{flex:1;display:flex;flex-direction:column;justify-content:flex-end;
                align-items:center;gap:.3rem;height:100%}
            .kolz-spark-bar{width:100%;border-radius:.25rem .25rem 0 0;background:var(--primary-500,#7c3aed);min-height:3px}
            @media(min-width:768px){.kolz-cols-2{grid-template-columns:repeat(2,minmax(0,1fr))}
                .kolz-cols-4{grid-template-columns:repeat(4,minmax(0,1fr))}
                .kolz-cols-5{grid-template-columns:repeat(5,minmax(0,1fr))}}
        </style>
    @endpush

    @if (! $channel)
        {{-- Daftar KOL, sama seperti KOL Data. Klik baris untuk membuka analisisnya. --}}
        <p class="kolz-muted">
            Semua KOL di KOL Data otomatis muncul di sini. Klik satu baris untuk melihat analisis channel-nya.
        </p>

        {{ $this->table }}
    @else
        {{-- 1. Card profil --}}
        <div class="kolz-card" style="display:flex;gap:1.25rem;flex-wrap:wrap;align-items:flex-start">
            @php $avatar = \App\Support\KolImageProxy::url($channel->profile_pic_url); @endphp
            @if ($avatar)
                <img src="{{ $avatar }}" alt="{{ $channel->username }}" class="kolz-avatar"
                     onerror="this.remove()">
            @else
                <div class="kolz-avatar"></div>
            @endif

            <div style="flex:1;min-width:16rem">
                <div style="display:flex;align-items:center;gap:.5rem;flex-wrap:wrap">
                    <span style="font-size:1.15rem;font-weight:700">&#64;{{ $channel->username }}</span>
                    @if ($channel->is_verified)
                        <span class="kolz-chip">&#10003; Verified</span>
                    @endif
                    <span class="kolz-chip">{{ $channel->channel }}</span>
                    <span class="kolz-chip">{{ \App\Models\DataKol::tierFor((int) $channel->followers) }}</span>
                </div>

                @if ($channel->full_name)
                    <div class="kolz-muted" style="margin-top:.15rem">{{ $channel->full_name }}</div>
                @endif

                @if ($channel->biography)
                    <p style="margin-top:.5rem;font-size:.85rem;white-space:pre-line">{{ $channel->biography }}</p>
                @else
                    <p class="kolz-muted" style="margin-top:.5rem">Bio belum tersimpan — refresh channel ini di KOL Data.</p>
                @endif

                <div class="kolz-grid kolz-cols-4" style="margin-top:.9rem">
                    <div><div class="kolz-label">Followers</div><div class="kolz-value">{{ $angka($channel->followers) }}</div></div>
                    <div><div class="kolz-label">Following</div><div class="kolz-value">{{ $angka($channel->following_count) }}</div></div>
                    <div><div class="kolz-label">Engagement Rate</div><div class="kolz-value">{{ number_format((float) $channel->engagement_rate, 2) }}%</div></div>
                    <div>
                        <div class="kolz-label">{{ $channel->channel === 'Youtube Channels' ? 'Videos' : 'Posts' }}</div>
                        <div class="kolz-value">{{ $angka($channel->media_count) }}</div>
                    </div>
                </div>
            </div>

        </div>

        {{-- 2. Angka gabungan seluruh channel + tabel channel-nya --}}
        <div class="kolz-card">
            @php $gab = $this->gabungan; @endphp
            <div class="kolz-label" style="margin-bottom:.5rem">Social Data — Semua Channel &#64;{{ $channel->username }}</div>

            <div class="kolz-grid kolz-cols-4" style="margin-bottom:1rem">
                <div>
                    <div class="kolz-label">Total Followers</div>
                    <div class="kolz-value">{{ $angka($gab['followers']) }}</div>
                    <div class="kolz-muted" style="font-size:.75rem">
                        {{ $gab['channels'] }} channel · tier {{ $gab['tier'] }}
                    </div>
                </div>
                <div>
                    <div class="kolz-label">Total Engagements</div>
                    <div class="kolz-value">{{ $angka($gab['engagements']) }}</div>
                    <div class="kolz-muted" style="font-size:.75rem">seluruh channel dijumlahkan</div>
                </div>
                <div>
                    <div class="kolz-label">ER Gabungan</div>
                    <div class="kolz-value">{{ number_format($gab['engagement_rate'], 2) }}%</div>
                    <div class="kolz-muted" style="font-size:.75rem">rata-rata ER antar channel</div>
                </div>
                <div>
                    <div class="kolz-label">Avg Views Gabungan</div>
                    <div class="kolz-value">{{ $angka($gab['average_views']) }}</div>
                    <div class="kolz-muted" style="font-size:.75rem">rata-rata antar channel</div>
                </div>
            </div>

            <div style="overflow-x:auto">
                <table class="kolz-table">
                    <thead>
                        <tr>
                            <th>Channel</th><th>Username</th><th style="text-align:right">Followers</th>
                            <th style="text-align:right">ER</th><th style="text-align:right">Engagements</th>
                            <th style="text-align:right">Avg Views</th><th>Last Update</th><th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($this->siblings as $row)
                            <tr @if ($row->id === $channel->id) style="background:rgba(124,58,237,.08)" @endif>
                                <td><span class="kolz-chip">{{ $row->channel }}</span></td>
                                <td>&#64;{{ $row->username }}</td>
                                <td style="text-align:right">{{ $angka($row->followers) }}</td>
                                <td style="text-align:right">{{ number_format((float) $row->engagement_rate, 2) }}%</td>
                                <td style="text-align:right">{{ $angka($row->engagements) }}</td>
                                <td style="text-align:right">{{ $angka($row->average_views) }}</td>
                                <td>{{ $row->terakhir_update?->format('d M Y') ?? '—' }}</td>
                                <td style="text-align:right">
                                    @if ($row->id === $channel->id)
                                        <span class="kolz-muted">sedang dianalisis</span>
                                    @else
                                        <button type="button" wire:click="$set('channelId', {{ $row->id }})"
                                                class="fi-link" style="color:var(--primary-500,#7c3aed)">Analisis</button>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>

        {{-- Kartu AI. Muncul hanya setelah tombol "Buat Kartu AI" diklik —
             tiap panggilan berbayar, jadi tidak pernah jalan otomatis. --}}
        @if ($channel->ai_insight)
            <div class="kolz-card">
                <div class="kolz-label" style="margin-bottom:.4rem">Kartu AI</div>
                <p style="white-space:pre-line;font-size:.9rem;line-height:1.55">{{ $channel->ai_insight }}</p>
                <div class="kolz-muted" style="font-size:.75rem;margin-top:.5rem">
                    Ditulis {{ $channel->ai_insight_at?->diffForHumans() }}. Unduh versi PDF-nya lewat
                    tombol "Download Kartu (PDF)" di header.
                </div>
            </div>
        @endif

        {{-- 3. Tab --}}
        <div class="kolz-card">
            <div style="display:flex;gap:.5rem;margin-bottom:1rem;flex-wrap:wrap">
                @foreach (['overview' => 'Overview', 'latest' => '10 Postingan Terakhir'] as $key => $label)
                    <button type="button" wire:click="$set('tab', '{{ $key }}')"
                            style="padding:.4rem .9rem;border-radius:9999px;font-size:.85rem;font-weight:600;border:1px solid rgba(128,128,128,.25);
                                   {{ $this->tab === $key ? 'background:var(--primary-500,#7c3aed);color:#fff;border-color:transparent' : '' }}">
                        {{ $label }}
                    </button>
                @endforeach
            </div>

            @if ($this->tab === 'overview')
                {{-- 4a. User performa (seluruh postingan saat scraping) --}}
                <div class="kolz-label">User Performa — angka tersimpan saat scraping terakhir</div>
                <p class="kolz-muted" style="margin-top:.2rem">
                    Basis 9 postingan, mengikuti aturan ER yang dipakai di seluruh sistem.
                    Tab sebelah menghitung ulang dari 10 postingan terakhir apa adanya.
                </p>
                <div class="kolz-grid kolz-cols-4" style="margin-top:.5rem">
                    <div class="kolz-card"><div class="kolz-label">Avg Likes</div><div class="kolz-value">{{ $angka($channel->average_likes) }}</div></div>
                    <div class="kolz-card"><div class="kolz-label">Avg Comments</div><div class="kolz-value">{{ $angka($channel->average_comments) }}</div></div>
                    <div class="kolz-card"><div class="kolz-label">Avg Views</div><div class="kolz-value">{{ $angka($channel->average_views) }}</div></div>
                    <div class="kolz-card">
                        <div class="kolz-label">VTR</div>
                        <div class="kolz-value">{{ $channel->viewThroughRate() !== null ? number_format($channel->viewThroughRate(), 2) . '%' : '—' }}</div>
                        <div class="kolz-muted">avg views &divide; followers</div>
                    </div>
                </div>

                {{-- 4b. Follower growth --}}
                <div class="kolz-label" style="margin-top:1.25rem">Follower Growth</div>
                @if ($growth->count() >= 2)
                    @php $maxFollowers = max($growth->pluck('followers')->all()) ?: 1; @endphp
                    <div class="kolz-spark" style="margin-top:.6rem">
                        @foreach ($growth as $titik)
                            <div class="kolz-spark-col" title="{{ $titik->captured_on->format('d M Y') }}: {{ $angka($titik->followers) }}">
                                <div class="kolz-spark-bar" style="height:{{ max(3, round($titik->followers / $maxFollowers * 100)) }}%"></div>
                                <span class="kolz-muted" style="font-size:.65rem">{{ $titik->captured_on->format('d/m') }}</span>
                            </div>
                        @endforeach
                    </div>
                    @php $selisih = $growth->last()->followers - $growth->first()->followers; @endphp
                    <p class="kolz-muted" style="margin-top:.5rem">
                        {{ $selisih >= 0 ? '+' : '' }}{{ $angka(abs($selisih)) }} followers
                        sejak {{ $growth->first()->captured_on->format('d M Y') }}.
                    </p>
                @else
                    <div class="kolz-empty" style="margin-top:.6rem">
                        Butuh minimal 2 tanggal untuk menggambar grafik. Tidak ada sumber histori followers dari
                        ScrapeCreators, jadi datanya dikumpulkan sendiri setiap channel ini di-refresh.
                        Tercatat sejauh ini: {{ $growth->count() }} titik.
                    </div>
                @endif

                {{-- 4c. Audience breakdown --}}
                <div class="kolz-label" style="margin-top:1.25rem">Audience Breakdown</div>
                <div class="kolz-grid kolz-cols-2" style="margin-top:.6rem">
                    <div>
                        <div class="kolz-label" style="margin-bottom:.4rem">Top Country</div>
                        @forelse ($audience as $baris)
                            <div class="kolz-bar-row">
                                <span class="kolz-bar-name">{{ $baris['country'] }}</span>
                                <span class="kolz-bar-track"><span class="kolz-bar-fill" style="width:{{ min(100, $baris['percentage']) }}%"></span></span>
                                <span class="kolz-bar-num">{{ number_format($baris['percentage'], 1) }}%</span>
                            </div>
                        @empty
                            <div class="kolz-empty">
                                @if ($channel->channel === 'Tiktok')
                                    Belum diambil. Klik <strong>Ambil Data Audiens</strong> di atas
                                    ({{ \App\Service\TiktokService::AUDIENCE_CREDITS }} kredit).
                                @else
                                    Tidak tersedia untuk {{ $channel->channel }} — ScrapeCreators hanya menyediakan
                                    data audiens untuk TikTok.
                                @endif
                            </div>
                        @endforelse
                        @if ($channel->audience_fetched_at)
                            <p class="kolz-muted">Diambil {{ $channel->audience_fetched_at->diffForHumans() }}.</p>
                        @endif
                    </div>

                    <div>
                        <div class="kolz-label" style="margin-bottom:.4rem">Top City &middot; Top Age &middot; Gender</div>
                        <div class="kolz-empty">
                            Tidak tersedia dari sumber data. Endpoint audiens ScrapeCreators hanya mengembalikan
                            sebaran negara — kota, umur, dan gender tidak disediakan untuk channel mana pun.
                        </div>
                    </div>
                </div>

                {{-- 4d. Top hashtag --}}
                <div class="kolz-label" style="margin-top:1.25rem">Top Hashtag</div>
                @php $maxTag = $hashtags ? max($hashtags) : 1; @endphp
                <div style="margin-top:.6rem">
                    @forelse ($hashtags as $tag => $jumlah)
                        <div class="kolz-bar-row">
                            <span class="kolz-bar-name">#{{ $tag }}</span>
                            <span class="kolz-bar-track"><span class="kolz-bar-fill" style="width:{{ round($jumlah / $maxTag * 100) }}%"></span></span>
                            <span class="kolz-bar-num">{{ $jumlah }}&times;</span>
                        </div>
                    @empty
                        <div class="kolz-empty">
                            Belum ada hashtag terdeteksi dari caption postingan terakhir.
                        </div>
                    @endforelse
                </div>
            @else
                {{-- 5. Tab 10 postingan terakhir --}}
                <div class="kolz-label">
                    User Performa — {{ $latest['posts'] }} postingan terakhir
                    ({{ $latest['videos'] }} video, {{ $latest['photos'] }} foto)
                </div>
                <p class="kolz-muted" style="margin-top:.2rem">
                    Dihitung ulang dari postingan di bawah, bukan angka tersimpan.
                    @if ($latest['posts'] < \App\Service\KolPostNormalizer::LIMIT)
                        Channel ini baru punya {{ $latest['posts'] }} postingan tersimpan —
                        refresh di KOL Data untuk mengambil {{ \App\Service\KolPostNormalizer::LIMIT }} terbaru.
                    @endif
                </p>
                <div class="kolz-grid kolz-cols-4" style="margin-top:.5rem">
                    <div class="kolz-card"><div class="kolz-label">Avg Likes</div><div class="kolz-value">{{ $angka($latest['likes']) }}</div></div>
                    <div class="kolz-card"><div class="kolz-label">Avg Comments</div><div class="kolz-value">{{ $angka($latest['comments']) }}</div></div>
                    <div class="kolz-card"><div class="kolz-label">Avg Views</div><div class="kolz-value">{{ $angka($latest['views']) }}</div></div>
                    <div class="kolz-card">
                        <div class="kolz-label">VTR</div>
                        <div class="kolz-value">{{ $latest['vtr'] !== null ? number_format($latest['vtr'], 2) . '%' : '—' }}</div>
                    </div>
                </div>

                <div class="kolz-grid kolz-cols-5" style="margin-top:1rem">
                    @forelse ($this->posts as $post)
                        <div class="kolz-post">
                            @php $thumb = \App\Support\KolImageProxy::url($post['thumbnail']); @endphp
                            @if ($thumb)
                                {{-- onerror: link CDN bisa kedaluwarsa; kotak abu-abu lebih baik
                                     daripada ikon gambar rusak. --}}
                                <img src="{{ $thumb }}" alt="" loading="lazy"
                                     onerror="this.replaceWith(Object.assign(document.createElement('div'),
                                         {style:'width:100%;aspect-ratio:1;background:rgba(128,128,128,.15)'}))">
                            @else
                                <div style="width:100%;aspect-ratio:1;background:rgba(128,128,128,.15)"></div>
                            @endif
                            <div class="kolz-post-body">
                                <span class="kolz-chip">{{ $post['is_video'] ? 'Video' : 'Foto' }}</span>
                                <div>&#9829; {{ $angka($post['likes']) }} &middot; &#128172; {{ $angka($post['comments']) }}</div>
                                @if ($post['views'])
                                    <div class="kolz-muted">&#9654; {{ $angka($post['views']) }} views</div>
                                @endif
                                @if ($post['posted_at'])
                                    <div class="kolz-muted">{{ \Illuminate\Support\Carbon::parse($post['posted_at'])->format('d M Y') }}</div>
                                @endif
                                @if ($post['url'])
                                    <a href="{{ $post['url'] }}" target="_blank" rel="noopener noreferrer"
                                       style="color:var(--primary-500,#7c3aed)">Buka &#8599;</a>
                                @endif
                            </div>
                        </div>
                    @empty
                        <div class="kolz-empty" style="grid-column:1/-1">
                            Belum ada postingan tersimpan. Refresh channel ini di KOL Data untuk mengambil
                            10 postingan terakhir.
                        </div>
                    @endforelse
                </div>
            @endif
        </div>

        {{--
            WAJIB di cabang ini. Halaman ber-HasTable TIDAK dirender modalnya oleh
            <x-filament-panels::page> — Filament menyerahkannya ke komponen tabel
            (lihat vendor/filament/filament/.../components/page/index.blade.php).
            Di mode rincian tabelnya tidak dirender, jadi tanpa baris ini tombol
            header yang pakai modal (Analyze, Buat Kartu AI, Ambil Data Audiens)
            diklik tanpa terjadi apa-apa.
        --}}
        <x-filament-actions::modals />
    @endif
</x-filament-panels::page>
