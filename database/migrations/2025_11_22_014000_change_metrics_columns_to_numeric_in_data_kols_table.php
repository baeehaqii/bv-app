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
        Schema::table('data_kols', function (Blueprint $table) {
            // Mengubah kolom string menjadi tipe numerik agar sorting bekerja dengan benar
            $table->bigInteger('followers')->nullable()->change();
            $table->decimal('engagement_rate', 8, 2)->nullable()->change();
            $table->bigInteger('impressions')->nullable()->change();
            $table->bigInteger('engagements')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('data_kols', function (Blueprint $table) {
            $table->string('followers')->nullable()->change();
            $table->string('engagement_rate')->nullable()->change();
            $table->string('impressions')->nullable()->change();
            $table->string('engagements')->nullable()->change();
        });
    }
};
