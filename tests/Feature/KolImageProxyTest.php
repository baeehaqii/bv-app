<?php

use App\Support\KolImageProxy;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;

/**
 * Proxy thumbnail KOL. Endpoint "ambilkan URL ini" adalah open proxy kalau
 * pengamanannya lepas, jadi tanda tangan + allowlist host yang dikunci di sini.
 */
beforeEach(function () {
    Storage::fake('local');

    // Route-nya di dalam grup auth — <img> di panel admin ikut mengirim cookie sesi.
    $this->actingAs(\App\Models\User::create([
        'name' => 'Proxy Tester',
        'email' => 'proxy-' . uniqid() . '@bvnetwork.net',
        'password' => bcrypt('password'),
    ]));
});

it('hanya menerima https dari host CDN yang dikenal', function () {
    expect(KolImageProxy::isAllowed('https://scontent-cgk1-1.cdninstagram.com/v/t51/foo.jpg'))->toBeTrue()
        ->and(KolImageProxy::isAllowed('https://p16-sign.tiktokcdn-us.com/obj/abc'))->toBeTrue()
        ->and(KolImageProxy::isAllowed('https://i.ytimg.com/vi/abc/hq.jpg'))->toBeTrue();

    expect(KolImageProxy::isAllowed('http://scontent.cdninstagram.com/x.jpg'))->toBeFalse() // wajib https
        ->and(KolImageProxy::isAllowed('https://evil.com/x.jpg'))->toBeFalse()
        // Sufiks palsu: "evil-cdninstagram.com" bukan subdomain cdninstagram.com.
        ->and(KolImageProxy::isAllowed('https://evil-cdninstagram.com/x.jpg'))->toBeFalse()
        ->and(KolImageProxy::isAllowed('https://127.0.0.1/admin'))->toBeFalse()
        ->and(KolImageProxy::isAllowed(null))->toBeFalse();
});

it('url() hanya dibuat untuk sumber yang diizinkan', function () {
    expect(KolImageProxy::url('https://evil.com/x.jpg'))->toBeNull()
        ->and(KolImageProxy::url(null))->toBeNull()
        ->and(KolImageProxy::url('https://i.ytimg.com/vi/a/hq.jpg'))->toContain('signature=');
});

it('menolak permintaan tanpa tanda tangan yang sah', function () {
    $this->get(route('kol-image', ['src' => 'https://i.ytimg.com/vi/a/hq.jpg']))
        ->assertForbidden();
});

it('mengambil gambar dengan Referer yang benar lalu menyajikannya dari domain sendiri', function () {
    Http::fake(['*' => Http::response('BYTES-GAMBAR', 200, ['Content-Type' => 'image/jpeg'])]);

    $src = 'https://p16-sign.tiktokcdn-us.com/obj/cover.jpeg';

    $this->get(KolImageProxy::url($src))
        ->assertOk()
        ->assertHeader('Content-Type', 'image/jpeg')
        ->assertSee('BYTES-GAMBAR');

    // Referer TikTok, bukan domain kita — justru itu yang membuat CDN tidak 403.
    Http::assertSent(fn($request) => $request->header('Referer')[0] === 'https://www.tiktok.com/');

    Storage::disk('local')->assertExists(KolImageProxy::cachePath($src));
});

it('permintaan kedua dilayani dari cache, tidak menembak CDN lagi', function () {
    Http::fake(['*' => Http::response('BYTES', 200, ['Content-Type' => 'image/jpeg'])]);

    $url = KolImageProxy::url('https://i.ytimg.com/vi/abc/hq.jpg');

    $this->get($url)->assertOk();
    $this->get($url)->assertOk();

    Http::assertSentCount(1);
});

it('link CDN yang sudah kedaluwarsa jadi 404, bukan halaman error', function () {
    Http::fake(['*' => Http::response('nope', 403)]);

    $this->get(KolImageProxy::url('https://i.ytimg.com/vi/abc/hq.jpg'))->assertNotFound();
});

it('respons yang bukan gambar ditolak', function () {
    Http::fake(['*' => Http::response('<html>login</html>', 200, ['Content-Type' => 'text/html'])]);

    $this->get(KolImageProxy::url('https://i.ytimg.com/vi/abc/hq.jpg'))->assertNotFound();
    Storage::disk('local')->assertMissing(KolImageProxy::cachePath('https://i.ytimg.com/vi/abc/hq.jpg'));
});

it('URL bertanda tangan tapi host di luar allowlist tetap ditolak', function () {
    // Skenario "tanda tangan bocor": tetap tidak bisa dipakai menembak host internal.
    $url = URL::signedRoute('kol-image', ['src' => 'http://169.254.169.254/latest/meta-data/']);

    $this->get($url)->assertNotFound();
});
