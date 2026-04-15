<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>Kontrak KOL - {{ $spk->spk_number }}</title>
    <style>
        @page {
            size: A4 portrait;
            margin: 15mm 15mm 20mm 15mm;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: Arial, sans-serif;
            font-size: 9.5px;
            color: #1a1a1a;
            background: #fff;
            line-height: 1.5;
        }

        /* ─── HEADER ─────────────────────────────────────────── */
        .header-wrap {
            width: 100%;
            border-bottom: 3px solid #49009F;
            padding-bottom: 10px;
            margin-bottom: 10px;
        }

        .header-table {
            width: 100%;
            border-collapse: collapse;
        }

        .header-logo-cell {
            width: 22%;
            vertical-align: middle;
        }

        .header-logo-cell img {
            max-height: 55px;
            width: auto;
        }

        .header-logo-cell .logo-placeholder {
            font-size: 16px;
            font-weight: bold;
            color: #49009F;
            letter-spacing: 1px;
        }

        .header-title-cell {
            width: 56%;
            text-align: center;
            vertical-align: middle;
            padding: 0 10px;
        }

        .header-title-id {
            font-size: 14px;
            font-weight: bold;
            color: #49009F;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        .header-title-en {
            font-size: 9px;
            color: #555;
            letter-spacing: 0.5px;
            margin-top: 2px;
        }

        .header-meta-cell {
            width: 22%;
            text-align: right;
            vertical-align: middle;
        }

        .header-meta-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 8.5px;
        }

        .header-meta-table td {
            padding: 1px 3px;
        }

        .header-meta-table .label {
            color: #777;
            white-space: nowrap;
        }

        .header-meta-table .value {
            font-weight: bold;
            color: #1a1a1a;
        }

        /* SPK badge */
        .spk-badge {
            display: inline-block;
            background: #49009F;
            color: #DAFF01;
            padding: 3px 10px;
            border-radius: 3px;
            font-size: 9px;
            font-weight: bold;
            margin-bottom: 4px;
            letter-spacing: 0.5px;
        }

        /* ─── PARTIES BLOCK ──────────────────────────────────── */
        .parties-block {
            background: #f8f5ff;
            border: 1px solid #d9d0f0;
            border-radius: 4px;
            padding: 10px 12px;
            margin-bottom: 12px;
            font-size: 9px;
        }

        .parties-block .intro {
            margin-bottom: 8px;
            color: #333;
        }

        .parties-table {
            width: 100%;
            border-collapse: collapse;
        }

        .parties-table td {
            padding: 3px 6px;
            vertical-align: top;
        }

        .party-label {
            width: 28%;
            font-weight: bold;
            color: #49009F;
            white-space: nowrap;
        }

        .party-colon {
            width: 3%;
            text-align: center;
        }

        .party-value {
            width: 69%;
        }

        .party-divider {
            height: 6px;
        }

        /* ─── PASAL / ARTICLES ───────────────────────────────── */
        .article {
            margin-bottom: 10px;
            page-break-inside: avoid;
        }

        .article-header {
            background: #49009F;
            color: #DAFF01;
            padding: 4px 10px;
            font-size: 9.5px;
            font-weight: bold;
            border-radius: 3px 3px 0 0;
        }

        .article-header .en {
            font-size: 8px;
            font-weight: normal;
            color: #e0d0ff;
            margin-left: 6px;
        }

        .article-body {
            border: 1px solid #d9d0f0;
            border-top: none;
            border-radius: 0 0 3px 3px;
            padding: 8px 10px;
        }

        .bilingual-row {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 5px;
        }

        .bilingual-row td {
            vertical-align: top;
            padding: 0 4px;
        }

        .bilingual-row .col-id {
            width: 50%;
            padding-right: 8px;
            border-right: 1px solid #e8e0f8;
        }

        .bilingual-row .col-en {
            width: 50%;
            padding-left: 8px;
            color: #444;
        }

        .lang-badge {
            display: inline-block;
            font-size: 7px;
            font-weight: bold;
            padding: 1px 4px;
            border-radius: 2px;
            margin-bottom: 3px;
        }

        .lang-id { background: #eef; color: #49009F; }
        .lang-en { background: #ffe; color: #666; }

        ol.contract-list {
            padding-left: 18px;
            margin: 4px 0;
        }

        ol.contract-list li {
            margin-bottom: 3px;
        }

        ul.contract-list {
            padding-left: 16px;
            margin: 4px 0;
        }

        ul.contract-list li {
            margin-bottom: 3px;
        }

        /* ─── SOW TABLE ──────────────────────────────────────── */
        .sow-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 9px;
            margin-top: 5px;
        }

        .sow-table th {
            background: #49009F;
            color: #DAFF01;
            padding: 4px 8px;
            text-align: left;
            font-size: 8.5px;
        }

        .sow-table td {
            border: 1px solid #d9d0f0;
            padding: 4px 8px;
            vertical-align: top;
        }

        .sow-table tr:nth-child(even) td {
            background: #f9f7ff;
        }

        /* ─── PAYMENT BLOCK ──────────────────────────────────── */
        .payment-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 9px;
        }

        .payment-table td {
            border: 1px solid #d9d0f0;
            padding: 4px 8px;
        }

        .payment-table .lbl {
            font-weight: bold;
            background: #f3efff;
            width: 35%;
        }

        .payment-table .total-row td {
            background: #49009F;
            color: #DAFF01;
            font-weight: bold;
        }

        .bank-box {
            margin-top: 6px;
            border: 1px dashed #49009F;
            border-radius: 4px;
            padding: 6px 10px;
            background: #faf8ff;
        }

        .bank-box .bank-title {
            font-size: 8px;
            font-weight: bold;
            color: #49009F;
            text-transform: uppercase;
            margin-bottom: 3px;
        }

        .bank-detail-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 9px;
        }

        .bank-detail-table td {
            padding: 1px 3px;
            vertical-align: top;
        }

        .bank-detail-table .lbl {
            width: 35%;
            color: #555;
        }

        /* ─── SIGNATURE SECTION ──────────────────────────────── */
        .signature-section {
            margin-top: 14px;
            page-break-inside: avoid;
        }

        .signature-title {
            text-align: center;
            font-size: 9px;
            color: #555;
            margin-bottom: 10px;
        }

        .signature-table {
            width: 100%;
            border-collapse: collapse;
        }

        .signature-table td {
            width: 50%;
            text-align: center;
            vertical-align: top;
            padding: 0 20px;
        }

        .sig-box {
            border: 1px solid #d9d0f0;
            border-radius: 4px;
            padding: 8px 10px;
        }

        .sig-party-label {
            font-size: 8px;
            color: #777;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 2px;
        }

        .sig-party-name {
            font-size: 9.5px;
            font-weight: bold;
            color: #49009F;
            margin-bottom: 4px;
        }

        .sig-party-role {
            font-size: 8px;
            color: #555;
        }

        .sig-stamp-area {
            height: 55px;
            border-bottom: 1px solid #ccc;
            margin: 8px 0;
        }

        .sig-name-line {
            font-size: 9.5px;
            font-weight: bold;
            margin-top: 4px;
        }

        .sig-role-line {
            font-size: 8px;
            color: #555;
        }

        /* ─── FOOTER ─────────────────────────────────────────── */
        .doc-footer {
            margin-top: 10px;
            border-top: 1px solid #d9d0f0;
            padding-top: 5px;
            text-align: center;
            font-size: 7.5px;
            color: #aaa;
        }

        /* ─── UTILITIES ──────────────────────────────────────── */
        .highlight {
            font-weight: bold;
            color: #49009F;
        }

        .text-center { text-align: center; }
        .mt4 { margin-top: 4px; }
        .mb4 { margin-bottom: 4px; }
    </style>
</head>
<body>

<!-- ═══════════════════════════════════════════════════════
     HEADER
════════════════════════════════════════════════════════ -->
<div class="header-wrap">
    <table class="header-table">
        <tr>
            <!-- Logo -->
            <td class="header-logo-cell">
                @if($logoBase64)
                    <img src="{{ $logoBase64 }}" alt="Beyond Viral Logo">
                @else
                    <span class="logo-placeholder">BEYOND VIRAL</span>
                @endif
            </td>

            <!-- Title -->
            <td class="header-title-cell">
                <div class="spk-badge">{{ $spk->spk_number }}</div>
                <div class="header-title-id">Perjanjian Kerja Sama KOL</div>
                <div class="header-title-en">KOL Collaboration Agreement</div>
            </td>

            <!-- Meta -->
            <td class="header-meta-cell">
                <table class="header-meta-table">
                    <tr>
                        <td class="label">Tgl. Perjanjian</td>
                        <td class="label">:</td>
                        <td class="value">{{ $tanggalId }}</td>
                    </tr>
                    <tr>
                        <td class="label">Agreement Date</td>
                        <td class="label">:</td>
                        <td class="value">{{ $tanggalEn }}</td>
                    </tr>
                    <tr>
                        <td class="label">Campaign</td>
                        <td class="label">:</td>
                        <td class="value">{{ $spk->nama_campaign ?? '-' }}</td>
                    </tr>
                    <tr>
                        <td class="label">Status</td>
                        <td class="label">:</td>
                        <td class="value" style="text-transform: capitalize;">{{ $spk->status }}</td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</div>

<!-- ═══════════════════════════════════════════════════════
     PARTIES
════════════════════════════════════════════════════════ -->
<div class="parties-block">
    <div class="intro">
        Perjanjian Kerja Sama ini (<em>"Perjanjian"</em>) dibuat dan berlaku efektif pada tanggal
        <span class="highlight">{{ $tanggalId }}</span>, oleh dan antara: &nbsp;|&nbsp;
        This Collaboration Agreement (<em>"Agreement"</em>) is entered into and made effective on
        <span class="highlight">{{ $tanggalEn }}</span>, by and between:
    </div>
    <table class="parties-table">
        <!-- Pihak A -->
        <tr>
            <td class="party-label">Pihak A / Party A</td>
            <td class="party-colon">:</td>
            <td class="party-value">
                <strong>PT Bisa Viral Butuh Usaha</strong>, selaku KOL Agency
                / <em>as KOL Agency</em>
                (selanjutnya disebut <strong>"Pihak A"</strong> / hereinafter referred to as <strong>"Party A"</strong>)
            </td>
        </tr>
        <tr><td colspan="3" class="party-divider"></td></tr>
        <!-- Pihak B -->
        <tr>
            <td class="party-label">Pihak B / Party B</td>
            <td class="party-colon">:</td>
            <td class="party-value">
                <strong>{{ $spk->pihak_kedua_nama_lengkap ?? '-' }}</strong>
                @if($spk->pihak_kedua_nama_akun)
                    (akun: <em>{{ $spk->pihak_kedua_nama_akun }}</em>)
                @endif
                @if($spk->pihak_kedua_nik)
                    , NIK: {{ $spk->pihak_kedua_nik }}
                @endif
                @if($spk->pihak_kedua_alamat)
                    , beralamat di {{ $spk->pihak_kedua_alamat }} /
                    domiciled at {{ $spk->pihak_kedua_alamat }}
                @endif
                (selanjutnya disebut <strong>"Pihak B"</strong> / hereinafter referred to as <strong>"Party B"</strong>)
            </td>
        </tr>
    </table>
</div>

<!-- ═══════════════════════════════════════════════════════
     PASAL 1 – RUANG LINGKUP & DELIVERABLES
════════════════════════════════════════════════════════ -->
<div class="article">
    <div class="article-header">
        Pasal 1 – Ruang Lingkup & Hasil yang Diharapkan
        <span class="en">/ Article 1 – Scope & Deliverables</span>
    </div>
    <div class="article-body">
        <table class="bilingual-row">
            <tr>
                <td class="col-id">
                    <span class="lang-badge lang-id">ID</span><br>
                    Para Pihak sepakat menjalin kerja sama dalam rangka pelaksanaan kampanye
                    <strong>{{ $spk->nama_campaign ?? '-' }}</strong> selama periode
                    <strong>{{ $spk->timeline_kerja_sama ?? '-' }}</strong>.
                </td>
                <td class="col-en">
                    <span class="lang-badge lang-en">EN</span><br>
                    The Parties agree to collaborate for the campaign
                    <strong>{{ $spk->nama_campaign ?? '-' }}</strong> during the period of
                    <strong>{{ $spk->timeline_kerja_sama ?? '-' }}</strong>.
                </td>
            </tr>
        </table>

        @if($spk->sow_disepakati)
        <div class="mt4">
            <strong>Scope of Work yang Disepakati / Agreed Scope of Work:</strong>
        </div>
        <table class="sow-table">
            <thead>
                <tr>
                    <th style="width: 5%;">No</th>
                    <th>Deliverable / Kewajiban</th>
                </tr>
            </thead>
            <tbody>
                @foreach(array_filter(explode("\n", trim($spk->sow_disepakati))) as $i => $item)
                <tr>
                    <td style="text-align:center;">{{ $i + 1 }}</td>
                    <td>{{ trim($item) }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
        @endif
    </div>
</div>

<!-- ═══════════════════════════════════════════════════════
     PASAL 2 – TANGGUNG JAWAB
════════════════════════════════════════════════════════ -->
<div class="article">
    <div class="article-header">
        Pasal 2 – Tanggung Jawab Para Pihak
        <span class="en">/ Article 2 – Responsibilities</span>
    </div>
    <div class="article-body">
        <table class="bilingual-row">
            <tr>
                <td class="col-id">
                    <span class="lang-badge lang-id">ID</span>
                    <ol class="contract-list">
                        <li>Pihak A wajib memberikan brief kampanye, aset kreatif, dan arahan konten secara tepat waktu.</li>
                        <li>Pihak B wajib memproduksi dan mempublikasikan konten sesuai brief dan timeline yang telah disepakati.</li>
                        <li>Pihak B wajib memberikan laporan tayang (screenshot/link) kepada Pihak A selambat-lambatnya H+3 setelah konten diterbitkan.</li>
                        <li>Para Pihak wajib mematuhi ketentuan hukum dan peraturan yang berlaku.</li>
                    </ol>
                </td>
                <td class="col-en">
                    <span class="lang-badge lang-en">EN</span>
                    <ol class="contract-list">
                        <li>Party A shall provide campaign briefs, creative assets, and content guidelines in a timely manner.</li>
                        <li>Party B shall produce and publish content in accordance with the agreed brief and timeline.</li>
                        <li>Party B shall submit a posting report (screenshot/link) to Party A no later than D+3 after content is published.</li>
                        <li>Both Parties shall comply with all applicable laws and regulations.</li>
                    </ol>
                </td>
            </tr>
        </table>
    </div>
</div>

<!-- ═══════════════════════════════════════════════════════
     PASAL 3 – JANGKA WAKTU
════════════════════════════════════════════════════════ -->
<div class="article">
    <div class="article-header">
        Pasal 3 – Jangka Waktu Perjanjian
        <span class="en">/ Article 3 – Term of Agreement</span>
    </div>
    <div class="article-body">
        <table class="bilingual-row">
            <tr>
                <td class="col-id">
                    <span class="lang-badge lang-id">ID</span>
                    Perjanjian ini berlaku selama periode kampanye
                    <strong>{{ $spk->timeline_kerja_sama ?? '-' }}</strong>.
                    Apabila dibutuhkan perpanjangan, Para Pihak wajib menyepakatinya secara tertulis
                    paling lambat 3 hari sebelum berakhirnya perjanjian.
                </td>
                <td class="col-en">
                    <span class="lang-badge lang-en">EN</span>
                    This Agreement is valid for the campaign period of
                    <strong>{{ $spk->timeline_kerja_sama ?? '-' }}</strong>.
                    Any extension requires written mutual consent at least 3 days prior to expiry.
                </td>
            </tr>
        </table>
    </div>
</div>

<!-- ═══════════════════════════════════════════════════════
     PASAL 4 – HARGA & PEMBAYARAN
════════════════════════════════════════════════════════ -->
<div class="article">
    <div class="article-header">
        Pasal 4 – Harga dan Pembayaran
        <span class="en">/ Article 4 – Fee and Payment</span>
    </div>
    <div class="article-body">
        <!-- Nominal -->
        <table class="payment-table mb4">
            <tr>
                <td class="lbl">Nominal Kesepakatan / Agreed Fee</td>
                <td>
                    <strong class="highlight">{{ $spk->getFormattedNominalAttribute() }}</strong>
                </td>
            </tr>
            @if($spk->nominal_terbilang)
            <tr>
                <td class="lbl">Terbilang / In Words</td>
                <td><em>{{ $spk->nominal_terbilang }}</em></td>
            </tr>
            @endif
            @if($spk->termin_pembayaran_1)
            <tr>
                <td class="lbl">Termin 1 / Payment 1</td>
                <td>{{ $spk->termin_pembayaran_1 }}</td>
            </tr>
            @endif
            @if($spk->termin_pembayaran_2)
            <tr>
                <td class="lbl">Termin 2 / Payment 2</td>
                <td>{{ $spk->termin_pembayaran_2 }}</td>
            </tr>
            @endif
        </table>

        <!-- Bank Info -->
        @if($spk->nomor_rekening || $spk->nama_bank)
        <div class="bank-box">
            <div class="bank-title">Transfer ke / Transfer to:</div>
            <table class="bank-detail-table">
                @if($spk->nama_bank)
                <tr>
                    <td class="lbl">Bank</td>
                    <td>: <strong>{{ $spk->nama_bank }}</strong>
                        @if($spk->kantor_cabang_bank) – {{ $spk->kantor_cabang_bank }} @endif
                    </td>
                </tr>
                @endif
                @if($spk->nomor_rekening)
                <tr>
                    <td class="lbl">No. Rekening</td>
                    <td>: <strong>{{ $spk->nomor_rekening }}</strong></td>
                </tr>
                @endif
                @if($spk->atas_nama_rekening)
                <tr>
                    <td class="lbl">Atas Nama</td>
                    <td>: {{ $spk->atas_nama_rekening }}</td>
                </tr>
                @endif
            </table>
        </div>
        @endif
    </div>
</div>

<!-- ═══════════════════════════════════════════════════════
     PASAL 5 – KEKAYAAN INTELEKTUAL
════════════════════════════════════════════════════════ -->
<div class="article">
    <div class="article-header">
        Pasal 5 – Penggunaan Kekayaan Intelektual
        <span class="en">/ Article 5 – Intellectual Property</span>
    </div>
    <div class="article-body">
        <table class="bilingual-row">
            <tr>
                <td class="col-id">
                    <span class="lang-badge lang-id">ID</span>
                    Seluruh konten yang dipublikasikan oleh Pihak B dalam rangka kampanye ini dapat digunakan
                    kembali oleh Pihak A untuk keperluan promosi selama 12 bulan sejak tanggal tayang,
                    kecuali disepakati lain secara tertulis. Perjanjian ini tidak memindahkan hak kekayaan
                    intelektual lainnya tanpa kesepakatan tertulis.
                </td>
                <td class="col-en">
                    <span class="lang-badge lang-en">EN</span>
                    All content published by Party B under this campaign may be repurposed by Party A
                    for promotional use for 12 months from the posting date, unless otherwise agreed in
                    writing. This Agreement does not transfer any other intellectual property rights without
                    written consent.
                </td>
            </tr>
        </table>
    </div>
</div>

<!-- ═══════════════════════════════════════════════════════
     PASAL 6 – KERAHASIAAN
════════════════════════════════════════════════════════ -->
<div class="article">
    <div class="article-header">
        Pasal 6 – Kerahasiaan
        <span class="en">/ Article 6 – Confidentiality</span>
    </div>
    <div class="article-body">
        <table class="bilingual-row">
            <tr>
                <td class="col-id">
                    <span class="lang-badge lang-id">ID</span>
                    Para Pihak sepakat untuk menjaga kerahasiaan seluruh informasi yang dipertukarkan
                    sehubungan dengan Perjanjian ini. Kewajiban ini tetap berlaku meskipun Perjanjian
                    telah berakhir. Pihak B dilarang mengungkapkan informasi strategis, materi promosi,
                    dan produk yang belum diluncurkan ke publik.
                </td>
                <td class="col-en">
                    <span class="lang-badge lang-en">EN</span>
                    Both Parties agree to maintain strict confidentiality of all information exchanged
                    in connection with this Agreement. This obligation survives termination. Party B
                    shall not disclose strategic information, promotional materials, or unreleased products.
                </td>
            </tr>
        </table>
    </div>
</div>

<!-- ═══════════════════════════════════════════════════════
     PASAL 7 – PENGAKHIRAN & FORCE MAJEURE
════════════════════════════════════════════════════════ -->
<div class="article">
    <div class="article-header">
        Pasal 7 – Pengakhiran & Force Majeure
        <span class="en">/ Article 7 – Termination & Force Majeure</span>
    </div>
    <div class="article-body">
        <table class="bilingual-row">
            <tr>
                <td class="col-id">
                    <span class="lang-badge lang-id">ID</span>
                    <ol class="contract-list">
                        <li>Perjanjian ini tidak dapat diakhiri secara sepihak tanpa persetujuan tertulis pihak lainnya.</li>
                        <li>Pengakhiran sepihak (di luar force majeure) mewajibkan pihak yang mengakhiri memberikan kompensasi sebesar nilai yang disepakati bersama.</li>
                        <li>Force Majeure meliputi bencana alam, wabah penyakit, peperangan, kerusuhan sipil, atau perubahan kebijakan pemerintah yang berada di luar kendali Para Pihak.</li>
                    </ol>
                </td>
                <td class="col-en">
                    <span class="lang-badge lang-en">EN</span>
                    <ol class="contract-list">
                        <li>This Agreement may not be unilaterally terminated without the other Party's written consent.</li>
                        <li>Unilateral termination (except force majeure) obliges the terminating Party to compensate the other in a mutually agreed amount.</li>
                        <li>Force Majeure includes natural disasters, epidemics, wars, civil unrest, or government policy changes beyond the Parties' control.</li>
                    </ol>
                </td>
            </tr>
        </table>
    </div>
</div>

<!-- ═══════════════════════════════════════════════════════
     PASAL 8 – PENYELESAIAN SENGKETA
════════════════════════════════════════════════════════ -->
<div class="article">
    <div class="article-header">
        Pasal 8 – Penyelesaian Sengketa
        <span class="en">/ Article 8 – Dispute Resolution</span>
    </div>
    <div class="article-body">
        <table class="bilingual-row">
            <tr>
                <td class="col-id">
                    <span class="lang-badge lang-id">ID</span>
                    Sengketa diselesaikan secara musyawarah. Jika gagal, Para Pihak menunjuk Mediator
                    bersertifikat (terdaftar di Mahkamah Agung RI). Apabila mediasi tidak berhasil,
                    sengketa diselesaikan di Pengadilan Negeri Jakarta Selatan.
                </td>
                <td class="col-en">
                    <span class="lang-badge lang-en">EN</span>
                    Disputes shall first be resolved amicably. If unsuccessful, the Parties shall appoint
                    a certified Mediator (registered with the Supreme Court of Indonesia). Unresolved
                    disputes shall be settled under the jurisdiction of the South Jakarta District Court.
                </td>
            </tr>
        </table>
    </div>
</div>

<!-- ═══════════════════════════════════════════════════════
     PASAL 9 – PENUTUP
════════════════════════════════════════════════════════ -->
<div class="article">
    <div class="article-header">
        Pasal 9 – Penutup
        <span class="en">/ Article 9 – Closing</span>
    </div>
    <div class="article-body">
        <table class="bilingual-row">
            <tr>
                <td class="col-id">
                    <span class="lang-badge lang-id">ID</span>
                    Perjanjian ini dibuat dalam dua (2) rangkap asli, masing-masing dibubuhi materai
                    yang cukup dan memiliki kekuatan hukum yang sama, serta dipahami dan dilaksanakan
                    oleh Para Pihak tanpa paksaan dari pihak mana pun. Hal-hal yang belum diatur
                    akan disepakati secara tertulis.
                </td>
                <td class="col-en">
                    <span class="lang-badge lang-en">EN</span>
                    This Agreement is made in two (2) original copies, each bearing adequate duty stamps
                    and having equal legal force, to be fully understood and executed by the Parties
                    without coercion. Any unaddressed matters shall be agreed upon in writing.
                </td>
            </tr>
        </table>
    </div>
</div>

<!-- ═══════════════════════════════════════════════════════
     SIGNATURE SECTION
════════════════════════════════════════════════════════ -->
<div class="signature-section">
    <div class="signature-title">
        Ditandatangani pada / Signed on: <strong>{{ $tanggalId }}</strong> / <strong>{{ $tanggalEn }}</strong>
    </div>
    <table class="signature-table">
        <tr>
            <!-- Pihak A -->
            <td>
                <div class="sig-box">
                    <div class="sig-party-label">Pihak A / Party A</div>
                    <div class="sig-party-name">PT Bisa Viral Butuh Usaha</div>
                    <div class="sig-party-role">KOL Agency</div>
                    <div class="sig-stamp-area"></div>
                    <div class="sig-name-line">Gerry Hutomo</div>
                    <div class="sig-role-line">Direktur / Director</div>
                </div>
            </td>
            <!-- Pihak B -->
            <td>
                <div class="sig-box">
                    <div class="sig-party-label">Pihak B / Party B</div>
                    <div class="sig-party-name">{{ $spk->pihak_kedua_nama_lengkap ?? '-' }}</div>
                    @if($spk->pihak_kedua_nama_akun)
                    <div class="sig-party-role">{{ $spk->pihak_kedua_nama_akun }}</div>
                    @else
                    <div class="sig-party-role">Content Creator / KOL</div>
                    @endif
                    <div class="sig-stamp-area"></div>
                    <div class="sig-name-line">{{ $spk->pihak_kedua_nama_lengkap ?? '___________________' }}</div>
                    <div class="sig-role-line">Content Creator / KOL</div>
                </div>
            </td>
        </tr>
    </table>
</div>

<!-- ═══════════════════════════════════════════════════════
     FOOTER
════════════════════════════════════════════════════════ -->
<div class="doc-footer">
    Dokumen ini digenerate otomatis oleh sistem Beyond Viral &nbsp;|&nbsp;
    Generated: {{ $generatedAt }} &nbsp;|&nbsp;
    No. SPK: {{ $spk->spk_number }}
</div>

</body>
</html>
