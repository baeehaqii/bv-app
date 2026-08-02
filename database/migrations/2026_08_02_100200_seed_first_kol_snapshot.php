<?php

use App\Models\DataKol;
use Illuminate\Database\Migrations\Migration;

/**
 * Titik awal grafik Follower Growth untuk channel yang sudah ada.
 *
 * Tanpa ini grafik baru mulai terisi setelah channel di-refresh dua kali. Kita
 * sebenarnya sudah tahu followers per channel beserta tanggal scraping terakhir
 * (`terakhir_update`), jadi satu titik pertama bisa dicatat sekarang — bukan
 * data karangan, hanya memindahkan yang sudah ada.
 */
return new class extends Migration {
    public function up(): void
    {
        DataKol::query()
            ->whereNotNull('terakhir_update')
            ->where('followers', '>', 0)
            ->chunkById(100, function ($channels) {
                foreach ($channels as $channel) {
                    $channel->snapshots()->firstOrCreate(
                        ['captured_on' => $channel->terakhir_update->toDateString()],
                        [
                            'followers' => (int) $channel->followers,
                            'engagement_rate' => (float) $channel->engagement_rate,
                            'engagements' => (int) $channel->engagements,
                            'impressions' => (int) $channel->impressions,
                        ],
                    );
                }
            });
    }

    public function down(): void
    {
        // Snapshot hasil scraping sesudahnya tidak boleh ikut terhapus, dan tidak ada
        // penanda mana yang berasal dari seed ini — jadi biarkan.
    }
};
