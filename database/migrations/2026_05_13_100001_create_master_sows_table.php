<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('master_sows', function (Blueprint $table) {
            $table->id();
            $table->string('name');                     // e.g. "IG Reels", "TikTok Video"
            $table->string('channel')->nullable();      // e.g. "instagram", "tiktok", "youtube"
            $table->string('code')->nullable()->unique(); // e.g. "ig_reels"
            $table->text('description')->nullable();
            $table->boolean('is_custom')->default(false); // true = opsi "Custom / Tulis Manual"
            $table->boolean('is_active')->default(true);
            $table->integer('sort_order')->default(0);
            $table->timestamps();
        });

        // Link kol_rate_cards ke master_sows (nullable — bisa pakai custom_sow_name)
        Schema::table('kol_rate_cards', function (Blueprint $table) {
            $table->foreignId('master_sow_id')
                ->nullable()
                ->after('channel')
                ->constrained('master_sows')
                ->nullOnDelete();
            $table->string('custom_sow_name')->nullable()->after('master_sow_id');
        });
    }

    public function down(): void
    {
        Schema::table('kol_rate_cards', function (Blueprint $table) {
            $table->dropForeign(['master_sow_id']);
            $table->dropColumn(['master_sow_id', 'custom_sow_name']);
        });

        Schema::dropIfExists('master_sows');
    }
};
