<?php

use App\Filament\Resources\MediaPlans\Schemas\MediaPlanForm;
use App\Models\DataKol;
use App\Models\MasterSow;

/**
 * Aksi "Tambah KOL Multi-Channel": input beberapa channel sekaligus → 1 DataKol per channel
 * + rate card-nya, lalu append 1 baris KOL List per channel. Tidak memanggil API eksternal.
 */

it('membuat 1 DataKol per channel + rate card per channel + 1 baris per channel', function () {
    $igSow = MasterSow::create(['name' => 'IG Reels', 'channel' => 'Instagram', 'is_custom' => false, 'is_active' => true, 'sort_order' => 1]);
    $ttSow = MasterSow::create(['name' => 'TikTok Video', 'channel' => 'Tiktok', 'is_custom' => false, 'is_active' => true, 'sort_order' => 2]);

    $data = [
        'channels' => [
            ['channel' => 'Instagram', 'link_userprofile' => 'https://instagram.com/budi', 'username' => 'budi', 'followers' => 10000, 'tier' => 'Micro', 'engagement_rate' => 3.5, 'engagements' => 350, 'impressions' => 5000, 'category' => ['Lifestyle']],
            ['channel' => 'Tiktok', 'link_userprofile' => 'https://tiktok.com/@budi', 'username' => 'budi', 'followers' => 20000, 'tier' => 'Micro', 'engagement_rate' => 5, 'engagements' => 1000, 'impressions' => 8000, 'category' => ['Lifestyle']],
        ],
        'full_name' => 'Budi PIC',
        'email' => 'budi@example.com',
        'wa_number' => '08123',
        'notes' => 'catatan',
        'rate_cards' => [
            ['channel' => 'Instagram', 'master_sow_id' => $igSow->id, 'rate' => 4544444, 'valid_from' => now()->toDateString()],
            ['channel' => 'Tiktok', 'master_sow_id' => $ttSow->id, 'rate' => 444444, 'valid_from' => now()->toDateString()],
        ],
    ];

    $result = MediaPlanForm::createMultiChannelKols($data, []);

    expect($result['created'])->toBe(2)
        ->and($result['kols'])->toHaveCount(2)
        ->and(DataKol::count())->toBe(2);

    $ig = DataKol::where(['channel' => 'Instagram', 'username' => 'budi'])->first();
    $tt = DataKol::where(['channel' => 'Tiktok', 'username' => 'budi'])->first();

    expect($ig)->not->toBeNull()
        ->and($tt)->not->toBeNull()
        ->and($ig->full_name)->toBe('Budi PIC')           // additional info shared ke semua channel
        ->and($tt->full_name)->toBe('Budi PIC')
        ->and($ig->rateCards()->count())->toBe(1)          // rate card terkelompok per channel
        ->and($tt->rateCards()->count())->toBe(1);

    $igRow = collect($result['kols'])->firstWhere('channel', 'Instagram');
    expect($igRow['data_kol_id'])->toBe($ig->id)
        ->and($igRow['name'])->toBe('budi')
        ->and($igRow['scope_items'])->toContain('IG Reels')  // SOW auto-fill dari rate card
        ->and((int) $igRow['rate'])->toBe(4544444);          // rate dihitung dari rate card
});

it('append ke baris yang sudah ada tanpa menghapus & melanjutkan row_number', function () {
    $existing = [['row_number' => 5, 'name' => 'lama', 'channel' => 'Instagram', 'data_kol_id' => null]];

    // Threads = channel non-scrapable → tanpa API, username manual.
    $data = ['channels' => [['channel' => 'Threads', 'username' => 'newkol', 'followers' => 100]]];

    $result = MediaPlanForm::createMultiChannelKols($data, $existing);

    expect($result['kols'])->toHaveCount(2)
        ->and($result['kols'][0]['name'])->toBe('lama')
        ->and($result['kols'][1]['row_number'])->toBe(6)
        ->and($result['kols'][1]['channel'])->toBe('Threads')
        ->and($result['kols'][1]['name'])->toBe('newkol');
});

it('melewati channel tanpa username atau channel', function () {
    $data = ['channels' => [
        ['channel' => 'Instagram', 'username' => ''],
        ['channel' => '', 'username' => 'tanpachannel'],
    ]];

    $result = MediaPlanForm::createMultiChannelKols($data, []);

    expect($result['created'])->toBe(0)
        ->and($result['kols'])->toHaveCount(0)
        ->and(DataKol::count())->toBe(0);
});
