{{--
    Campaign Summary versi cetak. Angkanya diambil dari App\Service\CampaignSummary
    yang sama dengan halaman Filament, jadi tidak ada rumus yang ditulis dua kali.

    dompdf tidak mengenal flexbox/grid — semua tata letak di sini pakai <table>.
    Jangan tambahkan `* { margin: 0 }`: itu menghapus margin @page. Lihat memori
    reference_dompdf_gotchas.
--}}
@php
    $t = $summary->totals();
    $sentiment = $summary->sentiment();
    $buckets = config('sentiment.buckets');
    $skor = $summary->successScore();
    $angka = fn($n) => number_format((int) $n, 0, ',', '.');
    $kartu = array_values($summary->cards());
    $barisKartu = array_chunk($kartu, 4);
@endphp
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>Campaign Summary - {{ $campaign->campaign_name }}</title>
    <style>
        @page { margin: 18mm 12mm; }
        body { font-family: 'DejaVu Sans', sans-serif; font-size: 8.5pt; color: #111; }
        h1 { font-size: 15pt; margin: 0 0 2px; }
        .muted { color: #666; }
        .head { width: 100%; border-bottom: 2px solid #111; padding-bottom: 4px; margin-bottom: 6px; }
        .head td { vertical-align: bottom; }
        .logo { height: 34px; }
        .sec { font-size: 10pt; font-weight: bold; margin: 9px 0 4px; }
        .cards { width: 100%; border-collapse: separate; border-spacing: 4px; }
        .cards td { width: 25%; border: 1px solid #d4d4d4; border-radius: 3px; padding: 4px 8px; }
        .cl { font-size: 7pt; text-transform: uppercase; letter-spacing: .4px; color: #666; }
        .cv { font-size: 11pt; font-weight: bold; padding-top: 1px; }
        .ch { font-size: 6.5pt; color: #888; }
        .box { border: 1px solid #d4d4d4; border-radius: 3px; padding: 8px; }
        .badge { display: inline-block; padding: 2px 6px; border-radius: 3px; font-size: 7.5pt; margin: 0 3px 3px 0; }
        .score { font-size: 18pt; font-weight: bold; }
        table.data { width: 100%; border-collapse: collapse; margin-top: 4px; }
        table.data th { background: #111; color: #fff; font-size: 7pt; padding: 4px 3px; text-align: left; }
        table.data td { border-bottom: 1px solid #e5e5e5; padding: 3px; font-size: 7.5pt; }
        .r { text-align: right; }
        .bar { height: 7px; background: #eee; }
        .bar span { display: block; height: 7px; }
    </style>
</head>
<body>

<table class="head">
    <tr>
        <td>
            @if ($logoBase64)
                <img src="{{ $logoBase64 }}" class="logo" alt="Beyond Viral">
            @endif
            <h1>Campaign Summary</h1>
            <div>{{ $campaign->campaign_name }}</div>
            <div class="muted">
                {{ $campaign->client?->nama_brand ?? $campaign->agency_name ?? '—' }}
                @if ($campaign->start_date)
                    · {{ \Illuminate\Support\Carbon::parse($campaign->start_date)->format('d M Y') }}
                    – {{ $campaign->end_date ? \Illuminate\Support\Carbon::parse($campaign->end_date)->format('d M Y') : 'berjalan' }}
                @endif
            </div>
        </td>
        <td class="r muted">
            Dicetak {{ now()->format('d M Y H:i') }}<br>
            Status: {{ ucfirst((string) $campaign->status) }}
        </td>
    </tr>
</table>

<table class="cards">
    @foreach ($barisKartu as $baris)
        <tr>
            @foreach ($baris as $card)
                <td>
                    <div class="cl">{{ $card['label'] }}</div>
                    <div class="cv">{{ $card['value'] }}</div>
                    <div class="ch">{{ $card['hint'] ?? '' }}</div>
                </td>
            @endforeach
            @for ($i = count($baris); $i < 4; $i++)
                <td style="border:none"></td>
            @endfor
        </tr>
    @endforeach
</table>

@if ($campaign->ai_summary)
    <div class="sec">Ringkasan AI</div>
    <div class="box" style="white-space:pre-line">{{ $campaign->ai_summary }}</div>
@endif

<div class="sec">Campaign Performance</div>
<table style="width:100%">
    <tr>
        <td style="width:20%;vertical-align:top;padding-right:6px">
            <div class="box">
                <div class="cl">Success Metrics</div>
                <div class="score">{{ $skor['score'] }}<span class="muted" style="font-size:10pt">/{{ $skor['max'] }}</span></div>
                <div class="ch">metrik yang lolos benchmark</div>
            </div>
        </td>
        <td style="width:28%;vertical-align:top;padding-right:6px">
            <div class="box">
                <div class="cl">Top 3 Creator</div>
                @forelse ($summary->topCreators() as $creator)
                    <div style="padding-top:3px">
                        &#64;{{ $creator->username ?: $creator->creator_name }}
                        <span class="muted">· {{ $angka($creator->total_engagement) }}</span>
                    </div>
                @empty
                    <div class="muted" style="padding-top:3px">Belum ada data.</div>
                @endforelse
            </div>
        </td>
        <td style="vertical-align:top">
            <div class="box">
                <div class="cl">Metrics Overview</div>
                <div style="padding-top:4px">
                    @foreach ($summary->metricsOverview() as $m)
                        @php
                            $warna = match ($m['verdict']) {
                                'excellent' => '#86efac',
                                'good' => '#fde68a',
                                'bad' => '#fca5a5',
                                default => '#e5e5e5',
                            };
                        @endphp
                        <span class="badge" style="background:{{ $warna }}">{{ $m['label'] }} {{ $m['value'] }}</span>
                    @endforeach
                </div>
            </div>
        </td>
    </tr>
</table>

<div class="sec">Campaign Sentiments Summary</div>
@if ($sentiment['total'] === 0)
    <div class="box muted">Komentar belum pernah diambil, jadi bagian ini kosong.</div>
@else
    <table style="width:100%">
        <tr>
            <td style="width:20%;vertical-align:top;padding-right:6px">
                <div class="box">
                    <div class="cl">Sentiments Score</div>
                    <div class="score">{{ $sentiment['score'] }}<span class="muted" style="font-size:10pt">/5</span></div>
                    <div class="ch">{{ $angka($sentiment['total']) }} komentar</div>
                </div>
            </td>
            <td style="width:28%;vertical-align:top;padding-right:6px">
                <div class="box">
                    <div class="cl">Top 10 Buzz Word</div>
                    <div style="padding-top:4px">
                        @forelse ($summary->buzzWords() as $kata => $jumlah)
                            <span class="badge" style="background:#eee">{{ $kata }} <b>{{ $jumlah }}</b></span>
                        @empty
                            <span class="muted">Tidak ada kata yang menonjol.</span>
                        @endforelse
                    </div>
                </div>
            </td>
            <td style="vertical-align:top">
                <div class="box">
                    <div class="cl">Sebaran Sentimen</div>
                    <table style="width:100%;margin-top:4px">
                        @foreach ($buckets as $kunci => $bucket)
                            @php $persen = $sentiment['percentages'][$kunci] ?? 0; @endphp
                            <tr>
                                <td style="width:70px">{{ $bucket['label'] }}</td>
                                <td>
                                    <div class="bar"><span style="width:{{ min(100, $persen) }}%;background:{{ $bucket['color'] }}"></span></div>
                                </td>
                                <td class="r" style="width:90px">
                                    {{ $angka($sentiment['counts'][$kunci] ?? 0) }} ({{ number_format($persen, 2) }}%)
                                </td>
                            </tr>
                        @endforeach
                    </table>
                </div>
            </td>
        </tr>
    </table>
@endif

<div class="sec">Content List</div>
<table class="data">
    <thead>
        <tr>
            <th>Creator</th>
            <th>Status</th>
            <th>Platform</th>
            <th>Category</th>
            <th class="r">Cost (IDR)</th>
            <th class="r">View</th>
            <th class="r">Engagement</th>
            <th class="r">Like</th>
            <th class="r">Comment</th>
            <th class="r">Share</th>
            <th class="r">Save</th>
            <th class="r">CPE (IDR)</th>
        </tr>
    </thead>
    <tbody>
        @forelse ($summary->kols as $kol)
            <tr>
                <td>
                    {{ $kol->creator_name }}
                    @if ($kol->username)<span class="muted">&#64;{{ $kol->username }}</span>@endif
                </td>
                <td>{{ $kol->isPublished() ? 'COMPLETED' : 'PENDING' }}</td>
                <td>{{ \App\Models\BvCampaignKol::PLATFORMS[$kol->platform] ?? ucfirst((string) $kol->platform) }}</td>
                <td>{{ $kol->tier ? ucfirst($kol->tier) : '—' }}</td>
                <td class="r">{{ number_format((float) $kol->price, 0, ',', '.') }}</td>
                <td class="r">{{ $angka($kol->views) }}</td>
                <td class="r">{{ $angka($kol->total_engagement) }}</td>
                <td class="r">{{ $angka($kol->likes) }}</td>
                <td class="r">{{ $angka($kol->comments) }}</td>
                <td class="r">{{ $angka($kol->shares) }}</td>
                <td class="r">{{ $angka($kol->saves) }}</td>
                <td class="r">{{ number_format($kol->cpe(), 0, ',', '.') }}</td>
            </tr>
        @empty
            <tr><td colspan="12" class="muted">Belum ada KOL yang di-approve.</td></tr>
        @endforelse

        {{-- Total ditaruh di <tbody>, bukan <tfoot>: dompdf mengulang tfoot
             tiap halaman dan bisa melemparnya sendirian ke halaman kosong. --}}
        <tr style="font-weight:bold;background:#f2f2f2">
            <td colspan="4">Total ({{ $summary->published()->count() }} tayang / {{ $summary->kols->count() }} KOL)</td>
            <td class="r">{{ number_format($t['cost'], 0, ',', '.') }}</td>
            <td class="r">{{ $angka($t['views']) }}</td>
            <td class="r">{{ $angka($t['engagement']) }}</td>
            <td class="r">{{ $angka($t['likes']) }}</td>
            <td class="r">{{ $angka($t['comments']) }}</td>
            <td class="r">{{ $angka($t['shares']) }}</td>
            <td class="r">{{ $angka($t['saves']) }}</td>
            <td class="r">{{ number_format($summary->cpe(), 0, ',', '.') }}</td>
        </tr>
    </tbody>
</table>

</body>
</html>
