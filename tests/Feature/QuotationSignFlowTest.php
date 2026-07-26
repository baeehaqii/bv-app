<?php

use App\Models\BvQuotation;
use App\Models\User;
use Illuminate\Support\Facades\Storage;

/**
 * Quotation dibagikan lewat link (tanpa PDF), pengesahannya urut:
 * CEO → Business Development → Client (client tanda tangan sendiri di halaman public).
 */
function quotation(): BvQuotation
{
    $user = User::create([
        'name' => 'Pembuat Quotation',
        'email' => 'quo-' . uniqid() . '@bvnetwork.net',
        'password' => bcrypt('password'),
    ]);

    return BvQuotation::create([
        'quotation_number' => 'BV/Q/TEST/' . uniqid(),
        'quotation_date' => now()->toDateString(),
        'client_name' => 'PT Client Uji',
        'total_amount' => 10_000_000,
        'status' => 'draft',
        'user_id' => $user->id,
    ]);
}

/** 1x1 px PNG sebagai data URL — cukup untuk menguji jalur simpan gambar TTD. */
function pngDataUrl(): string
{
    return 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mP8z8DwHwAFAAH/q842iQAAAABJRU5ErkJggg==';
}

it('mengunci urutan tanda tangan CEO → BD → Client', function () {
    $q = quotation();

    expect($q->nextSigner())->toBe('ceo')
        ->and($q->canSign('ceo'))->toBeTrue()
        ->and($q->canSign('bd'))->toBeFalse()
        ->and($q->canSign('client'))->toBeFalse();

    // Client / BD tidak bisa mendahului CEO.
    expect(fn() => $q->sign('client', 'Client Nakal'))->toThrow(RuntimeException::class);
    expect(fn() => $q->sign('bd', 'BD Nakal'))->toThrow(RuntimeException::class);

    $q->sign('ceo', 'Bos Besar', 'CEO');
    expect($q->refresh()->nextSigner())->toBe('bd')
        ->and($q->isSignedBy('ceo'))->toBeTrue()
        ->and($q->status)->toBe('draft');

    $q->sign('bd', 'Mas BD', 'Business Development');
    expect($q->refresh()->nextSigner())->toBe('client');

    $q->sign('client', 'PIC Client', 'Marketing Manager');
    expect($q->refresh()->isFullySigned())->toBeTrue()
        ->and($q->status)->toBe('accepted')
        ->and($q->nextSigner())->toBeNull();

    // Sudah lengkap → tidak bisa ditandatangani ulang.
    expect(fn() => $q->sign('client', 'Dobel'))->toThrow(RuntimeException::class);
});

it('client tanda tangan lewat link public dan gambarnya tersimpan', function () {
    Storage::fake('public');

    $q = quotation();
    $q->generatePublicToken();
    $q->sign('ceo', 'Bos Besar', 'CEO');
    $q->sign('bd', 'Mas BD', 'Business Development');

    $this->post(route('quotation.public.sign', ['token' => $q->public_token]), [
        'name' => 'PIC Client',
        'job_title' => 'Marketing Manager',
        'signature' => pngDataUrl(),
        'agree' => '1',
    ])->assertRedirect(route('quotation.public', ['token' => $q->public_token]));

    $q->refresh();
    expect($q->isSignedBy('client'))->toBeTrue()
        ->and($q->status)->toBe('accepted');

    Storage::disk('public')->assertExists("signatures/quotation-{$q->id}-client.png");
});

it('menolak tanda tangan client bila internal belum tanda tangan', function () {
    $q = quotation();
    $q->generatePublicToken();

    $this->post(route('quotation.public.sign', ['token' => $q->public_token]), [
        'name' => 'PIC Client',
        'signature' => pngDataUrl(),
        'agree' => '1',
    ])->assertSessionHasErrors('signature');

    expect($q->refresh()->isSignedBy('client'))->toBeFalse();
});

it('halaman public menampilkan alur tanda tangan, bukan tombol PDF', function () {
    $q = quotation();
    $q->generatePublicToken();
    $q->sign('ceo', 'Bos Besar', 'CEO');

    $this->get(route('quotation.public', ['token' => $q->public_token]))
        ->assertOk()
        ->assertSee('Pengesahan')
        ->assertSee('Business Development')
        ->assertSee('Menunggu tanda tangan');
});
