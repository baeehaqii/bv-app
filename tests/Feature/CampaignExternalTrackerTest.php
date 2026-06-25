<?php

use App\Filament\Resources\CampaignExternals\Pages\ViewCampaignExternal;
use App\Filament\Resources\CampaignExternals\RelationManagers\TrackerExternalRelationManager;
use App\Models\BvCampaignKol;
use App\Models\BvCampign;
use App\Models\User;
use Illuminate\Support\Facades\Gate;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;

/**
 * Phase 2 — Tracker External: memastikan RELATION MANAGER benar-benar me-render baris
 * (mengeksekusi closure kolom). Smoke test GET halaman tidak cukup karena tab RM lazy-load —
 * itu sebabnya bug `typeLabel()` lolos. Test ini render baris langsung.
 */

beforeEach(function () {
    Role::firstOrCreate(['name' => 'super_admin']);
    $user = User::create([
        'name' => 'Tracker Admin',
        'email' => 'tracker-admin@bvnetwork.net',
        'password' => bcrypt('password'),
    ]);
    $user->syncRoles(['super_admin']);
    $this->actingAs($user);
    Gate::before(fn () => true);

    $this->campaign = BvCampign::create([
        'campaign_name' => 'Masters of the Universe',
        'campaign_type' => BvCampign::TYPE_INTERNAL,
        'status' => 'ongoing',
    ]);

    $this->kol = BvCampaignKol::create([
        'campaign_id' => $this->campaign->id,
        'creator_name' => 'adifi_',
        'username' => 'adifi_',
        'platform' => 'tiktok',
        'content_type' => 'video',
        'status' => 'posted',
        'post_url' => 'https://www.tiktok.com/@adifi_/video/1',
        'posting_date' => '2026-06-08',
    ]);
});

it('me-render baris Tracker dengan kolom SOW (content_type_label) tanpa error', function () {
    Livewire::test(TrackerExternalRelationManager::class, [
        'ownerRecord' => $this->campaign,
        'pageClass' => ViewCampaignExternal::class,
    ])
        ->assertSuccessful()
        ->assertCanSeeTableRecords([$this->kol])
        ->assertSee('adifi_')
        ->assertSee('Video'); // content_type_label tiktok/video
});
