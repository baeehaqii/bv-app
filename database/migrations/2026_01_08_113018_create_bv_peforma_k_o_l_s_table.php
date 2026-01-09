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
        Schema::create('bv_peforma_k_o_l_s', function (Blueprint $table) {
            $table->id();

            $table->string('pic')->nullable();
            $table->string('username')->nullable();
            $table->date('tanggal_posting')->nullable();
            $table->string('link_insight_postingan')->nullable();

            // TikTok
            $table->string('link_posting_tiktok')->nullable();
            $table->bigInteger('tiktok_views')->default(0);
            $table->bigInteger('tiktok_likes')->default(0);
            $table->bigInteger('tiktok_comments')->default(0);
            $table->bigInteger('tiktok_saves')->default(0);
            $table->bigInteger('tiktok_shares')->default(0);
            $table->bigInteger('tiktok_total_engagement')->default(0);

            // Instagram
            $table->string('link_posting_instagram')->nullable();
            $table->bigInteger('instagram_views')->default(0);
            $table->bigInteger('instagram_likes')->default(0);
            $table->bigInteger('instagram_comments')->default(0);
            $table->bigInteger('instagram_saves')->default(0);
            $table->bigInteger('instagram_shares')->default(0);
            $table->bigInteger('instagram_total_engagement')->default(0);

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bv_peforma_k_o_l_s');
    }
};
