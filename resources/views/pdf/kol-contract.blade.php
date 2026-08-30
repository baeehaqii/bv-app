@php
    $pertama = \App\Models\BvSPK::pihakPertama();
    $perusahaan = $pertama['perusahaan'];
    $sowLines = collect(preg_split('/\r\n|\r|\n/', (string) $spk->sow_disepakati))
        ->map(fn($l) => trim($l))
        ->filter()
        ->all();
    $nominal = 'Rp ' . number_format((float) $spk->nominal_kesepakatan, 0, ',', '.');

    $aktif = fn(string $k) => $spk->clauseEnabled($k);
    // Klausul ditulis BV lewat textarea → escape, tapi izinkan penekanan <strong>/<em>
    // yang dipakai teks bawaan.
    $klausul = fn(string $k) => strip_tags($spk->clauseText($k), '<strong><em><br>');
    $addons = $spk->activeAddons();

    /**
     * Penomoran ayat otomatis, counter di-reset di setiap pasal.
     * Draft asli melompat 3 → 6 dan meneruskan nomor ayat lintas pasal (6: 1-3,
     * 7: 4-10, 8: 11-14); itu salah ketik, jadi pasalnya dirapatkan ke 1-7 dan tiap
     * pasal mulai dari ayat 1. Klausul yang dimatikan juga tidak meninggalkan
     * nomor bolong.
     */
    $no = 0;
    $reset = function () use (&$no) { $no = 0; };
    $next = function () use (&$no) { return ++$no; };
@endphp
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>SPK {{ $spk->spk_number }}</title>
    <style>
        @page {
            size: A4 portrait;
            margin: 34mm 20mm 18mm 20mm;
        }

        /*
         * JANGAN tambahkan `margin` ke selector `*` di sini: di dompdf selector
         * universal ikut kena ke frame halaman, jadi `* { margin: 0 }` menghapus
         * margin @page di atas dan seluruh dokumen jadi menempel ke tepi kertas.
         */
        * { padding: 0; box-sizing: border-box; }
        p, div, table, h1, h2, h3 { margin: 0; }

        /*
         * DejaVu Serif, bukan Times New Roman: di dompdf keluarga Times hanya
         * punya metrik .afm tanpa file glyph, jadi <strong>/<em> tercetak sama
         * dengan teks normal — fatal untuk kontrak yang menandai "PIHAK PERTAMA"
         * dengan bold. DejaVu di-bundle dompdf lengkap dengan Bold & Italic.
         * DejaVu lebih lebar dari Times, karena itu ukurannya 10pt bukan 11pt.
         */
        body {
            font-family: "DejaVu Serif", serif;
            font-size: 9pt;
            line-height: 1.35;
            color: #000;
        }

        /* Kop surat — position:fixed di dompdf ikut tercetak di setiap halaman. */
        .kop {
            position: fixed;
            top: -29mm;
            left: 0;
            right: 0;
            height: 25mm;
        }

        .kop table { width: 100%; border-collapse: collapse; }
        .kop td { vertical-align: middle; }
        .kop-logo { width: 34mm; text-align: right; padding-right: 4mm; }
        .kop-logo img { height: 20mm; }
        .kop-logo .fallback {
            font-family: "DejaVu Sans", sans-serif;
            font-size: 20pt; font-weight: bold; color: #1f3d2b;
        }
        .kop-text { font-family: "DejaVu Sans", sans-serif; }
        .kop-nama { font-size: 15pt; font-weight: bold; color: #4b4b4b; letter-spacing: .3px; }
        .kop-brand { font-size: 12pt; color: #8a8a8a; }
        .kop-alamat { font-size: 6.5pt; color: #6f6f6f; line-height: 1.35; }
        .kop-alamat .site { color: #1f7a4d; text-decoration: underline; }

        .judul { text-align: center; font-weight: bold; margin-bottom: 2mm; }
        .judul div { line-height: 1.45; }

        p { text-align: justify; margin-bottom: 1.5mm; }

        .pasal-head {
            text-align: center;
            font-weight: bold;
            margin-top: 3mm;
            line-height: 1.45;
        }

        /* Ayat bernomor pakai tabel supaya hanging indent rapi di dompdf. */
        table.ayat { width: 100%; border-collapse: collapse; }
        table.ayat td { vertical-align: top; padding: 0 0 1.5mm 0; }
        table.ayat td.no { width: 8mm; }
        table.ayat td.isi { text-align: justify; }

        table.identitas { width: 100%; border-collapse: collapse; }
        table.identitas td { vertical-align: top; padding: 0 0 .5mm 0; }
        table.identitas td.label { width: 30mm; }
        table.identitas td.sep { width: 4mm; }

        table.rekening { width: 100%; border-collapse: collapse; margin: 1.5mm 0 1.5mm 12mm; }
        table.rekening td { vertical-align: top; padding: 1mm 0; }
        table.rekening td.label { width: 42mm; }
        table.rekening td.sep { width: 6mm; }

        .sow-list { margin: 0 0 0 6mm; }
        .sow-list div { text-align: justify; }

        .indent { margin-left: 8mm; }
        .indent-2 { margin-left: 16mm; }

        .ttd { width: 100%; border-collapse: collapse; margin-top: 6mm; }
        .ttd td { width: 50%; text-align: center; vertical-align: top; }
        .ttd .peran { font-weight: bold; padding-bottom: 2mm; }
        /* Tinggi tetap: blok tanda tangan tidak boleh mengkerut saat TTD belum ada,
           supaya versi kosong dan versi bertanda tangan tata letaknya sama. */
        .ttd .ttd-area { height: 20mm; }
        .ttd .ttd-area img { height: 18mm; }
        /* Wadah e-meterai: gambarnya ditempel manual lewat form SPK. Kotak putus-putus
           tetap tercetak saat kosong supaya ada tempat menempelkan meterai fisik. */
        .ttd .materai { display: inline-block; width: 24mm; height: 18mm; }
        /* dompdf tidak menengahkan teks lewat line-height pada inline-block,
           jadi tinggi + padding-top yang dipakai. */
        .ttd .materai-kosong { border: 1px dashed #999; color: #999; font-size: 7pt;
                               text-align: center; height: 11mm; padding-top: 7mm; }
        .ttd .materai img { width: 24mm; height: 18mm; }
        .ttd .nama { font-weight: bold; }
        .esign-note {
            margin-top: 5mm;
            font-size: 7.5pt;
            color: #444;
            text-align: center;
            font-style: italic;
        }
        .avoid-break { page-break-inside: avoid; }
    </style>
</head>
<body>

<div class="kop">
    <table>
        <tr>
            <td class="kop-logo">
                @if($logoBase64)
                    <img src="{{ $logoBase64 }}" alt="BV Network">
                @else
                    <span class="fallback">BV</span>
                @endif
            </td>
            <td class="kop-text">
                <div class="kop-nama">{{ $perusahaan }}</div>
                <div class="kop-brand">{{ $pertama['brand'] }}</div>
                <div class="kop-alamat">
                    <span class="site">{{ config('company.site') }}</span>
                    {!! implode('<br>', array_map('e', config('company.address_lines'))) !!}
                </div>
            </td>
        </tr>
    </table>
</div>

<div class="judul">
    <div>SURAT PERJANJIAN KERJA SAMA</div>
    <div>SPK NO. {{ $spk->spk_number }}</div>
    <div>{{ $perusahaan }}</div>
</div>

<p>
    Perjanjian Kerja Sama Beyond Viral (selanjutnya disebut &ldquo;Perjanjian&rdquo;) dilangsungkan dan
    ditandatangani di Jakarta pada tanggal <strong>{{ $tanggalId }}.</strong>
</p>

<p>
    Dalam hal ini bertindak untuk dan atas nama {{ $perusahaan }} dalam hal ini diwakili oleh
    {{ $pertama['nama'] }} selaku {{ $pertama['jabatan'] }} dari dan oleh karenanya sah bertindak untuk
    dan atas nama serta mewakili kepentingan {{ $perusahaan }} sebagai
    <strong>&ldquo;PIHAK PERTAMA&rdquo;</strong>
</p>

<table class="identitas">
    <tr>
        <td class="label">Nama</td>
        <td class="sep">:</td>
        <td>{{ $pertama['nama'] }}</td>
    </tr>
    <tr>
        <td class="label">Jabatan</td>
        <td class="sep">:</td>
        <td>{{ $pertama['jabatan'] }}</td>
    </tr>
    <tr>
        <td class="label">Alamat</td>
        <td class="sep">:</td>
        <td>{{ $pertama['alamat'] }}</td>
    </tr>
</table>

<p style="text-align: center;">Dan</p>

<p>
    Dalam hal ini bertindak untuk dan atas nama pribadi (selanjutnya disebut
    &ldquo;PIHAK KEDUA&rdquo;).
</p>

<p>Nama Lengkap : {{ $spk->pihak_kedua_nama_lengkap ?: '—' }}</p>
<p>Nama Akun : {{ $spk->pihak_kedua_nama_akun ?: '—' }}</p>
<p>NIK : {{ $spk->pihak_kedua_nik ?: '—' }}</p>
<p>Alamat : {{ $spk->pihak_kedua_alamat ?: '—' }}</p>

<p style="margin-top: 3mm;">
    <strong>PARA PIHAK</strong> telah sepakat untuk bekerja sama dan mengikatkan diri dalam Perjanjian
    ini dengan syarat-syarat dan ketentuan-ketentuan sebagai berikut:
</p>

{{-- ══════════ PASAL 1 ══════════ --}}
<div class="pasal-head">
    <div>PASAL 1</div>
    <div>MAKSUD DAN TUJUAN</div>
</div>

@php $reset(); @endphp
<table class="ayat">
    <tr>
        <td class="no">{{ $next() }}.</td>
        <td class="isi">
            Perjanjian ini dimaksudkan agar <strong>PIHAK PERTAMA</strong> dapat bekerja sama dengan
            <strong>PIHAK KEDUA</strong> yang sesuai dengan permintaan Pihak Pertama atau di tempat yang
            ditentukan oleh <strong>PIHAK PERTAMA.</strong>
        </td>
    </tr>
    <tr>
        <td class="no">{{ $next() }}.</td>
        <td class="isi">
            Tujuan dari Perjanjian ini adalah untuk menjamin kelangsungan
            <strong><em>Campaign {{ $spk->nama_campaign ?: '—' }}</em></strong> yang dilakukan oleh Klien
            <strong>PIHAK PERTAMA</strong> dan ketersediaan tenaga kerja dalam mendukung pencapaian
            kinerja dan usaha <strong>PIHAK PERTAMA.</strong>
        </td>
    </tr>
    <tr>
        <td class="no">{{ $next() }}.</td>
        <td class="isi">
            Pihak Kedua berkewajiban menjalankan tugas dan tanggung jawab utama sesuai kesepakatan
            dengan rincian kesepakatan sebagai berikut:
            <div class="sow-list">
                @forelse($sowLines as $line)
                    <div>-&nbsp;&nbsp;&nbsp;&nbsp;{{ $line }}</div>
                @empty
                    <div>-&nbsp;&nbsp;&nbsp;&nbsp;—</div>
                @endforelse
                <div style="margin-left: 8mm;">Timeline : <strong>{{ $spk->timeline_kerja_sama ?: '—' }}</strong></div>
            </div>
        </td>
    </tr>
    @if ($aktif('konten_tidak_dihapus'))
        <tr>
            <td class="no">{{ $next() }}.</td>
            <td class="isi">{!! $klausul('konten_tidak_dihapus') !!}</td>
        </tr>
    @endif
    <tr>
        <td class="no">{{ $next() }}.</td>
        <td class="isi">
            <strong>PIHAK KEDUA</strong> wajib mengikuti segala arahan/brief yang telah di kirim oleh
            <strong>PIHAK PERTAMA</strong>
        </td>
    </tr>
    <tr>
        <td class="no">{{ $next() }}.</td>
        <td class="isi">
            <strong>PIHAK KEDUA</strong> wajib mengikuti apabila konten yang telah dibuat tidak sesuai
            dengan brief yang telah di kirim oleh <strong>PIHAK PERTAMA</strong>
        </td>
    </tr>
    @if ($aktif('insight'))
        <tr>
            <td class="no">{{ $next() }}.</td>
            <td class="isi">{!! $klausul('insight') !!}</td>
        </tr>
    @endif
</table>

{{-- ══════════ PASAL 2 ══════════ --}}
<div class="pasal-head">
    <div>PASAL 2</div>
    <div>JANGKA WAKTU</div>
    <div>PERJANJIAN</div>
</div>

@php $reset(); @endphp
<table class="ayat">
    <tr>
        <td class="no">{{ $next() }}.</td>
        <td class="isi">
            Perjanjian ini berlaku efektif sejak ditandatangani oleh <strong>PARA PIHAK</strong> dan akan
            berakhir setelah Pekerjaan dan kewajiban-kewajiban diselesaikan seluruhnya, serta hak-hak
            diperoleh oleh <strong>PARA PIHAK</strong> (selanjutnya disebut sebagai
            &ldquo;<strong>Jangka Waktu Perjanjian</strong>&rdquo;).
        </td>
    </tr>
    <tr>
        <td class="no">{{ $next() }}.</td>
        <td class="isi">
            <strong>PARA PIHAK</strong> sepakat apabila terdapat perubahan dalam Jangka Waktu Perjanjian,
            maka akan diatur dalam Addendum Perjanjian yang disepakati dan ditandatangani oleh
            <strong>PARA PIHAK</strong> di kemudian hari.
        </td>
    </tr>
    @if ($aktif('eksklusivitas'))
        <tr>
            <td class="no">{{ $next() }}.</td>
            <td class="isi">{!! $klausul('eksklusivitas') !!}</td>
        </tr>
    @endif
</table>

{{-- ══════════ PASAL 3 ══════════ --}}
<div class="pasal-head">
    <div>PASAL 3</div>
    <div>PEMBAYARAN</div>
</div>

@php $reset(); @endphp
<table class="ayat">
    <tr>
        <td class="no">{{ $next() }}.</td>
        <td class="isi">
            Perjanjian mengenai harga kesepakatan bersifat pribadi dan rahasia.
            <strong>PIHAK PERTAMA</strong> dan <strong>PIHAK KEDUA</strong> tidak diperbolehkan
            membicarakan hal ini kepada pihak yang tidak bersangkutan.
        </td>
    </tr>
    <tr>
        <td class="no">{{ $next() }}.</td>
        <td class="isi">
            <strong>PIHAK KEDUA</strong> dapat menjalankan tugas-tugas dan tanggung jawab lainnya sesuai
            dengan Pasal 1 yang diberikan oleh <strong>PIHAK PERTAMA</strong> sesuai kebutuhan yang
            disepakati adalah sebesar <strong>{{ $nominal }}
            ({{ $spk->nominal_terbilang ?: \App\Models\BvSPK::terbilang((float) $spk->nominal_kesepakatan) }})</strong>{{ $aktif('pajak') ? ' di luar pajak' : '' }}.
        </td>
    </tr>
    <tr>
        <td class="no">{{ $next() }}.</td>
        <td class="isi">
            Menyetujui pembayaran Imbal Jasa Kerja Sama akan ditransfer ke rekening:
            <table class="rekening">
                <tr>
                    <td class="label">Atas Nama</td>
                    <td class="sep">:</td>
                    <td>{{ $spk->atas_nama_rekening ?: '—' }}</td>
                </tr>
                <tr>
                    <td class="label">Nomor Rekening</td>
                    <td class="sep">:</td>
                    <td>{{ $spk->nomor_rekening ?: '—' }}</td>
                </tr>
                <tr>
                    <td class="label">Bank</td>
                    <td class="sep">:</td>
                    <td>{{ $spk->nama_bank ?: '—' }}</td>
                </tr>
                <tr>
                    <td class="label">Kantor Cabang</td>
                    <td class="sep">:</td>
                    <td>{{ $spk->kantor_cabang_bank ?: '-' }}</td>
                </tr>
            </table>
        </td>
    </tr>
    <tr>
        <td class="no">{{ $next() }}.</td>
        <td class="isi">
            Waktu dan cara pembayaran seperti terlampir :
            <div class="indent-2">a.&nbsp;&nbsp;&nbsp;Termin Pembayaran 1 : {{ $spk->termin_pembayaran_1 ?: \App\Models\BvSPK::TERMIN_1_DEFAULT }}</div>
            @if(filled($spk->termin_pembayaran_2))
                <div class="indent-2">b.&nbsp;&nbsp;&nbsp;Termin Pembayaran 2 : {{ $spk->termin_pembayaran_2 }}</div>
            @endif
        </td>
    </tr>
</table>

@if ($aktif('pajak'))
    <p>{!! $klausul('pajak') !!}</p>
@endif

{{-- ══════════ PASAL 4 ══════════ --}}
{{-- Judulnya di draft tertulis "SANKSI-SANKSI DAN PENGAKHIRAN PERJANJIAN" — sama
     persis dengan pasal berikutnya, padahal isinya kerahasiaan. Ikut diperbaiki. --}}
<div class="pasal-head">
    <div>PASAL 4</div>
    <div>KERAHASIAAN</div>
</div>

@php $reset(); @endphp
<table class="ayat">
    <tr>
        <td class="no">{{ $next() }}.</td>
        <td class="isi">
            <strong>PARA PIHAK</strong> setuju untuk tidak memberitahukan kepada pihak manapun juga atas
            seluruh informasi baik lisan maupun tertulis dan/atau data, dokumen yang diperoleh dari
            pelaksanaan Perjanjian ini, termasuk tetapi tidak terbatas pada besarnya Honorarium yang
            diterima <strong>PIHAK KEDUA</strong>, baik selama Perjanjian ini berlangsung maupun setelah
            berakhir.
        </td>
    </tr>
    <tr>
        <td class="no">{{ $next() }}.</td>
        <td class="isi">
            <strong>PARA PIHAK</strong> sepakat bahwa seluruh isi Perjanjian ini harus diperlakukan secara
            rahasia sehingga tidak ada satu pun informasi sehubungan dengan Perjanjian ini dapat
            diberitahukan oleh salah satu <strong>PIHAK</strong> kepada pihak ketiga tanpa terlebih dahulu
            mendapat izin tertulis dari <strong>PIHAK</strong> lainnya, kecuali yang merupakan keharusan
            dalam rangka pelaksanaan Perjanjian ini atau disyaratkan oleh peraturan yang berlaku.
        </td>
    </tr>
    <tr>
        <td class="no">{{ $next() }}.</td>
        <td class="isi">
            Ketentuan Pasal ini tidak berlaku terhadap :
            <div class="indent">a. informasi yang sudah dimiliki atau sebelumnya telah diketahui oleh pihak penerima informasi pada saat pihak tersebut menerima informasi tersebut dari pihak yang memberikan informasi;</div>
            <div class="indent">b. informasi yang sudah menjadi milik umum (<em>public domain</em>);</div>
            <div class="indent">c. informasi tersebut dikembangkan secara independen oleh pihak ketiga tanpa melanggar ketentuan Perjanjian ini; atau d. informasi diminta untuk dibuka karena persyaratan hukum.</div>
        </td>
    </tr>
</table>

{{-- ══════════ PASAL 5 ══════════ --}}
<div class="pasal-head">
    <div>PASAL 5</div>
    <div>SANKSI-SANKSI DAN PENGAKHIRAN PERJANJIAN</div>
</div>

@php $reset(); @endphp
<table class="ayat">
    <tr>
        <td class="no">{{ $next() }}.</td>
        <td class="isi">
            Apabila <strong>PIHAK KEDUA</strong> membatalkan/memutuskan Perjanjian yang masih berlangsung
            secara sepihak, maka <strong>PIHAK KEDUA</strong> diwajibkan membayar ganti rugi sebesar jumlah
            Honorarium yang telah diterima dan biaya pelaksanaan pekerjaan lainnya atas kerugian yang
            dialami oleh <strong>PIHAK PERTAMA</strong>.
        </td>
    </tr>
    <tr>
        <td class="no">{{ $next() }}.</td>
        <td class="isi">
            Apabila <strong>PIHAK PERTAMA</strong> membatalkan/memutuskan Perjanjian yang masih berlangsung
            secara sepihak, maka <strong>PIHAK PERTAMA</strong> berkewajiban membayarkan ganti rugi kepada
            <strong>PIHAK KEDUA</strong> sesuai dengan <em>progress</em> pekerjaan yang telah dikerjakan
            oleh <strong>PIHAK KEDUA</strong> atas kesepakatan <strong>PARA PIHAK</strong>.
        </td>
    </tr>
    @if ($aktif('napza'))
        <tr>
            <td class="no">{{ $next() }}.</td>
            <td class="isi">{!! $klausul('napza') !!}</td>
        </tr>
    @endif
    <tr>
        <td class="no">{{ $next() }}.</td>
        <td class="isi">
            Apabila selama Jangka Waktu Perjanjian salah satu <strong>PIHAK</strong> melanggar Perjanjian,
            maka <strong>PIHAK</strong> yang dirugikan berhak memberikan teguran baik secara tulisan berupa
            surat peringatan dan/atau teguran secara lisan untuk memperbaiki pelanggaran dalam kurun waktu
            selambat-lambatnya 14 (empat belas) hari kalender ke keadaan semula.
        </td>
    </tr>
    @if ($aktif('denda'))
        <tr>
            <td class="no">{{ $next() }}.</td>
            <td class="isi">{!! $klausul('denda') !!}</td>
        </tr>
    @endif
    <tr>
        <td class="no">{{ $next() }}.</td>
        <td class="isi">
            Apabila terdapat perubahan nama akun media sosial dari setiap <strong>KOL</strong> dan/atau
            berpindah tangan kepada pihak lain, maka <strong>PIHAK KEDUA</strong> wajib memberitahukan
            informasi dan tersebut kepada <strong>PIHAK PERTAMA</strong>, apabila tidak dilakukan maka
            <strong>PIHAK KEDUA</strong> wajib bertanggungjawab atas segala kerugian yang timbul.
        </td>
    </tr>
    <tr>
        <td class="no">{{ $next() }}.</td>
        <td class="isi">
            Perjanjian ini akan berakhir apabila salah satu hal dibawah ini terjadi :
            <div class="indent">a. Berakhirnya Jangka Waktu Perjanjian sebagaimana ditentukan di dalam Pasal 2 Perjanjian ini; atau</div>
            <div class="indent">b. Berdasarkan kesepakatan tertulis <strong>PARA PIHAK</strong> pada setiap saat; atau</div>
            <div class="indent">c. Atas permintaan salah satu <strong>PIHAK</strong>, apabila <strong>PIHAK</strong> lainnya tidak melaksanakan kewajiban-kewajibannya sesuai dengan ketentuan dalam Perjanjian ini dan kewajiban tersebut tetap tidak dipenuhi sejak adanya permintaan pemenuhan kewajiban dari <strong>PIHAK</strong> lainnya tersebut.</div>
        </td>
    </tr>
</table>

{{-- ══════════ PASAL 6 ══════════ --}}
<div class="pasal-head">
    <div>PASAL 6</div>
    <div>HUKUM YANG BERLAKU DAN PENYELESAIAN</div>
    <div>PERMASALAHAN.</div>
</div>

@php $reset(); @endphp
<table class="ayat">
    <tr>
        <td class="no">{{ $next() }}.</td>
        <td class="isi">
            <strong>PARA PIHAK</strong> sepakat bahwa Perjanjian dan pelaksanaanya tunduk dan ditafsirkan
            sesuai dengan ketentuan hukum Negara Republik Indonesia.
        </td>
    </tr>
    <tr>
        <td class="no">{{ $next() }}.</td>
        <td class="isi">
            Apabila terjadi perselisihan dan perbedaan penafsiran dalam pelaksanaan Perjanjian ini, maka
            <strong>PARA PIHAK</strong> sepakat akan menyelesaikan perselisihan tersebut secara
            kekeluargaan melalui musyawarah mufakat.
        </td>
    </tr>
    <tr>
        <td class="no">{{ $next() }}.</td>
        <td class="isi">
            Bilamana terjadi perselisihan dalam hal pelaksanaan Perjanjian ini dan tidak dapat diselesaikan
            dengan jalan musyawarah dalam waktu 30 (tiga puluh) hari sejak timbulnya perselisihan tersebut,
            maka <strong>PARA PIHAK</strong> sepakat memilih tempat kediaman hukum yang tetap dan se umum
            nya di kantor Kepaniteraan Pengadilan Negeri Jakarta Selatan.
        </td>
    </tr>
    <tr>
        <td class="no">{{ $next() }}.</td>
        <td class="isi">
            <strong>PARA PIHAK</strong> Menyetujui selama berlangsungnya kerjasama akan membuat unggahan
            yang tidak melanggar etika dan tidak melanggar ketentuan hukum yang berlaku.
        </td>
    </tr>
</table>

{{-- ══════════ PASAL 7 — hanya muncul bila ada add-on ══════════ --}}
@if ($addons)
    <div class="pasal-head">
        <div>PASAL 7</div>
        <div>KETENTUAN TAMBAHAN</div>
    </div>

    @php $reset(); @endphp
    <table class="ayat">
        @foreach ($addons as $addon)
            <tr>
                <td class="no">{{ $next() }}.</td>
                <td class="isi">
                    @if ($addon['title'] !== '')
                        <strong>{{ $addon['title'] }}.</strong>
                    @endif
                    {{ $addon['text'] }}
                </td>
            </tr>
        @endforeach
    </table>
@endif

<div class="avoid-break">
    <p style="margin-top: 4mm;">
        Demikian Perjanjian ini dibuat atas persetujuan bersama dan ditandatangani oleh Para Pihak pada
        tanggal sebagaimana tercantum pada awal naskah Perjanjian ini, dibuat dalam dua rangkap yang
        masing-masing memiliki kekuatan hukum yang sama.
    </p>

    <table class="ttd">
        <tr>
            <td class="peran">Pihak Pertama</td>
            <td class="peran">Pihak Kedua</td>
        </tr>
        <tr>
            <td class="ttd-area">
                <span class="materai{{ empty($materaiBase64 ?? null) ? ' materai-kosong' : '' }}">
                    @if(!empty($materaiBase64 ?? null))
                        <img src="{{ $materaiBase64 }}" alt="e-Meterai">
                    @else
                        e-Meterai
                    @endif
                </span>
            </td>
            <td class="ttd-area">
                @if(!empty($signatureBase64))
                    <img src="{{ $signatureBase64 }}" alt="Tanda tangan {{ $spk->pihak_kedua_nama_lengkap }}">
                @endif
            </td>
        </tr>
        <tr>
            <td class="nama">{{ $pertama['nama'] }}</td>
            <td class="nama">{{ $spk->pihak_kedua_nama_lengkap ?: '—' }}</td>
        </tr>
    </table>

    @if($spk->signed_at)
        @php
            // translatedFormat ikut app.locale (masih 'en'), jadi pakai peta bulan
            // yang sama dengan tanggal perjanjian di atas supaya konsisten Indonesia.
            $ttd = $spk->signed_at;
            $tanggalTtd = $ttd->day . ' ' . \App\Models\BvSPK::MONTHS_ID[$ttd->month] . ' ' . $ttd->year
                . ', ' . $ttd->format('H:i');
        @endphp
        <p class="esign-note">
            Ditandatangani secara elektronik oleh {{ $spk->pihak_kedua_nama_lengkap }} pada
            {{ $tanggalTtd }} WIB{{ $spk->signed_ip ? ' (IP ' . $spk->signed_ip . ')' : '' }}.
            Dokumen ini sah tanpa tanda tangan basah.
        </p>
    @endif
</div>

</body>
</html>
