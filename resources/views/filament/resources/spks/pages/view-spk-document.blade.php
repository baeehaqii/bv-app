<x-filament-panels::page>
    @php
        $record = $this->getRecord();

        $tanggalPerjanjian = $record->tanggal_perjanjian
            ? $record->tanggal_perjanjian->translatedFormat('d F Y')
            : '[Tanggal]';

        $nominal = $record->nominal_kesepakatan
            ? 'Rp. ' . number_format((float) $record->nominal_kesepakatan, 0, ',', '.')
            : 'Rp. [Nominal Kesepakatan]';

        $terbilang = $record->nominal_terbilang ?: '[Terbilang]';
        $namaPihakKedua = $record->pihak_kedua_nama_lengkap ?: '[Nama Lengkap]';
        $namaAkun = $record->pihak_kedua_nama_akun ?: '[Nama Akun]';
        $nik = $record->pihak_kedua_nik ?: '[NIK]';
        $alamat = $record->pihak_kedua_alamat ?: '[Alamat]';
        $namaCampaign = $record->nama_campaign ?: '[Nama Campaign]';
        $sow = $record->sow_disepakati ?: 'SOW yang disepakati';
        $timeline = $record->timeline_kerja_sama ?: 'Timeline : Merupakan Bulan kerja sama';
    @endphp

    <style>
        /* Sembunyikan elemen Filament yang tidak diperlukan saat print */
        .fi-page-header,
        .fi-section,
        .fi-section-content {
            display: none !important;
        }

        /* Konfigurasi Ukuran Kertas A4 */
        @page {
            size: A4;
            margin: 20mm;
        }

        .spk-container {
            max-width: 210mm;
            min-height: 297mm;
            margin: 0 auto;
            background: #fff;
            color: #000;
            font-family: "Times New Roman", Times, serif;
            font-size: 11pt;
            line-height: 1.5;
            padding: 10mm;
            box-sizing: border-box;
        }

        /* Header Document */
        .spk-header {
            display: flex;
            align-items: center;
            border-bottom: 2px solid #000;
            padding-bottom: 10px;
            margin-bottom: 20px;
        }

        .spk-logo {
            width: 100px;
            height: auto;
            margin-right: 20px;
        }

        .spk-company-info {
            flex: 1;
            text-align: center;
            font-family: Arial, sans-serif;
        }

        .spk-company-info h1 {
            margin: 0;
            font-size: 18pt;
            font-weight: bold;
        }

        .spk-company-info h2 {
            margin: 5px 0 0;
            font-size: 10pt;
            font-weight: normal;
        }

        /* Typography & Spacing */
        .text-center { text-align: center; }
        .text-justify { text-align: justify; }
        .font-bold { font-weight: bold; }
        .mt-4 { margin-top: 1rem; }
        .mt-8 { margin-top: 2rem; }
        .mb-2 { margin-bottom: 0.5rem; }
        .mb-4 { margin-bottom: 1rem; }
        
        .spk-title {
            font-size: 14pt;
            font-weight: bold;
            text-align: center;
            text-decoration: underline;
            margin-bottom: 5px;
        }

        .spk-subtitle {
            font-size: 12pt;
            font-weight: bold;
            text-align: center;
            margin-bottom: 20px;
        }

        /* Identitas Para Pihak */
        .identitas-table {
            width: 100%;
            margin-bottom: 15px;
            margin-left: 20px;
        }

        .identitas-table td {
            vertical-align: top;
            padding: 2px 0;
        }

        .identitas-table td:first-child {
            width: 150px;
        }

        .identitas-table td:nth-child(2) {
            width: 20px;
            text-align: center;
        }

        /* Styling Pasal */
        .pasal-title {
            text-align: center;
            font-weight: bold;
            margin-top: 25px;
            margin-bottom: 5px;
        }

        .pasal-subtitle {
            text-align: center;
            font-weight: bold;
            margin-top: 0;
            margin-bottom: 15px;
            line-height: 1.3;
        }

        /* Signature Section */
        .signature-table {
            width: 100%;
            margin-top: 40px;
            text-align: center;
        }

        .signature-table td {
            width: 50%;
            padding: 10px;
            vertical-align: bottom;
        }

        .signature-space {
            height: 100px;
        }

        /* Print Utilities */
        @media print {
            .no-print { display: none !important; }
            .spk-container {
                box-shadow: none;
                margin: 0;
                padding: 0;
                max-width: 100%;
            }
            .page-break { page-break-before: always; }
        }
    </style>

    <div class="no-print mb-4">
        <x-filament::button color="gray" icon="heroicon-o-printer" onclick="window.print()">
            Cetak Dokumen
        </x-filament::button>
    </div>

    <div class="spk-container">
        <div class="spk-header">
            <img src="{{ asset('images/logo_bv.png') }}" alt="Logo BV" class="spk-logo">
            <div class="spk-company-info">
                <h1>PT. BISA VIRAL BUTUH USAHA</h1>
                <h2>Beyond Viral Indonesia<br>Pondok Labu Green Garden, Nomor 3. Jl. Cilobak, Pangkalan Jati,<br>Kecamatan Cinere, Kota Depok, Jawa Barat, 16514</h2>
            </div>
        </div>

        <div class="spk-title">SURAT PERJANJIAN KERJA SAMA</div>
        <div class="spk-subtitle">PT. BISA VIRAL BUTUH USAHA</div>

        <p class="text-justify mb-4">
            Perjanjian Kerja Sama Beyond Viral (selanjutnya disebut "Perjanjian") dilangsungkan dan ditandatangani di Jakarta pada tanggal {{ $tanggalPerjanjian }}
        </p>

        <p class="text-justify mb-2">
            Dalam hal ini bertindak untuk dan atas nama PT. BISA VIRAL BUTUH USAHA dalam hal ini diwakili oleh Syelinda Pratiwi Head of Operations dari dan oleh karenanya sah bertindak untuk dan atas nama serta mewakili kepentingan PT. BISA VIRAL BUTUH USAHA sebagai <span class="font-bold">"PIHAK PERTAMA"</span>.
        </p>

        <table class="identitas-table">
            <tr>
                <td>Nama</td>
                <td>:</td>
                <td>Syelinda Pratiwi</td>
            </tr>
            <tr>
                <td>Jabatan</td>
                <td>:</td>
                <td>Head of Operation</td>
            </tr>
            <tr>
                <td>Alamat</td>
                <td>:</td>
                <td>Pondok Labu Green Garden, Nomor 3. Jl. Cilobak, Pangkalan Jati, Kecamatan Cinere, Kota Depok, Jawa Barat, 16514</td>
            </tr>
        </table>

        <p class="text-center font-bold mb-2">Dan</p>

        <p class="text-justify mb-2">
            Dalam hal ini bertindak untuk dan atas nama pribadi (selanjutnya disebut <span class="font-bold">"PIHAK KEDUA"</span>).
        </p>

        <table class="identitas-table">
            <tr>
                <td>Nama Lengkap</td>
                <td>:</td>
                <td>{{ $namaPihakKedua }}</td>
            </tr>
            <tr>
                <td>Nama Akun</td>
                <td>:</td>
                <td>{{ $namaAkun }}</td>
            </tr>
            <tr>
                <td>NIK</td>
                <td>:</td>
                <td>{{ $nik }}</td>
            </tr>
            <tr>
                <td>Alamat</td>
                <td>:</td>
                <td>{{ $alamat }}</td>
            </tr>
        </table>

        <p class="text-justify mt-4">
            <span class="font-bold">PARA PIHAK</span> telah sepakat untuk bekerja sama dan mengikatkan diri dalam Perjanjian ini dengan syarat-syarat dan ketentuan-ketentuan sebagai berikut:
        </p>

        <div class="pasal-title">PASAL 1</div>
        <div class="pasal-subtitle">MAKSUD DAN TUJUAN</div>
        <ol start="1" style="list-style-type: decimal !important; padding-left: 25px; margin-bottom: 15px; text-align: justify;">
            <li style="margin-bottom: 8px; padding-left: 5px;">Perjanjian ini dimaksudkan agar <span class="font-bold">PIHAK PERTAMA</span> dapat bekerja sama dengan <span class="font-bold">PIHAK KEDUA</span> yang sesuai dengan permintaan Pihak Pertama atau di tempat yang ditentukan oleh <span class="font-bold">PIHAK PERTAMA</span>.</li>
            <li style="margin-bottom: 8px; padding-left: 5px;">Tujuan dari Perjanjian ini adalah untuk menjamin kelangsungan <em>{{ $namaCampaign }}</em> yang dilakukan oleh Klien <span class="font-bold">PIHAK PERTAMA</span> dan ketersediaan tenaga kerja dalam mendukung pencapaian kinerja dan usaha <span class="font-bold">PIHAK PERTAMA</span>.</li>
            <li style="margin-bottom: 8px; padding-left: 5px;">Pihak Kedua berkewajiban menjalankan tugas dan tanggung jawab utama sesuai kesepakatan dengan rincian kesepakatan sebagai berikut:
                <ul style="list-style-type: none; padding-left: 20px; margin-top: 5px; margin-bottom: 5px;">
                    <li style="position: relative; margin-bottom: 5px;"><span style="position: absolute; left: -15px;">-</span> {{ $sow }}</li>
                    <li style="position: relative; margin-bottom: 5px;"><span style="position: absolute; left: -15px;">-</span> {{ $timeline }}</li>
                </ul>
            </li>
            <li style="margin-bottom: 8px; padding-left: 5px;">Video promosi yang disebutkan dalam Ayat (3) Pasal ini akan diunggah pada akun yang sudah disebutkan sebagai Pihak Kedua dan tidak akan dihapus paling tidak selama 1 (satu) bulan sejak video promosi tersebut diunggah pada masing-masing akun.</li>
            <li style="margin-bottom: 8px; padding-left: 5px;"><span class="font-bold">PIHAK KEDUA</span> wajib mengikuti segala arahan/brief yang telah di kirim oleh <span class="font-bold">PIHAK PERTAMA</span></li>
            <li style="margin-bottom: 8px; padding-left: 5px;"><span class="font-bold">PIHAK KEDUA</span> wajib mengikuti apabila konten yang telah dibuat tidak sesuai dengan brief yang telah di kirim oleh <span class="font-bold">PIHAK PERTAMA</span></li>
            <li style="margin-bottom: 8px; padding-left: 5px;"><span class="font-bold">PIHAK KEDUA</span> wajib memberikan insight 14 Hari setelah postingan dari konten yang telah di unggah sebagaimana tanggung jawab sesuai kesepakatan oleh <span class="font-bold">PIHAK PERTAMA</span>.</li>
        </ol>

        <div class="pasal-title">PASAL 2</div>
        <div class="pasal-subtitle">JANGKA WAKTU<br>PERJANJIAN</div>
        <ol start="1" style="list-style-type: decimal !important; padding-left: 25px; margin-bottom: 15px; text-align: justify;">
            <li style="margin-bottom: 8px; padding-left: 5px;">Perjanjian ini berlaku efektif sejak ditandatangani oleh <span class="font-bold">PARA PIHAK</span> dan akan berakhir setelah Pekerjaan dan kewajiban-kewajiban diselesaikan seluruhnya, serta hak-hak diperoleh oleh <span class="font-bold">PARA PIHAK</span> (selanjutnya disebut sebagai "<span class="font-bold">Jangka Waktu Perjanjian</span>").</li>
            <li style="margin-bottom: 8px; padding-left: 5px;"><span class="font-bold">PARA PIHAK</span> sepakat apabila terdapat perubahan dalam Jangka Waktu Perjanjian, maka akan diatur dalam Addendum Perjanjian yang disepakati dan ditandatangani oleh <span class="font-bold">PARA PIHAK</span> di kemudian hari.</li>
            <li style="margin-bottom: 8px; padding-left: 5px;"><span class="font-bold">PIHAK KEDUA</span> sepakat tidak akan bekerja sama dan mempromosikan kompetitor dari <span class="font-bold">PIHAK PERTAMA</span> pada seluruh media sosial selama Jangka Waktu Perjanjian ini berlangsung.</li>
        </ol>

        <div class="pasal-title">PASAL 3</div>
        <div class="pasal-subtitle">PEMBAYARAN</div>
        <ol start="1" style="list-style-type: decimal !important; padding-left: 25px; margin-bottom: 15px; text-align: justify;">
            <li style="margin-bottom: 8px; padding-left: 5px;">Perjanjian mengenai harga kesepakatan bersifat pribadi dan rahasia. <span class="font-bold">PIHAK PERTAMA</span> dan <span class="font-bold">PIHAK KEDUA</span> tidak diperbolehkan membicarakan hal ini kepada pihak yang tidak bersangkutan.</li>
            <li style="margin-bottom: 8px; padding-left: 5px;"><span class="font-bold">PIHAK KEDUA</span> dapat menjalankan tugas-tugas dan tanggung jawab lainnya sesuai dengan Pasal 1 yang diberikan oleh <span class="font-bold">PIHAK PERTAMA</span> sesuai kebutuhan yang disepakati adalah sebesar <span class="font-bold">{{ $nominal }} ({{ $terbilang }})</span> diluar pajak.</li>
            <li style="margin-bottom: 8px; padding-left: 5px;">Menyetujui pembayaran Imbal Jasa Kerja Sama akan ditransfer ke rekening:
                <table style="margin-top: 10px; margin-bottom: 10px; width: 100%;">
                    <tr>
                        <td style="width: 150px; padding: 2px 0;">Atas Nama</td>
                        <td style="width: 20px; padding: 2px 0;">:</td>
                        <td style="padding: 2px 0;">{{ $record->atas_nama_rekening ?: '' }}</td>
                    </tr>
                    <tr>
                        <td style="padding: 2px 0;">Nomor Rekening</td>
                        <td style="padding: 2px 0;">:</td>
                        <td style="padding: 2px 0;">{{ $record->nomor_rekening ?: '' }}</td>
                    </tr>
                    <tr>
                        <td style="padding: 2px 0;">Bank</td>
                        <td style="padding: 2px 0;">:</td>
                        <td style="padding: 2px 0;">{{ $record->nama_bank ?: '' }}</td>
                    </tr>
                    <tr>
                        <td style="padding: 2px 0;">Kantor Cabang</td>
                        <td style="padding: 2px 0;">:</td>
                        <td style="padding: 2px 0;">{{ $record->kantor_cabang_bank ?: '' }}</td>
                    </tr>
                </table>
            </li>
        </ol>

        <p class="text-justify" style="margin-left: 10px; margin-bottom: 10px;">3. Waktu dan cara pembayaran seperti terlampir :</p>
        <ol type="a" style="list-style-type: lower-alpha !important; padding-left: 45px; margin-bottom: 15px;">
            <li style="margin-bottom: 5px; padding-left: 5px;">Termin Pembayaran 1 : {{ $record->termin_pembayaran_1 ?: '' }}</li>
            <li style="margin-bottom: 5px; padding-left: 5px;">Termin Pembayaran 2 : {{ $record->termin_pembayaran_2 ?: '' }}</li>
        </ol>

        <p class="text-justify mb-4">
            Menyetujui segala bentuk dan aspek perpajakan yang timbul akibat dari pelaksanaan campaign ini dilaksanakan sesuai dengan peraturan perpajakan yang berlaku di Indonesia.
        </p>

        <div class="page-break"></div>

        <div class="pasal-title">PASAL 6</div>
        <div class="pasal-subtitle">SANKSI-SANKSI DAN PENGAKHIRAN PERJANJIAN</div>
        <ol start="1" style="list-style-type: decimal !important; padding-left: 25px; margin-bottom: 15px; text-align: justify;">
            <li style="margin-bottom: 8px; padding-left: 5px;"><span class="font-bold">PARA PIHAK</span> setuju untuk tidak memberitahukan kepada pihak manapun juga atas seluruh informasi baik lisan maupun tertulis dan/atau data, dokumen yang diperoleh dari pelaksanaan Perjanjian ini, termasuk tetapi tidak terbatas pada besarnya Honorarium yang diterima <span class="font-bold">PIHAK KEDUA</span>, baik selama Perjanjian ini berlangsung maupun setelah berakhir.</li>
            <li style="margin-bottom: 8px; padding-left: 5px;"><span class="font-bold">PARA PIHAK</span> sepakat bahwa seluruh isi Perjanjian ini harus diperlakukan secara rahasia sehingga tidak ada satu pun informasi sehubungan dengan Perjanjian ini dapat diberitahukan oleh salah satu <span class="font-bold">PIHAK</span> kepada pihak ketiga tanpa lebih dahulu mendapat izin tertulis dari <span class="font-bold">PIHAK</span> lainnya, kecuali yang merupakan keharusan dalam rangka pelaksanaan Perjanjian ini atau disyaratkan oleh peraturan yang berlaku.</li>
            <li style="margin-bottom: 8px; padding-left: 5px;">Ketentuan Pasal ini tidak berlaku terhadap :<br>
                a. informasi yang sudah dimiliki atau sebelumnya telah diketahui oleh pihak penerima informasi pada saat pihak tersebut menerima informasi tersebut dari pihak yang memberikan informasi;<br>
                b. informasi yang sudah menjadi milik umum (<em>public domain</em>);<br>
                c. informasi tersebut dikembangkan secara independen oleh pihak ketiga tanpa melanggar ketentuan Perjanjian ini; atau d. informasi diminta untuk dibuka karena persyaratan hukum.
            </li>
        </ol>

        <div class="pasal-title">PASAL 7</div>
        <div class="pasal-subtitle">SANKSI-SANKSI DAN PENGAKHIRAN PERJANJIAN</div>
        <ol start="4" style="list-style-type: decimal !important; padding-left: 25px; margin-bottom: 15px; text-align: justify;">
            <li style="margin-bottom: 8px; padding-left: 5px;">Apabila <span class="font-bold">PIHAK KEDUA</span> membatalkan/memutuskan Perjanjian yang masih berlangsung secara sepihak, maka <span class="font-bold">PIHAK KEDUA</span> diwajibkan membayar ganti rugi sebesar jumlah Honorarium yang telah diterima dan biaya pelaksanaan pekerjaan lainnya atas kerugian yang dialami oleh <span class="font-bold">PIHAK PERTAMA</span>.</li>
            <li style="margin-bottom: 8px; padding-left: 5px;">Apabila <span class="font-bold">PIHAK PERTAMA</span> membatalkan/memutuskan Perjanjian yang masih berlangsung secara sepihak, maka <span class="font-bold">PIHAK PERTAMA</span> berkewajiban membayarkan ganti rugi kepada <span class="font-bold">PIHAK KEDUA</span> sesuai dengan <em>progress</em> pekerjaan yang telah dikerjakan oleh <span class="font-bold">PIHAK KEDUA</span> atas kesepakatan <span class="font-bold">PARA PIHAK</span>.</li>
            <li style="margin-bottom: 8px; padding-left: 5px;">Apabila selama berlakunya Perjanjian ini, <span class="font-bold">PIHAK KEDUA</span> terbukti secara sah menggunakan, mengedarkan dan/ atau menyalahgunakan NAPZA dikuatkan dengan adanya putusan pengadilan, maka <span class="font-bold">PIHAK KEDUA</span> diwajibkan membayar ganti rugi dengan mengembalikan secara penuh seluruh uang yang telah diterima oleh <span class="font-bold">PIHAK KEDUA</span> kepada <span class="font-bold">PIHAK PERTAMA</span> termasuk biaya pelaksanaan pekerjaannya.</li>
            <li style="margin-bottom: 8px; padding-left: 5px;">Apabila selama Jangka Waktu Perjanjian salah satu <span class="font-bold">PIHAK</span> melanggar Perjanjian, maka <span class="font-bold">PIHAK</span> yang dirugikan berhak memberikan teguran baik secara tulisan berupa surat peringatan dan/atau teguran secara lisan untuk memperbaiki pelanggaran dalam kurun waktu selambat-lambatnya 14 (empat belas) hari kalender ke keadaan semula.</li>
            <li style="margin-bottom: 8px; padding-left: 5px;">Apabila <span class="font-bold">PIHAK KEDUA</span> tidak melaksanakan Pekerjaan pada Pasal 1 Perjanjian ini sesuai dengan yang telah disepakati oleh <span class="font-bold">PARA PIHAK</span> yang diakibatkan oleh kelalaian dan/atau kesalahan dari <span class="font-bold">PIHAK KEDUA</span>, maka <span class="font-bold">PIHAK PERTAMA</span> berhak memberikan sanksi berupa teguran secara tertulis kepada <span class="font-bold">PIHAK KEDUA</span>, dan apabila paling lambat 7 (tujuh) hari kalender setelah teguran tersebut tidak ada itikad baik dari <span class="font-bold">PIHAK KEDUA</span>, maka <span class="font-bold">PIHAK KEDUA</span> wajib mengembalikan kepada <span class="font-bold">PIHAK PERTAMA</span> sejumlah uang yang telah diterima paling lambat dalam 14 (empat belas) hari kalender dan dikenakan denda sebesar 1‰ (satu permil) perhari dari total uang yang telah diterima oleh <span class="font-bold">PIHAK KEDUA</span>. Apabila setelah 30 (tiga puluh) hari kalender terhitung sejak surat teguran diterima dan tidak ditindaklanjuti dengan itikad baik dari <span class="font-bold">PIHAK KEDUA</span>, maka <span class="font-bold">PIHAK PERTAMA</span> berhak untuk melakukan pengakhiran Perjanjian ini dengan menerima sejumlah uang yang telah dibayarkan kepada <span class="font-bold">PIHAK KEDUA</span> dan denda sebagaimana dimaksud pada Ayat ini.</li>
            <li style="margin-bottom: 8px; padding-left: 5px;">Apabila terdapat perubahan nama akun media sosial dari setiap <span class="font-bold">KOL</span> dan/atau berpindah tangan kepada pihak lain, maka <span class="font-bold">PIHAK KEDUA</span> wajib memberitahukan informasi dan tersebut kepada <span class="font-bold">PIHAK PERTAMA</span>, apabila tidak dilakukan maka <span class="font-bold">PIHAK KEDUA</span> wajib bertanggungjawab atas segala kerugian yang timbul.</li>
            <li style="margin-bottom: 8px; padding-left: 5px;">Perjanjian ini akan berakhir apabila salah satu hal dibawah ini terjadi :<br>
                a. Berakhirnya Jangka Waktu Perjanjian sebagaimana ditentukan di dalam Pasal 2 Perjanjian ini; atau<br>
                b. Berdasarkan kesepakatan tertulis <span class="font-bold">PARA PIHAK</span> pada setiap saat; atau<br>
                c. Atas permintaan salah satu <span class="font-bold">PIHAK</span>, apabila <span class="font-bold">PIHAK</span> lainnya tidak melaksanakan kewajiban-kewajibannya sesuai dengan ketentuan dalam Perjanjian ini dan kewajiban tersebut tetap tidak dipenuhi sejak adanya permintaan pemenuhan kewajiban dari <span class="font-bold">PIHAK</span> lainnya tersebut.
            </li>
        </ol>

        <div class="pasal-title">PASAL 8</div>
        <div class="pasal-subtitle">HUKUM YANG BERLAKU DAN PENYELESAIAN<br>PERMASALAHAN.</div>
        <ol start="11" style="list-style-type: decimal !important; padding-left: 25px; margin-bottom: 15px; text-align: justify;">
            <li style="margin-bottom: 8px; padding-left: 5px;"><span class="font-bold">PARA PIHAK</span> sepakat bahwa Perjanjian dan pelaksanaanya tunduk dan ditafsirkan sesuai dengan ketentuan hukum Negara Republik Indonesia.</li>
            <li style="margin-bottom: 8px; padding-left: 5px;">Apabila terjadi perselisihan dan perbedaan penafsiran dalam pelaksanaan Perjanjian ini, maka <span class="font-bold">PARA PIHAK</span> sepakat akan menyelesaikan perselisihan tersebut secara kekeluargaan melalui musyawarah mufakat.</li>
            <li style="margin-bottom: 8px; padding-left: 5px;">Bilamana terjadi perselisihan dalam hal pelaksanaan Perjanjian ini dan tidak dapat diselesaikan dengan jalan musyawarah dalam waktu 30 (tiga puluh) hari sejak timbulnya perselisihan tersebut, maka <span class="font-bold">PARA PIHAK</span> sepakat memilih tempat kediaman hukum yang tetap dan se umum nya di kantor Kepaniteraan Pengadilan Negeri Jakarta Selatan.</li>
            <li style="margin-bottom: 8px; padding-left: 5px;"><span class="font-bold">PARA PIHAK</span> Menyetujui selama berlangsungnya kerjasama akan membuat unggahan yang tidak melanggar etika dan tidak melanggar ketentuan hukum yang berlaku.</li>
        </ol>

        <p class="text-justify mt-8">
            Demikian Perjanjian ini dibuat atas persetujuan bersama dan ditandatangani oleh Para Pihak pada tanggal sebagaimana tercantum pada awal naskah Perjanjian ini, dibuat dalam dua rangkap yang masing-masing memiliki kekuatan hukum yang sama.
        </p>

        <table class="signature-table">
            <tr>
                <td class="font-bold">Pihak Pertama</td>
                <td class="font-bold">Pihak Kedua</td>
            </tr>
            <tr>
                <td class="signature-space"></td>
                <td class="signature-space"></td>
            </tr>
            <tr>
                <td class="font-bold">Syelinda Pratiwi</td>
                <td class="font-bold">({{ $namaPihakKedua }})</td>
            </tr>
        </table>
    </div>
</x-filament-panels::page>