<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

/**
 * Buang tabel bv_tracker_progres_kols — resource/model-nya mati (tak pernah dibuat record-nya).
 * Tracker External kini dibangun di atas BvCampaignKol + CampaignStoryline + CampaignKolRevision.
 */
return new class extends Migration {
    public function up(): void
    {
        Schema::dropIfExists('bv_tracker_progres_kols');
    }

    public function down(): void
    {
        // Tidak di-restore: tabel sudah ditinggalkan (lihat TrackerExternalRelationManager).
    }
};
