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
            $table->bigInteger('total_engagement')->default(0)->after('saves');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('bv_campaign_kols', function (Blueprint $table) {
            $table->dropColumn('total_engagement');
        });
    }
};
