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
        Schema::create('bv_campaign_kols', function (Blueprint $table) {
            $table->id();
            $table->foreignId('campaign_id')->constrained('bv_campaigns')->cascadeOnDelete();

            // KOL/Creator information
            $table->string('creator_name');
            $table->string('username')->nullable();
            $table->string('post_url')->nullable();
            $table->double('price')->default(0);

            // Platform & content type
            $table->string('platform'); // instagram, tiktok, youtube
            $table->string('content_type'); // reels, feed, video, photos, short

            // Performance metrics (akan diisi dari API)
            $table->bigInteger('views')->default(0);
            $table->bigInteger('likes')->default(0);
            $table->bigInteger('comments')->default(0);
            $table->bigInteger('shares')->default(0);
            $table->bigInteger('saves')->default(0);
            $table->decimal('engagement_rate', 8, 4)->default(0);
            $table->bigInteger('reach')->default(0);
            $table->bigInteger('impressions')->default(0);
            $table->bigInteger('followers_count')->default(0);
            $table->string('er_type')->nullable();

            // Status
            $table->string('status')->default('pending'); // pending, posted, completed
            $table->timestamp('posted_at')->nullable();
            $table->timestamp('last_fetched_at')->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bv_campaign_kols');
    }
};
