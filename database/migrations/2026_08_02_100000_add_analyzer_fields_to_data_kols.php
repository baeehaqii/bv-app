<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Field untuk KOL Analyzer.
 *
 * Semua angka ini SUDAH dikembalikan oleh service scraping, tapi selama ini cuma
 * dijejalkan jadi teks di kolom `notes` — tidak bisa dipakai untuk analisis.
 * Di sini disimpan sebagai kolom sungguhan.
 */
return new class extends Migration {
    public function up(): void
    {
        Schema::table('data_kols', function (Blueprint $table) {
            $table->string('profile_pic_url', 2048)->nullable()->after('link_userprofile');
            $table->text('biography')->nullable()->after('full_name');
            $table->unsignedBigInteger('following_count')->nullable()->after('followers');
            $table->unsignedBigInteger('media_count')->nullable()->after('following_count');
            $table->boolean('is_verified')->default(false)->after('media_count');

            $table->unsignedBigInteger('average_likes')->nullable()->after('engagements');
            $table->unsignedBigInteger('average_comments')->nullable()->after('average_likes');
            $table->unsignedBigInteger('average_views')->nullable()->after('average_comments');

            // 10 postingan terakhir hasil normalisasi — dipakai tab Latest Performa &
            // top hashtag, supaya halaman analyzer tidak memanggil API tiap dibuka.
            $table->json('latest_posts')->nullable()->after('notes');

            // Hanya TikTok yang punya endpoint audiens (26 kredit, negara saja).
            $table->json('audience_countries')->nullable()->after('latest_posts');
            $table->timestamp('audience_fetched_at')->nullable()->after('audience_countries');
        });
    }

    public function down(): void
    {
        Schema::table('data_kols', function (Blueprint $table) {
            $table->dropColumn([
                'profile_pic_url', 'biography', 'following_count', 'media_count', 'is_verified',
                'average_likes', 'average_comments', 'average_views',
                'latest_posts', 'audience_countries', 'audience_fetched_at',
            ]);
        });
    }
};
