<?php

namespace App\Support;

/**
 * Sumber data tunggal untuk skenario "Masters of the Universe — Sony Pictures".
 * Diambil PERSIS dari file acuan client:
 *   [EXT] Masters of the Universe - Sony Pictures - KOL List - BV Network .xlsx
 *
 * - longlist()  → sheet "MacroMicro" (daftar panjang KOL + status pendekatan)
 * - shortlist() → sheet "Approval"   (KOL final di-approve client + rate; Sub Total Cost 15.100.000)
 * - tracker()   → sheet "Tracker"    (eksekusi per-KOL: revisi bertingkat, caption, posting, event)
 *
 * Dipakai bersama oleh SonyPicturesScenarioSeeder (UI manual) dan
 * SonyPicturesEndToEndTest (verifikasi alur otomatis).
 */
class MotuScenarioData
{
    public const CLIENT_BRAND = 'Sony Pictures';

    public const CAMPAIGN_NAME = 'Masters of the Universe';

    public const BD_NAME = 'Gerry';

    public const PIC_INTERNAL = 'Baehaqi';

    public const TIMELINE = 'Week 1 - June 2026';

    /** Sub Total Cost di sheet Approval = jumlah rate ke-7 KOL approved. */
    public const SUBTOTAL_COST = 15_100_000;

    /**
     * Sheet Approval — KOL yang di-approve client (is_selected = true di Media Plan).
     * 'rate' = kolom Rate (cost ke KOL). Jumlah = 15.100.000.
     */
    public static function shortlist(): array
    {
        return [
            ['name' => 'adifi_',          'channel' => 'TikTok', 'followers' => 59600, 'tier' => 'Micro', 'er' => 0.1663, 'rate' => 2_800_000, 'event' => true,  'link' => 'https://www.tiktok.com/@adifi_'],
            ['name' => 'ramsest00',       'channel' => 'TikTok', 'followers' => 7545,  'tier' => 'Nano',  'er' => 3.4673, 'rate' => 4_400_000, 'event' => false, 'link' => 'https://www.tiktok.com/@ramsest00'],
            ['name' => 'marucatfamily',   'channel' => 'TikTok', 'followers' => 22600, 'tier' => 'Micro', 'er' => 0.0051, 'rate' => 1_200_000, 'event' => false, 'link' => 'https://www.tiktok.com/@marucatfamily'],
            ['name' => 'Felix Sudjiman',  'channel' => 'TikTok', 'followers' => 9835,  'tier' => 'Nano',  'er' => 0.0,    'rate' => 2_400_000, 'event' => true,  'link' => 'https://www.tiktok.com/@felvel817'],
            ['name' => 'winnerizky',      'channel' => 'TikTok', 'followers' => 18100, 'tier' => 'Nano',  'er' => 0.0841, 'rate' => 1_000_000, 'event' => true,  'link' => 'https://www.tiktok.com/@winnerizky'],
            ['name' => 'lindafebrianaaa', 'channel' => 'TikTok', 'followers' => 1961,  'tier' => 'Nano',  'er' => 0.0,    'rate' => 1_400_000, 'event' => true,  'link' => 'https://www.tiktok.com/@lindafebrianaaa'],
            ['name' => 'ombwokreviewer',  'channel' => 'TikTok', 'followers' => 9585,  'tier' => 'Nano',  'er' => 0.0,    'rate' => 1_900_000, 'event' => true,  'link' => 'https://www.tiktok.com/@ombewokreviewer'],
        ];
    }

    /**
     * Sheet MacroMicro — daftar panjang KOL yang TIDAK di-approve (is_selected = false).
     * 'status' = status pendekatan (mapping ke MediaPlanKol::STATUS_OPTIONS).
     */
    public static function longlistOnly(): array
    {
        return [
            ['name' => 'ilhahoam',         'channel' => 'TikTok', 'followers' => 71300, 'tier' => 'Micro', 'er' => 0.0106, 'rate' => 3_800_000, 'status' => 'Approaching'],
            ['name' => 'kadin5s',          'channel' => 'TikTok', 'followers' => 35600, 'tier' => 'Micro', 'er' => 0.1073, 'rate' => 1_900_000, 'status' => 'Canceled'],
            ['name' => 're.sireview.film', 'channel' => 'TikTok', 'followers' => 5136,  'tier' => 'Nano',  'er' => 0.1199, 'rate' => 1_000_000, 'status' => 'Approaching'],
            ['name' => 'intantan41',       'channel' => 'TikTok', 'followers' => 2019,  'tier' => 'Nano',  'er' => 0.0,    'rate' => 0,         'status' => 'Approaching'],
            ['name' => 'gelardino99',      'channel' => 'TikTok', 'followers' => 32000, 'tier' => 'Micro', 'er' => 0.2703, 'rate' => 4_800_000, 'status' => 'Approaching'],
            ['name' => 'notthatwit',       'channel' => 'TikTok', 'followers' => 28700, 'tier' => 'Micro', 'er' => 0.0,    'rate' => 0,         'status' => 'Approaching'],
            ['name' => 'eonni.elma',       'channel' => 'TikTok', 'followers' => 61700, 'tier' => 'Micro', 'er' => 0.0204, 'rate' => 2_900_000, 'status' => 'Approaching'],
            ['name' => 'rifkyprasodjo',    'channel' => 'TikTok', 'followers' => 70400, 'tier' => 'Micro', 'er' => 0.0388, 'rate' => 3_800_000, 'status' => 'Approaching'],
        ];
    }

    /**
     * Sheet Tracker — eksekusi per-KOL.
     * 'kol_status'  → BvCampaignKol::STATUSES (posted | pending | canceled)
     * 'revisions'   → baris campaign_kol_revisions (stage|round|asset|feedback|status|final)
     * 'caption' / 'posting_link' / 'posting_date' → hasil akhir.
     */
    public static function tracker(): array
    {
        return [
            [
                'name' => 'adifi_', 'event' => true, 'kol_status' => 'posted',
                'posting_link' => 'https://www.tiktok.com/@adifi_/video/7649246091145121045',
                'posting_date' => '2026-06-08',
                'caption' => 'Review dan bahas film "Masters Of The Universe" #MastersOfTheUniverse #HeMan #NicholasGalitzine',
                'revisions' => [
                    ['stage' => 'storyline', 'round' => 1, 'asset_link' => 'https://docs.google.com/document/d/1J9Qq86adtNVMjTzkxQtnZnSiFy_7ejQ4-cQyXaPG9_k/edit', 'feedback' => null, 'status' => 'approved', 'final' => false],
                    ['stage' => 'video', 'round' => 1, 'asset_link' => 'https://drive.google.com/file/d/1nrABUmQTjMCmtZs4DR4pm0DZ2zcb7SFg/view', 'feedback' => 'masters of the universe ya, bukan master', 'status' => 'revision', 'final' => false],
                    ['stage' => 'video', 'round' => 2, 'asset_link' => 'https://drive.google.com/file/d/1yyEBoNmrytdiimIN1l-3IDRAYaj62v4Q/view', 'feedback' => 'OK', 'status' => 'approved', 'final' => true],
                ],
            ],
            [
                'name' => 'kadin5s', 'event' => false, 'kol_status' => 'canceled',
                'posting_link' => null, 'posting_date' => null, 'caption' => null,
                'revisions' => [],
            ],
            [
                'name' => 'ramsest00', 'event' => false, 'kol_status' => 'pending',
                'posting_link' => null, 'posting_date' => null,
                'caption' => 'Ada yang mau di ajarin sihir gak? #komedi #MastersOfTheUniverse #Film #HeMan',
                'revisions' => [
                    ['stage' => 'storyline', 'round' => 1, 'asset_link' => 'https://docs.google.com/document/d/1q7muVzsWHAkpfT0WSa6coDpOrRAG5GxsCffnGMheM7A/edit', 'feedback' => 'Ramses cuma gitu aja atau nantinya ada cuplikan dia sama temennya beneran nonton? OK bisa jalan', 'status' => 'approved', 'final' => false],
                    ['stage' => 'video', 'round' => 1, 'asset_link' => 'https://drive.google.com/file/d/1oNjdL9uFkNGBxXapYqheqOxEPvukdmRT/view', 'feedback' => null, 'status' => 'waiting_review', 'final' => false],
                ],
            ],
            [
                'name' => 'marucatfamily', 'event' => false, 'kol_status' => 'pending',
                'posting_link' => null, 'posting_date' => null,
                'caption' => 'Misi Nastar menyelamatkan Eternia bersama He-Man✨',
                'revisions' => [
                    ['stage' => 'storyline', 'round' => 1, 'asset_link' => 'https://docs.google.com/document/d/1eABC0VENWlSSB1eJWtcuK5XftyK_TLUVzM5eTAqW1QU/edit', 'feedback' => 'OK', 'status' => 'approved', 'final' => false],
                    ['stage' => 'video', 'round' => 1, 'asset_link' => 'https://drive.google.com/file/d/1ME0s6rY-4M6SbV3hbYQ0pk0pTO3j75cC/view', 'feedback' => 'OK', 'status' => 'approved', 'final' => false],
                ],
            ],
            [
                'name' => 'Felix Sudjiman', 'event' => true, 'kol_status' => 'posted',
                'posting_link' => 'https://www.tiktok.com/@felvel817/video/7649748855457713415',
                'posting_date' => '2026-06-08',
                'caption' => 'gw yang suka He-Man, dia yang beliin ticket nonton Masters of The Universe 🥺',
                'revisions' => [
                    ['stage' => 'storyline', 'round' => 1, 'asset_link' => 'https://drive.google.com/file/d/1jWiKmmP5OsIfJXnC0jrqtP-wPxYzsYuB/view', 'feedback' => 'Masters of The Universe pake "s", diseragamin, ga usah pegang pedang', 'status' => 'revision', 'final' => false],
                    ['stage' => 'storyline', 'round' => 2, 'asset_link' => 'https://docs.google.com/document/d/1QMPFS10gw1br6zC08ly9SYk8u7g-4KoTrHihDPpaNjU/edit', 'feedback' => null, 'status' => 'approved', 'final' => false],
                    ['stage' => 'video', 'round' => 1, 'asset_link' => 'https://drive.google.com/file/d/1ZksEuOf6DOzXdDVNhqjNx_5pLBjFnBEK/view', 'feedback' => 'felix mau nonton day 1 kan? rapiin lagi bahasanya', 'status' => 'revision', 'final' => false],
                    ['stage' => 'video', 'round' => 2, 'asset_link' => 'https://drive.google.com/file/d/1sQ0y7FcgaC3T2Y_M6eiVI7EOhphfFN1z/view', 'feedback' => 'OK', 'status' => 'approved', 'final' => true],
                ],
            ],
            [
                'name' => 'winnerizky', 'event' => true, 'kol_status' => 'posted',
                'posting_link' => 'https://www.tiktok.com/@winnerizky/video/7649242313155300628',
                'posting_date' => '2026-06-08',
                'caption' => 'HE-MAN IS BACK!! Visual Eternia lebih megah! #mastersoftheuniverse #heman',
                'revisions' => [
                    ['stage' => 'storyline', 'round' => 1, 'asset_link' => 'https://docs.google.com/document/d/1HamV4qjialfvBejfj3adZ-EbgDpxt1lh/edit', 'feedback' => 'CTA ganti sudah tayang, dia mau nonton dulu?', 'status' => 'revision', 'final' => false],
                    ['stage' => 'storyline', 'round' => 2, 'asset_link' => 'https://docs.google.com/document/d/1G2sfWBrWNQsLPh5WNCqbyaXnuzVuHP4h/edit', 'feedback' => 'he-man bukan himen, masters pake s', 'status' => 'revision', 'final' => false],
                    ['stage' => 'video', 'round' => 1, 'asset_link' => 'https://drive.google.com/file/d/1vWYMauthyiwoifwN2j1T3_KbBKLmI8Zq/view', 'feedback' => 'OK', 'status' => 'approved', 'final' => true],
                ],
            ],
            [
                'name' => 'lindafebrianaaa', 'event' => true, 'kol_status' => 'posted',
                'posting_link' => 'https://www.tiktok.com/@lindafebrianaaa/video/7648961475654044935',
                'posting_date' => '2026-06-08',
                'caption' => 'Kekuatan itu bukan dari penampilan luar. Masters of the Universe! #HeMan #RekomendasiFilm',
                'revisions' => [
                    ['stage' => 'video', 'round' => 1, 'asset_link' => 'https://drive.google.com/file/d/1-sdO-WNTopkHj1UKra9fjD9X4UtCDw5N/view', 'feedback' => 'OK', 'status' => 'approved', 'final' => true],
                ],
            ],
            [
                'name' => 'ombwokreviewer', 'event' => true, 'kol_status' => 'posted',
                'posting_link' => 'https://www.tiktok.com/@om.brewwwww/video/7648986140938095892',
                'posting_date' => '2026-06-08',
                'caption' => 'Sebelum superhero modern, ada MASTER OF THE UNIVERSE. #HeMan #skeletor',
                'revisions' => [
                    ['stage' => 'storyline', 'round' => 1, 'asset_link' => 'https://docs.google.com/document/d/1HOJFHjobtZsG0hygHIcC-v4C4TKOjhqkZ-vMM0gMnWc/edit', 'feedback' => 'OK', 'status' => 'approved', 'final' => false],
                    ['stage' => 'video', 'round' => 1, 'asset_link' => 'https://drive.google.com/file/d/1PyW50MXsfi51PxCeWz2jBfIRMrUPhSJx/view', 'feedback' => 'OK', 'status' => 'approved', 'final' => true],
                ],
            ],
        ];
    }
}
