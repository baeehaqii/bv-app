<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('media_plans', function (Blueprint $table) {
            $table->json('sub_pic_campaign_ids')
                ->nullable()
                ->after('pic_campaign_id')
                ->comment('Sub-PIC Campaign (multiple), JSON array of bv_sales_lists IDs');
        });
    }

    public function down(): void
    {
        Schema::table('media_plans', function (Blueprint $table) {
            $table->dropColumn('sub_pic_campaign_ids');
        });
    }
};
