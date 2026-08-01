<?php

use App\Http\Controllers\KolContractController;
use App\Models\BvSPK;
use App\Models\DataKol;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function spkDenganKlausul(array $clauses = [], array $addons = [], string $nomor = 'BVN/SPK/2026/08/001'): BvSPK
{
    return BvSPK::create([
        'spk_number' => $nomor,
        'tanggal_perjanjian' => '2026-08-01',
        'pihak_kedua_nama_lengkap' => 'M. Farhan Fava Rizky',
        'pihak_kedua_nama_akun' => 'justeenff (TikTok)',
        'nama_campaign' => 'GoPay Gamers',
        'sow_disepakati' => "1x TikTok Video",
        'nominal_kesepakatan' => 516_000,
        'nominal_terbilang' => BvSPK::terbilang(516_000),
        'clauses' => $clauses ?: BvSPK::defaultClauses(),
        'addons' => $addons,
        'status' => 'draft',
    ]);
}

function renderSpk(BvSPK $spk): string
{
    return view('pdf.kol-contract', KolContractController::prepareData($spk))->render();
}

/**
 * Nomor ayat per pasal, dipecah berdasarkan heading PASAL.
 * Jangan pecah per <table class="ayat">: tabel rekening ter-nest di dalam ayat
 * Pasal 3, jadi regex non-greedy akan berhenti di </table> yang salah.
 *
 * @return array<string, array<int, int>>
 */
function nomorAyatPerPasal(string $html): array
{
    $bagian = preg_split('/<div>(PASAL \d+)<\/div>/', $html, -1, PREG_SPLIT_DELIM_CAPTURE);
    $hasil = [];

    for ($i = 1; $i < count($bagian); $i += 2) {
        preg_match_all('/<td class="no">(\d+)\./', $bagian[$i + 1] ?? '', $m);
        $hasil[$bagian[$i]] = array_map('intval', $m[1]);
    }

    return $hasil;
}

it('mencetak semua klausul saat semuanya aktif', function () {
    $html = renderSpk(spkDenganKlausul());

    foreach (BvSPK::CLAUSES as $key => $c) {
        // Bandingkan potongan awal teks — cukup untuk membuktikan ayatnya tercetak.
        expect($html)->toContain(substr(strip_tags($c['text']), 0, 60));
    }
});

it('menghilangkan ayat yang klausulnya dimatikan', function () {
    $clauses = BvSPK::defaultClauses();
    $clauses['eksklusivitas']['enabled'] = false;
    $clauses['denda']['enabled'] = false;

    $html = renderSpk(spkDenganKlausul($clauses));

    expect($html)
        ->not->toContain('mempromosikan kompetitor')
        ->not->toContain('satu permil')
        // yang lain tetap ada
        ->toContain('menyalahgunakan NAPZA');
});

it('memakai redaksi kustom BV bila teksnya diisi', function () {
    $clauses = BvSPK::defaultClauses();
    $clauses['eksklusivitas']['text'] = 'PIHAK KEDUA dilarang bekerja sama dengan brand mi instan mana pun selama 90 hari.';

    $html = renderSpk(spkDenganKlausul($clauses));

    expect($html)
        ->toContain('brand mi instan mana pun selama 90 hari')
        ->not->toContain('mempromosikan kompetitor');
});

it('kembali ke redaksi bawaan bila teks kustomnya dikosongkan', function () {
    $clauses = BvSPK::defaultClauses();
    $clauses['eksklusivitas']['text'] = '   ';

    expect(spkDenganKlausul($clauses)->clauseText('eksklusivitas'))
        ->toBe(BvSPK::CLAUSES['eksklusivitas']['text']);
});

it('membuang tag HTML berbahaya dari teks klausul', function () {
    $clauses = BvSPK::defaultClauses();
    $clauses['eksklusivitas']['text'] = 'Aman <strong>tebal</strong> <script>alert(1)</script><img src=x onerror=alert(1)>';

    $html = renderSpk(spkDenganKlausul($clauses));

    expect($html)
        ->toContain('<strong>tebal</strong>')   // penekanan tetap boleh
        ->not->toContain('<script>')
        ->not->toContain('onerror');
});

it('menomori pasal berurutan dan mengulang ayat dari 1 di tiap pasal', function () {
    $html = renderSpk(spkDenganKlausul());

    // Draft asli melompat 3 → 6 dan meneruskan nomor ayat lintas pasal; dirapatkan.
    expect(nomorAyatPerPasal($html))->toBe([
        'PASAL 1' => [1, 2, 3, 4, 5, 6, 7],
        'PASAL 2' => [1, 2, 3],
        'PASAL 3' => [1, 2, 3, 4],
        'PASAL 4' => [1, 2, 3],
        'PASAL 5' => [1, 2, 3, 4, 5, 6, 7],
        'PASAL 6' => [1, 2, 3, 4],
    ]);
});

it('merapatkan nomor ayat tanpa meninggalkan lompatan saat klausul dimatikan', function () {
    $clauses = BvSPK::defaultClauses();
    $clauses['konten_tidak_dihapus']['enabled'] = false;  // Pasal 1
    $clauses['napza']['enabled'] = false;                 // Pasal 5

    $html = renderSpk(spkDenganKlausul($clauses));

    $urutan = nomorAyatPerPasal($html);

    expect($urutan['PASAL 1'])->toBe([1, 2, 3, 4, 5, 6])      // kehilangan 1 ayat
        ->and($urutan['PASAL 5'])->toBe([1, 2, 3, 4, 5, 6]);  // rapat, tidak bolong

    // Tidak ada nomor yang melompat di pasal mana pun.
    foreach ($urutan as $pasal => $nomor) {
        expect($nomor)->toBe(range($nomor[0], $nomor[0] + count($nomor) - 1), $pasal);
    }
});

it('mengaitkan klausul pajak ke frasa nominal dan paragraf perpajakan sekaligus', function () {
    expect(renderSpk(spkDenganKlausul()))
        ->toContain('di luar pajak')
        ->toContain('peraturan perpajakan yang berlaku di Indonesia');

    $clauses = BvSPK::defaultClauses();
    $clauses['pajak']['enabled'] = false;

    expect(renderSpk(spkDenganKlausul($clauses, [], 'BVN/SPK/2026/08/002')))
        ->not->toContain('di luar pajak')
        ->not->toContain('peraturan perpajakan yang berlaku di Indonesia');
});

it('tidak mencetak Pasal 7 bila tidak ada add on', function () {
    expect(renderSpk(spkDenganKlausul()))->not->toContain('KETENTUAN TAMBAHAN');
});

it('mencetak add on sebagai Pasal 7 dan mengabaikan baris kosong', function () {
    $spk = spkDenganKlausul([], [
        ['title' => 'Hak Pakai Konten', 'text' => 'PIHAK PERTAMA berhak memakai konten untuk iklan berbayar selama 6 bulan.'],
        ['title' => '', 'text' => ''],                       // baris kosong repeater
        ['title' => 'Bonus Story', 'text' => '1x Instagram Story tambahan.'],
    ]);

    expect($spk->activeAddons())->toHaveCount(2);

    $html = renderSpk($spk);

    expect($html)
        ->toContain('KETENTUAN TAMBAHAN')
        ->toContain('Hak Pakai Konten')
        ->toContain('Bonus Story');

    // Pasal 7 bernomor 1..2, bukan meneruskan nomor Pasal 6.
    preg_match('/KETENTUAN TAMBAHAN(.*)$/s', $html, $m);
    preg_match_all('/<td class="no">(\d+)\./', $m[1], $nomor);
    expect(array_map('intval', $nomor[1]))->toBe([1, 2]);
});

it('mengaktifkan semua klausul untuk SPK yang dibuat dari KOL approved', function () {
    expect(BvSPK::defaultClauses())->toHaveCount(count(BvSPK::CLAUSES));

    foreach (BvSPK::defaultClauses() as $key => $c) {
        expect($c['enabled'])->toBeTrue()
            ->and($c['text'])->toBe(BvSPK::CLAUSES[$key]['text']);
    }
});

it('memperlakukan SPK lama tanpa kolom clauses sebagai semua klausul aktif', function () {
    $spk = spkDenganKlausul();
    $spk->forceFill(['clauses' => null])->save();

    foreach (array_keys(BvSPK::CLAUSES) as $key) {
        expect($spk->fresh()->clauseEnabled($key))->toBeTrue();
    }
});

it('mengubah map klausul ke list Repeater dan kembali tanpa kehilangan data', function () {
    $map = BvSPK::defaultClauses();

    $list = BvSPK::clausesToForm($map);

    expect($list)->toHaveCount(count(BvSPK::CLAUSES))
        // Urutan baris mengikuti CLAUSES, bukan urutan acak dari JSON.
        ->and(array_column($list, 'key'))->toBe(array_keys(BvSPK::CLAUSES))
        ->and(BvSPK::clausesFromForm($list))->toBe($map);
});

it('melengkapi baris klausul walau SPK lama hanya menyimpan sebagian kunci', function () {
    $list = BvSPK::clausesToForm(['eksklusivitas' => ['enabled' => false, 'text' => 'Kustom']]);

    expect($list)->toHaveCount(count(BvSPK::CLAUSES));

    $eks = collect($list)->firstWhere('key', 'eksklusivitas');
    expect($eks['enabled'])->toBeFalse()
        ->and($eks['text'])->toBe('Kustom');

    // Kunci yang tidak tersimpan ikut default: aktif dengan redaksi bawaan.
    $denda = collect($list)->firstWhere('key', 'denda');
    expect($denda['enabled'])->toBeTrue()
        ->and($denda['text'])->toBe(BvSPK::CLAUSES['denda']['text']);
});

it('membuang baris klausul berkunci asing atau kosong dari Repeater', function () {
    $rows = [
        'uuid-a' => ['key' => 'denda', 'enabled' => true, 'text' => 'Tetap'],
        'uuid-b' => ['key' => 'klausul_palsu', 'enabled' => true, 'text' => 'Suntikan'],
        'uuid-c' => ['enabled' => true, 'text' => 'Tanpa kunci'],
    ];

    $map = BvSPK::clausesFromForm($rows);

    expect(array_keys($map))->toBe(['denda'])
        ->and($map['denda']['text'])->toBe('Tetap');
});

it('setiap klausul di CLAUSES benar-benar dipakai di blade', function () {
    $blade = file_get_contents(resource_path('views/pdf/kol-contract.blade.php'));

    foreach (array_keys(BvSPK::CLAUSES) as $key) {
        $this->assertStringContainsString("'{$key}'", $blade,
            "Klausul '{$key}' terdaftar di BvSPK::CLAUSES tapi tidak pernah dipakai di blade — tidak akan tercetak.");
    }
});

it('menampilkan riwayat SPK KOL lintas campaign', function () {
    $kol = DataKol::create([
        'username' => 'justeenff',
        'channel' => 'TikTok',
        'link_userprofile' => 'https://tiktok.com/@justeenff',
        'full_name' => 'M. Farhan Fava Rizky',
    ]);

    foreach ([['GoPay Gamers', '2026-07-10'], ['Sony Pictures', '2026-08-01']] as $i => [$campaign, $tanggal]) {
        BvSPK::create([
            'spk_number' => 'BVN/SPK/2026/0' . (7 + $i) . '/00' . ($i + 1),
            'data_kol_id' => $kol->id,
            'nama_campaign' => $campaign,
            'tanggal_perjanjian' => $tanggal,
            'nominal_kesepakatan' => 500_000,
            'status' => 'draft',
        ]);
    }

    // Terbaru di atas — riwayat dibaca dari yang paling relevan.
    expect($kol->spks()->pluck('nama_campaign')->all())
        ->toBe(['Sony Pictures', 'GoPay Gamers']);
});
