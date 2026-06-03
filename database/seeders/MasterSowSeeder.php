<?php

namespace Database\Seeders;

use App\Models\MasterSow;
use Illuminate\Database\Seeder;

class MasterSowSeeder extends Seeder
{
    public function run(): void
    {
        $sows = [
            // Instagram
            ['name' => 'IG Feed', 'channel' => 'Instagram', 'sort_order' => 1],
            ['name' => 'IG Story', 'channel' => 'Instagram', 'sort_order' => 2],
            ['name' => 'IG Reels', 'channel' => 'Instagram', 'sort_order' => 3],
            ['name' => 'IG Carousel', 'channel' => 'Instagram', 'sort_order' => 4],
            ['name' => 'IG Live', 'channel' => 'Instagram', 'sort_order' => 5],
            ['name' => 'IG Story + Link', 'channel' => 'Instagram', 'sort_order' => 6],
            ['name' => 'IG Feed Carousel', 'channel' => 'Instagram', 'sort_order' => 7],
            ['name' => 'IG Feed + Story', 'channel' => 'Instagram', 'sort_order' => 8],
            ['name' => 'IG Reels + Story', 'channel' => 'Instagram', 'sort_order' => 9],
            // TikTok
            ['name' => 'TikTok Video', 'channel' => 'Tiktok', 'sort_order' => 10],
            ['name' => 'TikTok Live', 'channel' => 'Tiktok', 'sort_order' => 11],
            ['name' => 'TikTok FYP', 'channel' => 'Tiktok', 'sort_order' => 12],
            // YouTube Channels
            ['name' => 'YouTube Integration', 'channel' => 'Youtube Channels', 'sort_order' => 20],
            ['name' => 'YouTube Dedicated', 'channel' => 'Youtube Channels', 'sort_order' => 21],
            ['name' => 'YouTube Endcard', 'channel' => 'Youtube Channels', 'sort_order' => 22],
            // YouTube Shorts
            ['name' => 'YouTube Shorts', 'channel' => 'Youtube Shorts', 'sort_order' => 30],
            // Threads
            ['name' => 'Threads Post', 'channel' => 'Threads', 'sort_order' => 40],
            // Facebook
            ['name' => 'Facebook Post', 'channel' => 'Facebook', 'sort_order' => 50],
            ['name' => 'Facebook Reels', 'channel' => 'Facebook', 'sort_order' => 51],
            // X (Twitter)
            ['name' => 'Tweet / Post', 'channel' => 'X', 'sort_order' => 60],
            ['name' => 'Thread Post', 'channel' => 'X', 'sort_order' => 61],
            // Talent (offline/event)
            ['name' => 'Event Appearance', 'channel' => 'Talent', 'sort_order' => 70],
            ['name' => 'MC / Host', 'channel' => 'Talent', 'sort_order' => 71],
            // Custom (lintas channel)
            ['name' => 'Custom SOW', 'channel' => null, 'sort_order' => 99, 'is_custom' => true],
        ];

        foreach ($sows as $sow) {
            MasterSow::firstOrCreate(
                ['name' => $sow['name'], 'channel' => $sow['channel']],
                [
                    'sort_order' => $sow['sort_order'],
                    'is_active' => true,
                    'is_custom' => $sow['is_custom'] ?? false,
                ]
            );
        }

        $this->command->info('MasterSow seeded: ' . MasterSow::count() . ' records.');
    }
}
