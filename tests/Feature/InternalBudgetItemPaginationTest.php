<?php

use App\Filament\Resources\InternalBudgets\Pages\EditInternalBudget;
use App\Models\BvSales;
use App\Models\InternalBudget;
use App\Models\InternalBudgetItem;
use App\Models\MediaPlan;
use App\Models\MediaPlanKol;
use App\Models\User;
use Illuminate\Support\Facades\Gate;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;

/**
 * Daftar Budget Items di Media Plan External: satu baris per KOL + rincian SOW.
 *
 * Satu budget bisa berisi ratusan item (590 baris milik 98 KOL) dan Filament
 * Repeater membangun komponen seluruh baris sekaligus — halamannya kehabisan
 * memori. Barisnya karena itu diringkas per KOL dan dipaginasi; SOW-nya dibuka
 * lewat tombol Detail SOW.
 */
function budgetDengan(int $jumlahKol, int $sowPerKol = 3): InternalBudget
{
    \App\Models\DataClient::firstOrCreate(['nama_brand' => 'Brand X', 'type' => 'direct']);

    $sales = BvSales::create(['event_name' => 'Uji Pager Item', 'company_name' => 'Brand X']);
    $plan = MediaPlan::create([
        'bv_sales_id' => $sales->id,
        'campaign_name' => 'Uji Pager Item',
        'brand' => 'Brand X',
        'domisili' => 'Bali',
        'quotation_number' => 'BVN/ITEM/' . uniqid(),
    ]);

    $budget = InternalBudget::create(['media_plan_id' => $plan->id, 'status' => 'draft']);
    $urut = 0;

    for ($k = 1; $k <= $jumlahKol; $k++) {
        $kol = MediaPlanKol::create([
            'media_plan_id' => $plan->id,
            'row_number' => $k,
            'name' => 'KOL ' . str_pad((string) $k, 2, '0', STR_PAD_LEFT),
            'channel' => 'Instagram',
            'status' => 'pending',
        ]);

        for ($s = 1; $s <= $sowPerKol; $s++) {
            InternalBudgetItem::create([
                'internal_budget_id' => $budget->id,
                'media_plan_kol_id' => $kol->id,
                'scope_item' => "SOW {$k}.{$s}",
                'qty' => 1,
                'rate_base' => 1_000_000,
                'mu_pph' => 1_200_000,
                'rounded' => 2_000_000,
                'sort_order' => ++$urut,
            ]);
        }
    }

    return $budget;
}

function pagerItemUser(): User
{
    Role::firstOrCreate(['name' => 'super_admin']);

    return tap(User::create([
        'name' => 'Pager Item Admin',
        'email' => 'pager-item-' . uniqid() . '@bvnetwork.net',
        'password' => bcrypt('password'),
    ]))->syncRoles(['super_admin']);
}

function halamanBudget(InternalBudget $budget)
{
    return Livewire::actingAs(pagerItemUser())->test(EditInternalBudget::class, ['record' => $budget->id]);
}

/**
 * Nama aksi baris yang benar-benar terlihat, per baris.
 *
 * Sengaja lewat isVisible() aksinya, bukan lewat HTML: visible() membaca
 * getRawItemState(), dan field yang tidak ter-dehydrate tidak ada di sana —
 * itu pernah bikin tombol Detail SOW tidak pernah muncul sama sekali.
 *
 * @return array<int, array<int, string>>
 */
function aksiTiapBaris($halaman): array
{
    // Schema dibangun ulang tiap request, jadi komponennya harus diambil lagi
    // setelah mode daftar berganti — instance lama sudah tidak punya barisnya.
    $repeater = $halaman->instance()->form->getComponent(
        fn($c) => $c instanceof \Filament\Forms\Components\Repeater && $c->getName() === 'items',
    );

    $hasil = [];

    foreach (array_keys($halaman->get('data')['items']) as $uuid) {
        $hasil[] = collect($repeater->getExtraItemActions())
            ->filter(fn($aksi) => $aksi(['item' => $uuid])->isVisible())
            ->map(fn($aksi) => $aksi->getName())
            ->values()
            ->all();
    }

    return $hasil;
}

it('memuat 5 KOL saja sebagai bawaan, bukan seluruh itemnya', function () {
    Gate::before(fn() => true);
    $budget = budgetDengan(12, sowPerKol: 3);   // 36 item

    $halaman = halamanBudget($budget);

    expect($halaman->get('data')['items'])->toHaveCount(5)
        ->and($halaman->get('itemPerPage'))->toBe(5)
        ->and($halaman->get('itemPage'))->toBe(1);
});

it('menampilkan SOW pertama plus jumlah sisanya di baris KOL', function () {
    Gate::before(fn() => true);
    $budget = budgetDengan(2, sowPerKol: 7);

    $halaman = halamanBudget($budget);
    $baris = collect($halaman->get('data')['items'])->values();

    // 7 SOW jadi SATU baris, bukan 7 baris berturut-turut dengan nama KOL sama.
    expect($baris)->toHaveCount(2)
        ->and($baris[0]['scope_item'])->toBe('SOW 1.1')
        ->and($baris[0]['jumlah_sow'])->toBe(7);

    // Label "+6" dirakit saat render, dan teksnya sekaligus tombol pembuka
    // rincian — kolom aksi ada di ujung kanan tabel, terlalu jauh dijangkau.
    $halaman->assertSee('SOW 1.1', escape: false)
        ->assertSee('+6', escape: false)
        ->assertSee('bukaDetailKol(' . $baris[0]['media_plan_kol_id'] . ')', escape: false);
});

it('tidak memberi label +n pada KOL yang cuma punya satu SOW', function () {
    Gate::before(fn() => true);
    $budget = budgetDengan(2, sowPerKol: 1);

    $halaman = halamanBudget($budget);
    $baris = collect($halaman->get('data')['items'])->values();

    expect($baris[0]['scope_item'])->toBe('SOW 1.1')
        ->and($baris[0]['jumlah_sow'])->toBe(1)
        ->and($halaman->get('data')['items'])->toHaveCount(2);

    $halaman->assertDontSee('class="ib-sow-sisa"', escape: false);
});

it('SOW di mode rincian tampil sebagai teks biasa, bukan tombol', function () {
    Gate::before(fn() => true);
    $budget = budgetDengan(2, sowPerKol: 3);
    $kolId = $budget->items()->orderBy('sort_order')->first()->media_plan_kol_id;

    $halaman = halamanBudget($budget);
    $halaman->call('bukaDetailKol', $kolId);

    // Tidak ada yang bisa dibuka lagi dari sini; tombolnya cuma "Kembali".
    $halaman->assertDontSee('wire:click="bukaDetailKol(', escape: false);
});

it('menjumlahkan angka seluruh SOW ke baris KOL', function () {
    Gate::before(fn() => true);
    $budget = budgetDengan(1, sowPerKol: 4);

    // Nominalnya dibaca dari database, bukan dari fixture: InternalBudgetItem
    // punya hook saving() → recalculate() yang menghitung ulang mu_pph & rounded.
    $items = $budget->items()->get();
    $muPph = (float) $items->sum('mu_pph');
    $rounded = (float) $items->sum('rounded');

    $baris = collect(halamanBudget($budget)->get('data')['items'])->first();

    expect((float) $baris['rate_base'])->toBe(4_000_000.0)
        ->and((float) $baris['mu_pph'])->toBe($muPph)
        ->and((float) $baris['rounded'])->toBe($rounded)
        // Margin dihitung ulang dari total, bukan rata-rata persen per item —
        // rata-rata persen salah kalau nominal antar SOW timpang.
        ->and((float) $baris['actual_margin_percent'])
        ->toBe(round(($rounded - $muPph) / $rounded * 100, 2));
});

it('bisa pindah halaman dan mengubah jumlah KOL per halaman', function () {
    Gate::before(fn() => true);
    $budget = budgetDengan(12, sowPerKol: 2);

    $halaman = halamanBudget($budget);

    $halaman->call('gantiHalamanItem', 3);
    expect($halaman->get('data')['items'])->toHaveCount(2);

    // Halaman di luar jangkauan dijepit, bukan menghasilkan daftar kosong.
    $halaman->call('gantiHalamanItem', 99);
    expect($halaman->get('itemPage'))->toBe(3);

    $halaman->call('aturItemPerPage', 15);
    expect($halaman->get('data')['items'])->toHaveCount(12)
        ->and($halaman->get('itemPage'))->toBe(1);

    // Nilai di luar pilihan yang sah jatuh ke bawaan.
    $halaman->call('aturItemPerPage', 500);
    expect($halaman->get('itemPerPage'))->toBe(5);
});

it('Detail SOW menampilkan seluruh SOW milik satu KOL, lalu bisa kembali', function () {
    Gate::before(fn() => true);
    $budget = budgetDengan(12, sowPerKol: 7);
    $kolId = $budget->items()->orderBy('sort_order')->first()->media_plan_kol_id;

    $halaman = halamanBudget($budget);
    $halaman->call('bukaDetailKol', $kolId);

    $baris = collect($halaman->get('data')['items'])->values();

    expect($baris)->toHaveCount(7)
        ->and($baris->pluck('scope_item')->all())
        ->toBe(['SOW 1.1', 'SOW 1.2', 'SOW 1.3', 'SOW 1.4', 'SOW 1.5', 'SOW 1.6', 'SOW 1.7'])
        // id terisi = aksi per-SOW (Approve/Reject/Nego/Ganti KOL) punya sasaran.
        ->and($baris->pluck('id')->filter())->toHaveCount(7);

    $halaman->call('tutupDetailKol');
    expect($halaman->get('data')['items'])->toHaveCount(5)
        ->and($halaman->get('kolFokus'))->toBeNull();
});

it('baris ringkas per KOL tidak punya id, supaya aksi per-SOW tidak salah sasaran', function () {
    Gate::before(fn() => true);
    $budget = budgetDengan(3, sowPerKol: 3);

    $baris = collect(halamanBudget($budget)->get('data')['items']);

    expect($baris->pluck('id')->filter())->toBeEmpty()
        ->and($baris->pluck('jumlah_sow')->all())->toBe([3, 3, 3]);
});

it('meringkas status SOW yang tidak seragam jadi campuran', function () {
    Gate::before(fn() => true);
    $budget = budgetDengan(1, sowPerKol: 3);
    $budget->items()->orderBy('sort_order')->first()->update(['status' => 'approved']);

    $baris = collect(halamanBudget($budget)->get('data')['items'])->first();
    expect($baris['status'])->toBe('campuran');

    // Seragam → statusnya sendiri, bukan "campuran".
    $budget->items()->update(['status' => 'approved']);
    expect(collect(halamanBudget($budget)->get('data')['items'])->first()['status'])->toBe('approved');
});

it('menyimpan halaman TIDAK menghapus item mana pun', function () {
    Gate::before(fn() => true);
    $budget = budgetDengan(12, sowPerKol: 3);

    halamanBudget($budget)->call('save')->assertHasNoErrors();

    // Daftar ini read-only dan dehydrated(false): save() tidak boleh menganggap
    // 31 item yang tidak ikut dimuat sebagai "dihapus user".
    expect($budget->items()->count())->toBe(36);
});

it('mengubah Status KOL langsung tersimpan tanpa menunggu Save Changes', function () {
    Gate::before(fn() => true);
    $budget = budgetDengan(3, sowPerKol: 3);
    $kolId = $budget->items()->orderBy('sort_order')->first()->media_plan_kol_id;

    $halaman = halamanBudget($budget);
    $items = $halaman->get('data')['items'];
    $halaman->set('data.items.' . array_key_first($items) . '.kol_status', 'contacted');

    // Tanpa penyimpanan seketika status ini hilang begitu user pindah halaman:
    // baris halaman lain sudah tidak ada lagi di state saat Save ditekan.
    expect(MediaPlanKol::find($kolId)->status)->toBe('contacted');
});

it('tetap menampilkan item yang belum terhubung ke KOL mana pun', function () {
    Gate::before(fn() => true);
    $budget = budgetDengan(1, sowPerKol: 2);
    InternalBudgetItem::create([
        'internal_budget_id' => $budget->id,
        'media_plan_kol_id' => null,
        'scope_item' => 'SOW yatim',
        'qty' => 1,
        'sort_order' => 99,
    ]);

    $baris = collect(halamanBudget($budget)->get('data')['items'])->values();

    expect($baris)->toHaveCount(2)
        ->and($baris[1]['scope_item'])->toBe('SOW yatim')
        ->and($baris[1]['media_plan_kol_id'])->toBeNull();

    // Tanpa KOL berarti tidak ada rincian untuk dibuka: teks biasa, bukan tombol.
    expect(substr_count(halamanBudget($budget)->html(), 'wire:click="bukaDetailKol('))->toBe(1);
});

it('setiap baris KOL punya aksi approval, bukan cuma baris pertama', function () {
    Gate::before(fn() => true);
    $budget = budgetDengan(3, sowPerKol: 2);

    $aksi = aksiTiapBaris(halamanBudget($budget));

    // Pernah rusak: media_plan_kol_id itu field disabled, dan disabled() ikut
    // mematikan dehydrate — nilainya tidak ada di getRawItemState() yang dibaca
    // visible(), jadi Detail SOW tidak pernah muncul di baris mana pun.
    expect($aksi)->toHaveCount(3);

    foreach ($aksi as $baris) {
        // Ganti KOL TIDAK ada di sini: satu-satunya aksi yang tetap per SOW.
        expect($baris)->toBe(['detail_sow', 'approve_item', 'reject_item', 'nego_item']);
    }
});

it('approve di baris KOL menyetujui seluruh SOW miliknya sekaligus', function () {
    Gate::before(fn() => true);
    $budget = budgetDengan(2, sowPerKol: 4);
    $kolPertama = $budget->items()->orderBy('sort_order')->first()->media_plan_kol_id;

    $halaman = halamanBudget($budget);
    $uuid = array_key_first($halaman->get('data')['items']);

    $halaman->callFormComponentAction('items', 'approve_item', arguments: ['item' => $uuid]);

    // Empat SOW milik KOL itu ikut semua …
    expect($budget->items()->where('media_plan_kol_id', $kolPertama)->where('status', 'approved')->count())
        ->toBe(4)
        // … dan KOL lain tidak ikut tersenggol.
        ->and($budget->items()->where('media_plan_kol_id', '!=', $kolPertama)->where('status', 'approved')->count())
        ->toBe(0);
});

it('reject & nego di baris KOL juga berlaku untuk seluruh SOW-nya', function () {
    Gate::before(fn() => true);
    $budget = budgetDengan(2, sowPerKol: 3);
    $kolPertama = $budget->items()->orderBy('sort_order')->first()->media_plan_kol_id;

    $halaman = halamanBudget($budget);
    $uuid = array_key_first($halaman->get('data')['items']);

    $halaman->callFormComponentAction('items', 'nego_item', data: [
        'nego_notes' => 'Minta turun 10%',
    ], arguments: ['item' => $uuid]);

    expect($budget->items()->where('media_plan_kol_id', $kolPertama)->where('status', 'nego')->count())
        ->toBe(3);

    // Aksi tadi memuat ulang daftarnya, jadi uuid barisnya sudah berganti.
    $uuid = array_key_first($halaman->get('data')['items']);

    $halaman->callFormComponentAction('items', 'reject_item', data: [
        'rejection_notes' => 'Client tidak cocok',
    ], arguments: ['item' => $uuid]);

    expect($budget->items()->where('media_plan_kol_id', $kolPertama)->where('status', 'rejected')->count())
        ->toBe(3)
        ->and($budget->items()->where('media_plan_kol_id', '!=', $kolPertama)->where('status', 'pending')->count())
        ->toBe(3);
});

it('di mode rincian aksi berlaku untuk satu SOW saja, plus Ganti KOL', function () {
    Gate::before(fn() => true);
    $budget = budgetDengan(3, sowPerKol: 2);
    $kolId = $budget->items()->orderBy('sort_order')->first()->media_plan_kol_id;

    $halaman = halamanBudget($budget);
    $halaman->call('bukaDetailKol', $kolId);

    $aksi = aksiTiapBaris($halaman);
    expect($aksi)->toHaveCount(2);

    foreach ($aksi as $baris) {
        // Detail SOW tidak ada lagi — sudah di dalamnya. Ganti KOL baru muncul
        // di sini karena barisnya memang satu SOW.
        expect($baris)->toBe(['approve_item', 'reject_item', 'nego_item', 'replace_kol']);
    }

    // Approve dari sini cuma kena satu SOW, bukan dua-duanya.
    $uuid = array_key_first($halaman->get('data')['items']);
    $halaman->callFormComponentAction('items', 'approve_item', arguments: ['item' => $uuid]);

    expect($budget->items()->where('media_plan_kol_id', $kolId)->where('status', 'approved')->count())
        ->toBe(1);
});

it('uuid baris basi tidak meledakkan halaman saat isi daftar berganti', function () {
    Gate::before(fn() => true);
    $budget = budgetDengan(3, sowPerKol: 2);
    $kolId = $budget->items()->orderBy('sort_order')->first()->media_plan_kol_id;

    $halaman = halamanBudget($budget);
    $uuidLama = array_key_first($halaman->get('data')['items']);

    // Mengganti isi daftar membuat Filament menerbitkan uuid baru untuk tiap
    // baris, sementara aksi yang barusan diklik masih memegang uuid lama.
    $halaman->call('bukaDetailKol', $kolId);

    $repeater = $halaman->instance()->form->getComponent(
        fn($c) => $c instanceof \Filament\Forms\Components\Repeater && $c->getName() === 'items',
    );

    expect($repeater->getChildSchema($uuidLama) === null)->toBeTrue();

    // Dulu di sini: "Call to a member function getStateSnapshot() on null".
    foreach ($repeater->getExtraItemActions() as $aksi) {
        expect($aksi(['item' => $uuidLama])->isVisible())->toBeFalse();
    }
});

it('pindah halaman juga tidak meninggalkan uuid yang meledak', function () {
    Gate::before(fn() => true);
    $budget = budgetDengan(12, sowPerKol: 2);

    $halaman = halamanBudget($budget);
    $uuidLama = array_key_first($halaman->get('data')['items']);

    $halaman->call('gantiHalamanItem', 2);

    $repeater = $halaman->instance()->form->getComponent(
        fn($c) => $c instanceof \Filament\Forms\Components\Repeater && $c->getName() === 'items',
    );

    foreach ($repeater->getExtraItemActions() as $aksi) {
        expect($aksi(['item' => $uuidLama])->isVisible())->toBeFalse();
    }
});

it('memperingatkan kalau ada approve yang belum masuk Campaign Ongoing', function () {
    Gate::before(fn() => true);
    $budget = budgetDengan(3, sowPerKol: 2);

    // Belum ada yang di-approve → tidak perlu diganggu.
    halamanBudget($budget)->assertDontSee('belum masuk Campaign Ongoing');

    $halaman = halamanBudget($budget);
    $uuid = array_key_first($halaman->get('data')['items']);
    $halaman->callFormComponentAction('items', 'approve_item', arguments: ['item' => $uuid]);

    // Approve hanya menandai internal_budget_items.status; yang mengisi Campaign
    // Ongoing adalah perubahan status budget ke "Approve AM".
    $halaman->assertSee('1 KOL (2 SOW) sudah di-approve tapi belum masuk Campaign Ongoing');
});

it('peringatan hilang setelah status budget final', function () {
    Gate::before(fn() => true);
    $budget = budgetDengan(2, sowPerKol: 2);
    $budget->items()->update(['status' => 'approved']);
    $budget->update(['status' => 'approve_am']);

    halamanBudget($budget)->assertDontSee('belum masuk Campaign Ongoing');
});
