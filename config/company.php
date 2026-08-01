<?php

/**
 * Identitas perusahaan. Satu sumber untuk kop kontrak SPK, halaman quotation
 * publik, dan dokumen lain — ubah di sini, jangan di blade masing-masing.
 *
 * ponytail: config, bukan tabel master — cuma ada satu perusahaan.
 */
return [
    'name' => 'PT. BISA VIRAL BUTUH USAHA',
    'brand' => 'BV Network Indonesia',
    'email' => 'hello@beyondviral.id',

    // Satu baris utuh, untuk paragraf badan kontrak.
    'address' => 'Enablerspace.id Jl. Bintaro Raya Selatan No. 8, RT. 002/RW. 010, Kel. Kby. Lama Utara, Kec. Kebayoran Lama, Kota Jakarta Selatan. Daerah Khusus Ibukota Jakarta 12240',

    // Versi terpecah, untuk kop surat & header halaman yang perlu ganti baris
    // di tempat tertentu. Sengaja bukan hasil wrap otomatis: titik potongnya
    // menentukan tinggi kop PDF yang sudah pas 25mm.
    'address_lines' => [
        'Jl. Bintaro Raya Selatan No.8, RT.2/RW.10, Kby. Lama Utara, Kec.',
        'Kebayoran Lama. Kota Jakarta Selatan. Daerah Khusus Ibukota Jakarta 12240',
    ],

    // Nama situs yang tercetak sebagai tautan hijau di kop.
    'site' => 'Enablerspace.id',

    // Penandatangan PIHAK PERTAMA di SPK.
    'signer' => [
        'nama' => 'Gerry Hutomo',
        'jabatan' => 'Head of Operation',
    ],
];
