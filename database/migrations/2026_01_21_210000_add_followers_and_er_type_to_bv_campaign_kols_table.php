<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('bv_campaign_kols', function (Blueprint $table) {
            // Followers count - needed for ER calculation when views not available
            $table->bigInteger('followers_count')->default(0)->after('saves');

            // ER calculation type: 'views' (ER by Views) or 'followers' (ER by Followers)
            $table->string('er_type')->default('followers')->after('engagement_rate');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('bv_campaign_kols', function (Blueprint $table) {
            $table->dropColumn(['followers_count', 'er_type']);
        });
    }
};
