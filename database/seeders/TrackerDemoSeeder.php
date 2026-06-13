<?php

namespace Database\Seeders;

use App\Models\BvCampaignKol;
use App\Models\BvCampign;
use App\Models\CampaignKolRevision;
use App\Models\CampaignStoryline;
use Illuminate\Database\Seeder;

/**
 * Data demo Campaign Ongoing Internal berdasarkan sheet "Tracker" file acuan client
 * (Masters of the Universe - Sony Pictures). Untuk menguji fitur baru: revisi bertingkat,
 * event_attendance, status canceled, dan tampil di modul Internal + External.
 *
 * Jalankan: php artisan db:seed --class=TrackerDemoSeeder
 */
class TrackerDemoSeeder extends Seeder
{
    public function run(): void
    {
        // Idempotent: hapus campaign demo lama bila ada (cascade ke kols/storylines/revisions).
        BvCampign::where('campaign_name', 'Masters of the Universe')
            ->where('campaign_type', BvCampign::TYPE_INTERNAL)
            ->get()->each->delete();

        $campaign = BvCampign::create([
            'campaign_name'   => 'Masters of the Universe',
            'campaign_type'   => BvCampign::TYPE_INTERNAL,
            'status'          => 'ongoing',
            'media_platforms' => ['tiktok'],
            'start_date'      => '2026-06-02',
            'end_date'        => '2026-06-08',
            'total_cost'      => 15175879,
        ]);

        // [username, status Tracker, event hadir, posting_date, post_url, brief_status]
        $rows = [
            ['adifi_',          'posted',   true,  '2026-06-08', 'https://www.tiktok.com/@adifi_/video/7649246091145121', 'approved'],
            ['kadin5s',         'canceled', false, null,         null,                                                    'draft'],
            ['ramsest00',       'pending',  false, null,         null,                                                    'approved'],
            ['marucatfamily',   'pending',  false, null,         null,                                                    'approved'],
            ['Felix Sudjiman',  'posted',   true,  '2026-06-08', 'https://www.tiktok.com/@felvel817/video/7649748855457', 'approved'],
            ['winnerizky',      'posted',   true,  '2026-06-08', 'https://www.tiktok.com/@winnerizky/video/7649242313155','approved'],
            ['lindafebrianaaa', 'posted',   true,  '2026-06-08', 'https://www.tiktok.com/@lindafebrianaaa/video/76489614','approved'],
            ['ombwokreviewer',  'posted',   true,  '2026-06-08', 'https://www.tiktok.com/@om.brewwwww/video/764898614093','approved'],
        ];

        $kols = [];
        foreach ($rows as [$username, $status, $event, $postDate, $postUrl, $briefStatus]) {
            $kols[$username] = BvCampaignKol::create([
                'campaign_id'      => $campaign->id,
                'creator_name'     => $username,
                'username'         => $username,
                'kol_profile_url'  => 'https://www.tiktok.com/@' . $username,
                'tier'             => 'micro',
                'platform'         => 'tiktok',
                'content_type'     => 'video',
                'price'            => 0,
                'status'           => $status,
                'brief_status'     => $briefStatus,
                'visit_status'     => $event ? 'done' : null,
                'visit_date'       => $event ? $postDate : null,
                'event_attendance' => $event,
                'posting_date'     => $postDate,
                'post_url'         => $postUrl,
                'posted_at'        => $status === 'posted' ? $postDate : null,
            ]);
        }

        // Storylines (content planning) — sebagian approved/posted, sebagian masih waiting_approval.
        foreach ($kols as $username => $kol) {
            if ($username === 'kadin5s') {
                continue; // KOL cancel, tak ada storyline aktif
            }
            CampaignStoryline::create([
                'bv_campaign_id'   => $campaign->id,
                'kol_name'         => $username,
                'platform'         => 'tiktok',
                'sow'              => '1x TikTok Video',
                'content_angle'    => 'Review film Masters of the Universe',
                'status'           => in_array($username, ['ramsest00', 'marucatfamily']) ? 'waiting_approval' : 'approved',
            ]);
        }

        // Revisi bertingkat — contoh Felix Sudjiman & winnerizky (storyline + video s/d final),
        // membuktikan dukungan >2 ronde + Final Revisi yang dulu tak tertampung.
        $felix = $kols['Felix Sudjiman'];
        $this->makeRevisions($campaign, $felix, [
            ['storyline', 1, null,                       'approved',       'Kalau untuk Felix makesure title-nya "Masters of the Universe"'],
            ['storyline', 2, null,                       'approved',       null],
            ['video',     1, 'https://drive.google.com/file/d/felix-v1', 'revision', 'cewenya bilang felix mau nonton day 1?'],
            ['video',     2, 'https://drive.google.com/file/d/felix-v2', 'approved', 'OK'],
            ['caption',   1, null,                       'approved',       'OK'],
        ]);

        $winner = $kols['winnerizky'];
        $this->makeRevisions($campaign, $winner, [
            ['storyline', 1, null,                        'revision',  'Winner paling CTA-nya ganti jadi "sudah tayang" aja'],
            ['storyline', 2, null,                        'approved',  null],
            ['video',     1, 'https://drive.google.com/file/d/winner-v1', 'revision', 'he-man bukan himen, masters pakai s'],
            ['video',     2, 'https://drive.google.com/file/d/winner-v2', 'approved', 'OK'],
        ]);

        // Final Revisi pada video terakhir masing-masing.
        CampaignKolRevision::where('bv_campaign_kol_id', $felix->id)->where('stage', 'video')->where('round', 2)->update(['is_final' => true]);
        CampaignKolRevision::where('bv_campaign_kol_id', $winner->id)->where('stage', 'video')->where('round', 2)->update(['is_final' => true]);

        $this->command?->info("Seeded campaign internal 'Masters of the Universe' dengan {$campaign->kols()->count()} KOL & {$campaign->revisions()->count()} revisi.");
    }

    private function makeRevisions(BvCampign $campaign, BvCampaignKol $kol, array $defs): void
    {
        foreach ($defs as [$stage, $round, $link, $status, $feedback]) {
            CampaignKolRevision::create([
                'bv_campaign_id'     => $campaign->id,
                'bv_campaign_kol_id' => $kol->id,
                'kol_name'           => $kol->creator_name,
                'stage'              => $stage,
                'round'              => $round,
                'asset_link'         => $link,
                'client_feedback'    => $feedback,
                'status'             => $status,
            ]);
        }
    }
}
