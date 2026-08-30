{{--
    Kartu profil KOL — angka gabungan lintas channel + tulisan model AI.
    Tata letak pakai <table>: dompdf tidak mengenal flex/grid.
--}}
@php
    $angka = fn($n) => number_format((int) $n, 0, ',', '.');
@endphp
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>Kartu KOL - {{ $kol->username }}</title>
    <style>
        @page { margin: 18mm 16mm; }
        body { font-family: 'DejaVu Sans', sans-serif; font-size: 9.5pt; color: #111; }
        h1 { font-size: 16pt; margin: 0; }
        .muted { color: #666; }
        .head { width: 100%; border-bottom: 2px solid #111; padding-bottom: 6px; margin-bottom: 12px; }
        .head td { vertical-align: bottom; }
        .logo { height: 32px; }
        .sec { font-size: 11pt; font-weight: bold; margin: 16px 0 6px; }
        .chip { display: inline-block; background: #eee; border-radius: 3px; padding: 1px 6px; font-size: 8pt; margin-right: 4px; }
        .cards { width: 100%; border-collapse: separate; border-spacing: 5px; }
        .cards td { width: 25%; border: 1px solid #d4d4d4; border-radius: 3px; padding: 6px 8px; }
        .cl { font-size: 7.5pt; text-transform: uppercase; letter-spacing: .4px; color: #666; }
        .cv { font-size: 13pt; font-weight: bold; padding-top: 2px; }
        table.data { width: 100%; border-collapse: collapse; }
        table.data th { background: #111; color: #fff; font-size: 8pt; padding: 5px 4px; text-align: left; }
        table.data td { border-bottom: 1px solid #e5e5e5; padding: 4px; font-size: 8.5pt; }
        .r { text-align: right; }
        .insight { border: 1px solid #d4d4d4; border-radius: 3px; padding: 10px; white-space: pre-line; line-height: 1.5; }
        .foot { margin-top: 14px; font-size: 7.5pt; color: #888; }
    </style>
</head>
<body>

<table class="head">
    <tr>
        <td>
            @if ($logoBase64)<img src="{{ $logoBase64 }}" class="logo" alt="Beyond Viral">@endif
            <h1>&#64;{{ $kol->username }}</h1>
            <div class="muted">
                {{ $kol->full_name ?: 'Nama lengkap belum tersimpan' }}
            </div>
            <div style="margin-top:4px">
                <span class="chip">{{ $gabungan['tier'] }}</span>
                @foreach ($channels as $c)
                    <span class="chip">{{ $c->channel }}</span>
                @endforeach
            </div>
        </td>
        <td class="r muted">Dicetak {{ now()->format('d M Y H:i') }}</td>
    </tr>
</table>

<table class="cards">
    <tr>
        <td>
            <div class="cl">Total Followers</div>
            <div class="cv">{{ $angka($gabungan['followers']) }}</div>
        </td>
        <td>
            <div class="cl">Total Engagements</div>
            <div class="cv">{{ $angka($gabungan['engagements']) }}</div>
        </td>
        <td>
            <div class="cl">ER Gabungan</div>
            <div class="cv">{{ number_format($gabungan['engagement_rate'], 2) }}%</div>
        </td>
        <td>
            <div class="cl">Avg Views</div>
            <div class="cv">{{ $angka($gabungan['average_views']) }}</div>
        </td>
    </tr>
</table>

<div class="sec">Analisis AI</div>
<div class="insight">{{ $kol->ai_insight }}</div>

<div class="sec">Channel</div>
<table class="data">
    <thead>
        <tr>
            <th>Channel</th><th>Username</th>
            <th class="r">Followers</th><th class="r">ER</th>
            <th class="r">Engagements</th><th class="r">Avg Views</th>
            <th>Update Terakhir</th>
        </tr>
    </thead>
    <tbody>
        @foreach ($channels as $c)
            <tr>
                <td>{{ $c->channel }}</td>
                <td>&#64;{{ $c->username }}</td>
                <td class="r">{{ $angka($c->followers) }}</td>
                <td class="r">{{ number_format((float) $c->engagement_rate, 2) }}%</td>
                <td class="r">{{ $angka($c->engagements) }}</td>
                <td class="r">{{ $angka($c->average_views) }}</td>
                <td>{{ $c->terakhir_update?->format('d M Y') ?? '—' }}</td>
            </tr>
        @endforeach
    </tbody>
</table>

<p class="foot">
    Analisis ditulis {{ $kol->ai_insight_at?->format('d M Y H:i') }} oleh
    {{ config('ai.providers.' . config('ai.default') . '.models.text.default', config('ai.default')) }},
    berdasarkan angka yang tersimpan saat scraping terakhir. Periksa ulang sebelum dikirim ke client.
</p>

</body>
</html>
