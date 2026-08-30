<?php

use App\Models\BvSPK;
use App\Models\DataKol;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

/** PNG 1x1 valid sebagai pengganti hasil canvas. */
function dummySignature(): string
{
    return 'data:image/png;base64,'
        . 'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mP8z8DwHwAFAAH/q842iQAAAABJRU5ErkJggg==';
}

function spkSiapTandaTangan(): BvSPK
{
    $kol = DataKol::create([
        'username' => 'justeenff',
        'channel' => 'TikTok',
        'link_userprofile' => 'https://tiktok.com/@justeenff',
        'full_name' => 'M. Farhan Fava Rizky',
        'wa_number' => '085155334015',
        'nik' => '3201132001000006',
    ]);

    return BvSPK::create([
        'spk_number' => 'BVN/SPK/2026/07/011',
        'tanggal_perjanjian' => '2026-07-10',
        'data_kol_id' => $kol->id,
        'pihak_kedua_nama_lengkap' => 'M. Farhan Fava Rizky',
        'pihak_kedua_nama_akun' => 'justeenff (TikTok)',
        'nama_campaign' => 'GoPay Gamers',
        'nominal_kesepakatan' => 516_000,
        'status' => 'draft',
    ]);
}

it('menerbitkan link publik dan menaikkan status draft menjadi menunggu tanda tangan', function () {
    $spk = spkSiapTandaTangan();

    $token = $spk->generatePublicToken();

    expect($token)->toHaveLength(48)
        ->and($spk->fresh()->status)->toBe('active')
        ->and($spk->public_url)->toContain('/spk-sign/' . $token);
});

it('memakai ulang token yang sama supaya link yang sudah dikirim tidak mati', function () {
    $spk = spkSiapTandaTangan();

    expect($spk->generatePublicToken())->toBe($spk->generatePublicToken());
});

it('menolak verifikasi bila data tidak cocok, dan tidak membocorkan field mana yang salah', function () {
    $spk = spkSiapTandaTangan();
    $token = $spk->generatePublicToken();

    $this->post(route('spk.public.verify', $token), [
        'spk_number' => 'BVN/SPK/2026/07/999',
        'name' => 'M. Farhan Fava Rizky',
        'platform' => 'TikTok',
    ])->assertSessionHasErrors('spk_number');

    // Nama salah juga ditolak.
    $this->post(route('spk.public.verify', $token), [
        'spk_number' => $spk->spk_number,
        'name' => 'Orang Lain',
        'platform' => 'TikTok',
    ])->assertSessionHasErrors('spk_number');

    // Platform salah juga ditolak.
    $this->post(route('spk.public.verify', $token), [
        'spk_number' => $spk->spk_number,
        'name' => $spk->pihak_kedua_nama_lengkap,
        'platform' => 'Instagram',
    ])->assertSessionHasErrors('spk_number');

    expect(session()->has("spk_verified_{$spk->id}"))->toBeFalse();
});

it('meloloskan verifikasi walau beda huruf besar-kecil dan spasi berlebih', function () {
    $spk = spkSiapTandaTangan();
    $token = $spk->generatePublicToken();

    $this->post(route('spk.public.verify', $token), [
        'spk_number' => '  bvn/spk/2026/07/011 ',
        'name' => 'm.  farhan   fava rizky',
        'platform' => 'tiktok',
    ])->assertRedirect(route('spk.public', $token));

    expect(session("spk_verified_{$spk->id}"))->toBeTrue();
});

it('tidak mengizinkan tanda tangan sebelum verifikasi lolos', function () {
    $spk = spkSiapTandaTangan();
    $token = $spk->generatePublicToken();

    $this->post(route('spk.public.sign', $token), [
        'signature' => dummySignature(),
        'agree' => '1',
    ])->assertRedirect(route('spk.public', $token));

    expect($spk->fresh()->isSigned())->toBeFalse();
});

it('menyimpan tanda tangan beserta jejak audit lalu menandai SPK signed', function () {
    Storage::fake('public');

    $spk = spkSiapTandaTangan();
    $token = $spk->generatePublicToken();

    $this->withSession(["spk_verified_{$spk->id}" => true])
        ->post(route('spk.public.sign', $token), [
            'signature' => dummySignature(),
            'agree' => '1',
        ])
        ->assertRedirect(route('spk.public', $token));

    $spk->refresh();

    expect($spk->status)->toBe('signed')
        ->and($spk->isSigned())->toBeTrue()
        ->and($spk->signed_ip)->not->toBeNull()
        ->and($spk->signature_path)->toBe("signatures/spk-{$spk->id}-kol.png");

    Storage::disk('public')->assertExists($spk->signature_path);
});

it('menolak replay POST setelah SPK ditandatangani agar TTD sah tidak tertimpa', function () {
    Storage::fake('public');

    $spk = spkSiapTandaTangan();
    $token = $spk->generatePublicToken();

    $this->withSession(["spk_verified_{$spk->id}" => true])
        ->post(route('spk.public.sign', $token), ['signature' => dummySignature(), 'agree' => '1']);

    $ttdPertama = $spk->fresh()->signed_at;

    $this->travel(5)->minutes();

    $this->withSession(["spk_verified_{$spk->id}" => true])
        ->post(route('spk.public.sign', $token), ['signature' => dummySignature(), 'agree' => '1'])
        ->assertRedirect(route('spk.public', $token));

    expect($spk->fresh()->signed_at->eq($ttdPertama))->toBeTrue();
});

it('mewajibkan centang persetujuan', function () {
    $spk = spkSiapTandaTangan();
    $token = $spk->generatePublicToken();

    $this->withSession(["spk_verified_{$spk->id}" => true])
        ->post(route('spk.public.sign', $token), ['signature' => dummySignature()])
        ->assertSessionHasErrors('agree');

    expect($spk->fresh()->isSigned())->toBeFalse();
});

it('menampilkan langkah yang benar sesuai keadaan SPK', function () {
    Storage::fake('public');

    $spk = spkSiapTandaTangan();
    $token = $spk->generatePublicToken();

    // Belum verifikasi → form verifikasi.
    $this->get(route('spk.public', $token))
        ->assertOk()
        ->assertSee('Verifikasi Data SPK')
        ->assertDontSee('Bubuhkan tanda tangan');

    // Sudah verifikasi → preview + panel tanda tangan.
    $this->withSession(["spk_verified_{$spk->id}" => true])
        ->get(route('spk.public', $token))
        ->assertOk()
        ->assertSee('Bubuhkan tanda tangan')
        ->assertSee('GoPay Gamers');

    // Sudah tanda tangan → halaman selesai, apa pun isi session-nya.
    $spk->signByKol(dummySignature(), '127.0.0.1');

    $this->get(route('spk.public', $token))
        ->assertOk()
        ->assertSee('Tanda tangan berhasil!')
        ->assertSee('SIGNED')
        ->assertDontSee('Verifikasi Data SPK');
});

it('mengunci isi perjanjian setelah ditandatangani', function () {
    Storage::fake('public');

    $spk = spkSiapTandaTangan();
    $spk->signByKol(dummySignature(), '127.0.0.1');

    // Nominal, SOW, nama pihak — semuanya terkunci.
    foreach ([
        'nominal_kesepakatan' => 999_000,
        'sow_disepakati' => '5x TikTok Video',
        'pihak_kedua_nama_lengkap' => 'Orang Lain',
        'spk_number' => 'BVN/SPK/2026/07/999',
    ] as $field => $value) {
        expect(fn() => $spk->fresh()->update([$field => $value]))
            ->toThrow(RuntimeException::class, 'sudah ditandatangani KOL');
    }

    expect((float) $spk->fresh()->nominal_kesepakatan)->toBe(516_000.0);
});

it('masih mengizinkan pembatalan dan catatan internal setelah ditandatangani', function () {
    Storage::fake('public');

    $spk = spkSiapTandaTangan();
    $spk->signByKol(dummySignature(), '127.0.0.1');

    $spk->update(['status' => 'cancelled', 'notes' => 'Dibatalkan, KOL mundur.']);

    expect($spk->fresh()->status)->toBe('cancelled')
        ->and($spk->fresh()->notes)->toBe('Dibatalkan, KOL mundur.');
});

it('tidak mengunci SPK yang belum ditandatangani', function () {
    $spk = spkSiapTandaTangan();

    $spk->update(['nominal_kesepakatan' => 750_000]);

    expect((float) $spk->fresh()->nominal_kesepakatan)->toBe(750_000.0);
});

it('menolak token yang tidak dikenal atau SPK yang dibatalkan', function () {
    $this->get(route('spk.public', 'token-ngawur'))->assertNotFound();

    $spk = spkSiapTandaTangan();
    $token = $spk->generatePublicToken();
    $spk->update(['status' => 'cancelled']);

    $this->get(route('spk.public', $token))->assertNotFound();
});

it('menyusun nomor WhatsApp ke format internasional', function (?string $input, ?string $harapan) {
    $spk = spkSiapTandaTangan();
    $spk->dataKol->update(['wa_number' => $input]);

    expect($spk->fresh()->whatsappNumber())->toBe($harapan);
})->with([
    ['085155334015', '6285155334015'],
    ['+62 851-5533-4015', '6285155334015'],
    ['6285155334015', '6285155334015'],
    [null, null],
]);

it('menyertakan link tanda tangan di pesan WhatsApp', function () {
    $spk = spkSiapTandaTangan();
    $spk->generatePublicToken();

    expect($spk->whatsappMessage())
        ->toContain($spk->public_url)
        ->toContain('BVN/SPK/2026/07/011')
        ->toContain('Rp 516.000')
        ->and($spk->whatsappUrl())->toStartWith('https://wa.me/6285155334015?text=');
});

it('menyematkan gambar tanda tangan ke PDF setelah ditandatangani', function () {
    Storage::fake('public');

    $spk = spkSiapTandaTangan();
    $token = $spk->generatePublicToken();

    $sebelum = App\Http\Controllers\KolContractController::prepareData($spk);
    expect($sebelum['signatureBase64'])->toBeNull();

    $spk->signByKol(dummySignature(), '127.0.0.1');

    $sesudah = App\Http\Controllers\KolContractController::prepareData($spk->fresh());
    expect($sesudah['signatureBase64'])->toStartWith('data:image/png;base64,');

    // Dokumen publik memuat catatan e-sign.
    $this->get(route('spk.public.document', $token))
        ->assertOk()
        ->assertSee('Ditandatangani secara elektronik');
});
// Cek via HTTP (bukan view()->render()) supaya $errors ter-share middleware.
it('memakai warna brand dan tombol ala panel di semua langkah', function () {
    $spk = spkSiapTandaTangan();
    $token = $spk->generatePublicToken();

    foreach ([1 => [], 2 => ["spk_verified_{$spk->id}" => true], 4 => []] as $step => $session) {
        if ($step === 4) {
            Storage::fake('public');
            $spk->signByKol(dummySignature(), '127.0.0.1');
        }

        $res = $this->withSession($session)->get(route('spk.public', $token));

        $res->assertOk()
            ->assertSee('bv-btn', false)
            ->assertSee('#48009F', false)      // primary panel office
            ->assertSee('#00E58F', false)      // hijau gradasi (bukan neon lime)
            ->assertDontSee('indigo', false);

        // Spec tombol panel (resources/css/filament/theme/panel/button.css).
        $res->assertSee('border-radius: 9999px', false)
            ->assertSee('box-shadow: 0px 3px 3px 2px var(--bv-glow)', false)
            ->assertSee('padding: 0.625rem 1.5rem', false)
            ->assertSee('rgb(216, 254, 0)', false);

        // Utility Tailwind tidak boleh balik menimpa radius/padding tombol.
        expect(preg_match_all('/class="[^"]*\bbv-btn\b[^"]*\b(?:rounded-(?:lg|xl|2xl)|px-\d|py-\d)\b[^"]*"/', $res->getContent()))
            ->toBe(0);
    }
});

it('tidak menyisakan CSS brand yang tak dipakai di markup', function () {
    Storage::fake('public');

    $spk = spkSiapTandaTangan();
    $token = $spk->generatePublicToken();

    // Satu kelas bisa muncul hanya di langkah tertentu (mis. .bv-btn-secondary
    // baru ada di Preview), jadi yang diperiksa gabungan markup semua langkah.
    $gabungan = $this->get(route('spk.public', $token))->getContent();
    $gabungan .= $this->withSession(["spk_verified_{$spk->id}" => true])
        ->get(route('spk.public', $token))->getContent();

    $spk->signByKol(dummySignature(), '127.0.0.1');
    $gabungan .= $this->get(route('spk.public', $token))->getContent();

    preg_match('/<style>(.*?)<\/style>/s', $gabungan, $m);
    preg_match_all('/\.(bv-[a-z-]+)/', $m[1], $found);

    foreach (array_unique($found[1]) as $class) {
        expect(preg_match('/class="[^"]*\b' . preg_quote($class, '/') . '\b/', $gabungan))
            ->toBe(1, "Kelas .{$class} didefinisikan tapi tidak dipakai di markup mana pun");
    }
});

it('mencetak kotak e-meterai kosong bila SPK belum bermeterai', function () {
    $spk = spkSiapTandaTangan();

    $html = view('pdf.kol-contract', \App\Http\Controllers\KolContractController::prepareData($spk))->render();

    // Nama kelasnya juga muncul di blok <style>, jadi yang dicek span-nya.
    expect($html)->toContain('class="materai materai-kosong"');
});

it('menempelkan gambar e-meterai yang sudah diunggah ke dokumen SPK', function () {
    Storage::fake('public');
    Storage::disk('public')->put('spk-materai/m.png', base64_decode(explode(',', dummySignature())[1]));

    $spk = spkSiapTandaTangan();
    $spk->update(['materai_path' => 'spk-materai/m.png']);

    $html = view('pdf.kol-contract', \App\Http\Controllers\KolContractController::prepareData($spk))->render();

    expect($html)->toContain('alt="e-Meterai"')
        ->and($html)->not->toContain('class="materai materai-kosong"');
});

it('membubuhkan tanda tangan Pihak Pertama dari config ke dokumen SPK', function () {
    $spk = spkSiapTandaTangan();

    $html = view('pdf.kol-contract', \App\Http\Controllers\KolContractController::prepareData($spk))->render();

    expect($html)->toContain('alt="Tanda tangan ' . config('company.signer.nama') . '"')
        ->and(public_path(config('company.signer.signature')))->toBeFile();
});
