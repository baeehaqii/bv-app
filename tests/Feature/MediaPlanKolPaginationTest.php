<?php

use App\Filament\Resources\MediaPlans\Pages\EditMediaPlan;
use App\Models\BvSales;
use App\Models\MediaPlan;
use App\Models\MediaPlanKol;
use App\Models\User;
use Illuminate\Support\Facades\Gate;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;

/**
 * Paginasi KOL List di Media Plan Internal.
 *
 * Media plan hasil migrasi bisa berisi ratusan KOL dan Filament Repeater
 * membangun komponen seluruh baris sekaligus. Yang dimuat ke form karena itu
 * cuma satu halaman — dan itu membawa risiko yang harus dijaga ketat: baris di
 * halaman lain TIDAK boleh ikut terhapus saat satu halaman disimpan.
 */
function planDengan(int $jumlahKol): MediaPlan
{
    // Field "brand" itu Select yang divalidasi terhadap daftar client, jadi
    // client-nya harus benar-benar ada — kalau tidak, save() tertahan validasi.
    \App\Models\DataClient::firstOrCreate(['nama_brand' => 'Brand X', 'type' => 'direct']);

    $sales = BvSales::create(['event_name' => 'Uji Paginasi', 'company_name' => 'Brand X']);
    $plan = MediaPlan::create([
        'bv_sales_id' => $sales->id,
        'campaign_name' => 'Uji Paginasi',
        'brand' => 'Brand X',
        // Wajib di form; tanpa ini save() tertahan validasi dan test lulus
        // karena alasan yang salah — tidak ada yang terhapus BUKAN karena
        // paginasinya aman, tapi karena penyimpanannya memang tidak jalan.
        'domisili' => 'Bali',
        'quotation_number' => 'BVN/PAGE/' . uniqid(),
    ]);

    for ($i = 1; $i <= $jumlahKol; $i++) {
        MediaPlanKol::create([
            'media_plan_id' => $plan->id,
            'row_number' => $i,
            'name' => 'KOL ' . str_pad((string) $i, 2, '0', STR_PAD_LEFT),
            'channel' => 'Instagram',
        ]);
    }

    return $plan;
}

function paginasiUser(): User
{
    Role::firstOrCreate(['name' => 'super_admin']);

    return tap(User::create([
        'name' => 'Paginasi Admin',
        'email' => 'paginasi-' . uniqid() . '@bvnetwork.net',
        'password' => bcrypt('password'),
    ]))->syncRoles(['super_admin']);
}

it('memuat 5 KOL saja sebagai bawaan, bukan seluruhnya', function () {
    Gate::before(fn() => true);
    $plan = planDengan(12);

    $halaman = Livewire::actingAs(paginasiUser())->test(EditMediaPlan::class, ['record' => $plan->id]);

    expect($halaman->get('data')['kols'])->toHaveCount(5)
        ->and($halaman->get('kolPerPage'))->toBe(5)
        ->and(collect($halaman->get('data')['kols'])->pluck('name')->all())
        ->toBe(['KOL 01', 'KOL 02', 'KOL 03', 'KOL 04', 'KOL 05']);
});

it('bisa pindah halaman dan mengubah jumlah per halaman', function () {
    Gate::before(fn() => true);
    $plan = planDengan(12);

    $halaman = Livewire::actingAs(paginasiUser())->test(EditMediaPlan::class, ['record' => $plan->id]);

    $halaman->call('gantiHalamanKol', 3);
    expect(collect($halaman->get('data')['kols'])->pluck('name')->all())->toBe(['KOL 11', 'KOL 12']);

    // Halaman di luar jangkauan dijepit, bukan menghasilkan daftar kosong.
    $halaman->call('gantiHalamanKol', 99);
    expect($halaman->get('kolPage'))->toBe(3);

    // Ganti jumlah per halaman mengembalikan ke halaman pertama.
    $halaman->call('aturKolPerPage', 15);
    expect($halaman->get('data')['kols'])->toHaveCount(12)
        ->and($halaman->get('kolPage'))->toBe(1);

    // Nilai di luar pilihan yang sah jatuh ke bawaan.
    $halaman->call('aturKolPerPage', 500);
    expect($halaman->get('kolPerPage'))->toBe(5);
});

it('menyimpan satu halaman TIDAK menghapus KOL di halaman lain', function () {
    Gate::before(fn() => true);
    $plan = planDengan(12);

    Livewire::actingAs(paginasiUser())
        ->test(EditMediaPlan::class, ['record' => $plan->id])
        ->call('save')
        ->assertHasNoErrors();

    // Ini inti keamanannya: 7 KOL di halaman 2 dan 3 tidak ikut dimuat ke form,
    // dan afterSave() tidak boleh menganggapnya "dihapus user".
    expect($plan->kols()->count())->toBe(12);
});

it('menghapus baris dari form tetap menghapus KOL-nya', function () {
    Gate::before(fn() => true);
    $plan = planDengan(12);

    $halaman = Livewire::actingAs(paginasiUser())->test(EditMediaPlan::class, ['record' => $plan->id]);

    // Buang satu baris dari halaman yang sedang dimuat, lalu simpan.
    $kols = $halaman->get('data')['kols'];
    unset($kols[array_key_first($kols)]);
    $halaman->set('data.kols', $kols)->call('save')->assertHasNoErrors();

    expect($plan->kols()->count())->toBe(11)
        ->and($plan->kols()->where('name', 'KOL 01')->exists())->toBeFalse()
        // Halaman lain tetap utuh.
        ->and($plan->kols()->where('name', 'KOL 12')->exists())->toBeTrue();
});

it('pindah halaman tidak membuang isian step lain yang belum disimpan', function () {
    Gate::before(fn() => true);
    $plan = planDengan(12);

    $halaman = Livewire::actingAs(paginasiUser())->test(EditMediaPlan::class, ['record' => $plan->id]);

    // Ketik sesuatu di step Campaign Information, lalu pindah halaman KOL.
    $halaman->set('data.notes', 'catatan yang belum disimpan')
        ->call('gantiHalamanKol', 2);

    // Dulu gantiHalamanKol() memanggil fillForm() dan menarik ulang SEMUA step
    // dari database, jadi catatan ini ikut hilang.
    expect($halaman->get('data')['notes'])->toBe('catatan yang belum disimpan')
        ->and($halaman->get('data')['kols'])->toHaveCount(5);
});

it('suntingan KOL bertahan saat pindah halaman lalu kembali', function () {
    Gate::before(fn() => true);
    $plan = planDengan(12);

    $halaman = Livewire::actingAs(paginasiUser())->test(EditMediaPlan::class, ['record' => $plan->id]);

    $kols = $halaman->get('data')['kols'];
    $kunci = array_key_first($kols);
    $kols[$kunci]['notes'] = 'nego lewat WA';
    $halaman->set('data.kols', $kols);

    $halaman->call('gantiHalamanKol', 2)->call('gantiHalamanKol', 1);

    expect(collect($halaman->get('data')['kols'])->firstWhere('id', $kols[$kunci]['id'])['notes'])
        ->toBe('nego lewat WA');
});

it('suntingan halaman lain ikut tersimpan saat Save Changes ditekan', function () {
    Gate::before(fn() => true);
    $plan = planDengan(12);

    $halaman = Livewire::actingAs(paginasiUser())->test(EditMediaPlan::class, ['record' => $plan->id]);

    // Sunting satu baris di halaman 1 …
    $kols = $halaman->get('data')['kols'];
    $kunci = array_key_first($kols);
    $idDisunting = $kols[$kunci]['id'];
    $kols[$kunci]['notes'] = 'disunting di halaman 1';
    $halaman->set('data.kols', $kols);

    // … lalu pindah ke halaman 2 dan menyimpan dari sana.
    $halaman->call('gantiHalamanKol', 2)->call('save')->assertHasNoErrors();

    expect(MediaPlanKol::find($idDisunting)->notes)->toBe('disunting di halaman 1')
        // Dan tidak ada yang hilang.
        ->and($plan->kols()->count())->toBe(12);
});

it('SOW dipilih lewat modal, bukan tag berjajar di dalam baris', function () {
    Gate::before(fn() => true);
    $plan = planDengan(2);

    $halaman = Livewire::actingAs(paginasiUser())->test(EditMediaPlan::class, ['record' => $plan->id]);

    $kunci = array_key_first($halaman->get('data')['kols']);

    // Tombolnya menggantikan Select multiple: satu baris dengan 4 SOW dulunya
    // setinggi tiga baris dan seluruh KOL List ikut memanjang.
    $halaman->callFormComponentAction("kols.{$kunci}.sow", 'ubah_sow', data: [
        'scope_items' => ['IG Reels', 'IG Story', 'Visit To Store'],
    ])->assertHasNoFormErrors();

    expect($halaman->get('data')['kols'][$kunci]['scope_items'])
        ->toBe(['IG Reels', 'IG Story', 'Visit To Store']);

    // scope_items sekarang Hidden, bukan Select. Yang dipakai saat menyimpan
    // adalah state ter-dehydrate, bukan $this->data — jadi itu yang dicek.
    expect($halaman->instance()->form->getState()['kols'][0]['scope_items'])
        ->toBe(['IG Reels', 'IG Story', 'Visit To Store']);
});

it('meringkas SOW jadi yang pertama plus sisanya', function () {
    expect(\App\Filament\Resources\MediaPlans\Schemas\MediaPlanForm::labelSow([]))->toBe('Pilih SOW')
        ->and(\App\Filament\Resources\MediaPlans\Schemas\MediaPlanForm::labelSow(['IG Reels']))->toBe('IG Reels')
        ->and(\App\Filament\Resources\MediaPlans\Schemas\MediaPlanForm::labelSow(['IG Reels', 'IG Story', 'Visit To Store']))->toBe('IG Reels  +2')
        // Nilai kosong tidak boleh ikut terhitung jadi "+n".
        ->and(\App\Filament\Resources\MediaPlans\Schemas\MediaPlanForm::labelSow(['IG Reels', null, '']))->toBe('IG Reels');
});

it('SOW yang sudah tersimpan tetap jadi opsi walau di luar daftar baku', function () {
    // Kalau tidak, Filament menolaknya sebagai pilihan tak sah dan SOW hasil
    // migrasi spreadsheet hilang begitu barisnya disimpan ulang.
    $opsi = \App\Filament\Resources\MediaPlans\Schemas\MediaPlanForm::sowOptions(null, ['Beer enjoyment with friends']);

    expect($opsi)->toHaveKey('Beer enjoyment with friends')
        ->and($opsi)->toHaveKey('IG Reels');
});
