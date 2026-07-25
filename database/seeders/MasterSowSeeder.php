<?php

namespace Database\Seeders;

use App\Models\MasterSow;
use Illuminate\Database\Seeder;

class MasterSowSeeder extends Seeder
{
    public function run(): void
    {
        // Daftar SOW resmi (revisi 13 Juli, dari mas Gerry).
        // channel harus memakai vokabulari yang sama dengan channel KOL
        // (Instagram / Tiktok / Youtube Channels / Youtube Shorts / Threads),
        // karena MasterSow::byChannel() memfilter pakai nilai ini.
        $sows = [
            // Instagram
            ['name' => 'IG Photo Feed', 'channel' => 'Instagram'],
            ['name' => 'IG Photo Carousel', 'channel' => 'Instagram'],
            ['name' => 'IG Reels', 'channel' => 'Instagram'],
            ['name' => 'IG Story', 'channel' => 'Instagram'],
            ['name' => 'IG Story Session', 'channel' => 'Instagram'],
            ['name' => 'IG Story + Link', 'channel' => 'Instagram'],
            ['name' => 'IG Live', 'channel' => 'Instagram'],
            // TikTok
            ['name' => 'TikTok Video', 'channel' => 'Tiktok'],
            ['name' => 'TikTok Video with Yellow Cart', 'channel' => 'Tiktok'],
            ['name' => 'TikTok Story', 'channel' => 'Tiktok'],
            ['name' => 'TikTok Live', 'channel' => 'Tiktok'],
            ['name' => 'TikTok Live with Yellow Cart', 'channel' => 'Tiktok'],
            // YouTube
            ['name' => 'Youtube Shorts', 'channel' => 'Youtube Shorts'],
            ['name' => 'Youtube Podcast', 'channel' => 'Youtube Channels'],
            ['name' => 'Youtube Video Built-in/Eps', 'channel' => 'Youtube Channels'],
            ['name' => 'Youtube Video Full', 'channel' => 'Youtube Channels'],
            ['name' => 'Youtube Video Product Placement', 'channel' => 'Youtube Channels'],
            // Threads
            ['name' => '1x Threads', 'channel' => 'Threads'],
            ['name' => '1x Threads with Image', 'channel' => 'Threads'],
            ['name' => '1x Threads with Video', 'channel' => 'Threads'],
            ['name' => '1x Threads Utas', 'channel' => 'Threads'],
            ['name' => '1x Threads Utas + Image', 'channel' => 'Threads'],
            ['name' => '1x Threads Utas + Video', 'channel' => 'Threads'],
            // Lintas channel
            ['name' => 'Event Attendance', 'channel' => null],
            ['name' => 'Boosting 30 Days', 'channel' => null],
            ['name' => 'Guest Star Podcast', 'channel' => null],
            ['name' => 'As a Talent', 'channel' => null],
            ['name' => 'Custom SOW', 'channel' => null, 'is_custom' => true],
        ];

        $ids = [];

        foreach ($sows as $i => $sow) {
            // updateOrCreate: baris dengan name+channel sama akan di-update
            // agar master data selalu sinkron dengan daftar di seeder ini.
            $ids[] = MasterSow::updateOrCreate(
                ['name' => $sow['name'], 'channel' => $sow['channel']],
                [
                    'sort_order' => ($i + 1) * 10,
                    'is_active' => true,
                    'is_custom' => $sow['is_custom'] ?? false,
                ]
            )->id;
        }

        // SOW lama di luar daftar ini dinonaktifkan, bukan dihapus —
        // rate card lama masih mereferensikan baris tersebut.
        $retired = MasterSow::whereNotIn('id', $ids)->update(['is_active' => false]);

        $this->command->info('MasterSow seeded: ' . count($ids) . ' aktif, ' . $retired . ' dinonaktifkan.');
    }
}
