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
        // All fields are now added in create_bv_campigns_table migration
        // This migration is kept for historical reference but does nothing
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('bv_campaigns', function (Blueprint $table) {
            $table->dropForeign(['client_id']);
            $table->dropColumn([
                'client_id',
                'campaign_description',
                'campaign_image',
                'media_platforms',
                'campaign_type',
                'total_cost',
                'retrieve_option',
                'retrieve_template',
            ]);
        });
    }
};
