{{-- Agenda tetap dari sheet "Meeting Agenda". Statis: isinya sama tiap minggu. --}}
<div style="font-size:.875rem;line-height:1.6">
    <table style="width:100%;border-collapse:collapse">
        <thead>
            <tr>
                <th style="text-align:left;padding:.5rem;border-bottom:1px solid var(--gray-200,#e5e7eb)">Item</th>
                <th style="text-align:right;padding:.5rem;border-bottom:1px solid var(--gray-200,#e5e7eb);width:5rem">Menit</th>
                <th style="text-align:right;padding:.5rem;border-bottom:1px solid var(--gray-200,#e5e7eb);width:6rem">Selesai di</th>
            </tr>
        </thead>
        <tbody>
            @foreach ([
                ['Good News', 5, 5, 'Hal baik apa pun sejak rapat terakhir, soal kerjaan atau pribadi.'],
                ['Client / Employee Headlines', 35, 40, 'Kemenangan sejak rapat terakhir, dan yang tidak berjalan sesuai rencana.'],
                ['Scorecard Review', null, null, 'On track atau tidak terhadap metrik minggu / bulan ini.'],
                ['OKR Review', null, null, 'Perbarui OKR SEBELUM rapat dimulai, bukan saat rapat berjalan.'],
                ['To Do List', null, null, 'Tugas baru dan status tugas yang masih terbuka.'],
                ['Issues To Discuss', 45, 85, 'Daftar dulu semua isunya, beri prioritas, baru dibahas dari yang teratas.'],
                ['Conclude & Rate', 5, 90, 'Nilai rapatnya 1-10 dan catat komentarnya.'],
            ] as [$nama, $menit, $selesai, $keterangan])
                <tr>
                    <td style="padding:.5rem;border-bottom:1px solid var(--gray-100,#f3f4f6)">
                        <div style="font-weight:600">{{ $nama }}</div>
                        <div style="color:var(--gray-500,#6b7280)">{{ $keterangan }}</div>
                    </td>
                    <td style="padding:.5rem;text-align:right;border-bottom:1px solid var(--gray-100,#f3f4f6)">{{ $menit ?? '—' }}</td>
                    <td style="padding:.5rem;text-align:right;border-bottom:1px solid var(--gray-100,#f3f4f6)">{{ $selesai ?? '—' }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <div style="margin-top:1.25rem">
        <div style="font-weight:700;margin-bottom:.375rem">Aturan Rapat</div>
        <ul style="list-style:disc;padding-left:1.25rem">
            <li>Jangan telat. Semua sudah di ruangan 5 menit sebelum mulai.</li>
            <li>Datang siap. Update diisi sebelum rapat, bukan sambil rapat berjalan.</li>
            <li>Patuhi alokasi waktunya.</li>
            <li>Isu dibahas di sesi Issues. Kalau muncul di sesi lain, dicatat dulu ke daftar isu.</li>
        </ul>
    </div>
</div>
