<?php

use App\Enums\OkrStatus;
use App\Filament\Resources\Okrs\Pages\ListOkrs;
use App\Models\Okr;
use App\Models\User;
use Illuminate\Support\Facades\Gate;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;

/**
 * OKR mengikuti template OKR Confluence: satu Objective per baris, dengan Key
 * results, Owner, Partner with, skor 0.0-1.0, dan Current status per bulan.
 */
function okrUser(): User
{
    Role::firstOrCreate(['name' => 'super_admin']);

    return tap(User::create([
        'name' => 'OKR Admin',
        'email' => 'okr-' . uniqid() . '@bvnetwork.net',
        'password' => bcrypt('password'),
    ]))->syncRoles(['super_admin']);
}

function okrBaris(array $atribut = []): Okr
{
    // Periode berjalan, bukan tanggal tetap: halaman defaultnya tahun & kuartal
    // hari ini, jadi data bertanggal mati akan tersaring habis begitu kuartalnya
    // lewat — testnya hijau hari ini lalu merah tanpa ada kode yang berubah.
    return Okr::create(array_merge([
        'owner_name' => 'Gerry',
        'year' => now()->year,
        'quarter' => Okr::quarterFromMonth(now()->month),
        'month' => now()->month,
        'objective' => 'Achieve June sales target',
        'key_results' => 'Booked GP at IDR 500 Million',
        'status' => OkrStatus::IN_PROGRESS,
    ], $atribut));
}

beforeEach(function () {
    $this->actingAs(okrUser());
    Gate::before(fn() => true);
});

it('menampilkan kolom sesuai template: objective, KR, owner, partner, skor, status bulanan', function () {
    okrBaris([
        'partner_with' => 'Tim Creative',
        'expected_score' => 0.8,
        'objective_score' => 0.7,
        'status_month_1' => 'GP baru 180 juta dari target 500 juta.',
    ]);

    Livewire::test(ListOkrs::class)
        ->assertOk()
        ->assertSee('Objectives')
        ->assertSee('Key results')
        ->assertSee('Partner with')
        ->assertSee('Expected EoQ key result score')
        ->assertSee('Current status')
        ->assertSee('Achieve June sales target')
        ->assertSee('Booked GP at IDR 500 Million')
        ->assertSee('Tim Creative')
        ->assertSee('0.8')
        ->assertSee('0.7')
        ->assertSee('GP baru 180 juta dari target 500 juta.');
});

it('hanya menampilkan OKR pada kuartal yang dipilih', function () {
    okrBaris(['objective' => 'Objective kuartal ini']);
    okrBaris(['year' => 2020, 'quarter' => 1, 'month' => 1, 'objective' => 'Objective kuartal lama']);

    Livewire::test(ListOkrs::class)
        ->assertSee('Objective kuartal ini')
        ->assertDontSee('Objective kuartal lama')
        ->set('year', 2020)
        ->set('quarter', 1)
        ->assertSee('Objective kuartal lama')
        ->assertDontSee('Objective kuartal ini');
});

it('periode memakai nama bulan, dan kuartal kalau bulannya kosong', function () {
    expect(okrBaris(['month' => 6, 'quarter' => 2])->periode_label)->toBe('June')
        ->and(okrBaris(['month' => null, 'quarter' => 4])->periode_label)->toBe('Q4');
});

it('current status dipetakan ke tiga bulan milik kuartalnya', function () {
    $okr = okrBaris([
        'quarter' => 3,
        'month' => null,
        'status_month_1' => 'progres Juli',
        'status_month_3' => 'progres September',
    ]);

    $bulan = $okr->status_bulanan;

    expect($bulan)->toHaveCount(3)
        ->and($bulan[0]['nama'])->toBe('July')
        ->and($bulan[0]['isi'])->toBe('progres Juli')
        ->and($bulan[1]['isi'])->toBeNull()
        ->and($bulan[2]['nama'])->toBe('September')
        ->and($bulan[2]['isi'])->toBe('progres September');
});

it('progress menghitung Done terhadap seluruh objective di periode terpilih', function () {
    okrBaris(['status' => OkrStatus::DONE]);
    okrBaris(['status' => OkrStatus::DONE]);
    okrBaris(['status' => OkrStatus::IN_PROGRESS]);
    okrBaris(['status' => OkrStatus::NOT_DONE]);
    okrBaris(['year' => 2020, 'quarter' => 1, 'month' => 1, 'status' => OkrStatus::DONE]);

    expect(Livewire::test(ListOkrs::class)->instance()->ringkasan())
        ->toBe(['total' => 4, 'selesai' => 2, 'persen' => 50]);
});
