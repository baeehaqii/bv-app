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
        Schema::create('data_kols', function (Blueprint $table) {
            $table->id();
            $table->string('username')->nullable();
            $table->string('status')->nullable();
            $table->string('link_userprofile');
            $table->string('channel')->nullable();
            $table->json('category')->nullable();
            $table->bigInteger('followers')->nullable();
            $table->string('tier')->nullable();
            $table->decimal('engagement_rate', 8, 2)->nullable();
            $table->bigInteger('impressions')->nullable();
            $table->bigInteger('engagements')->nullable();
            $table->string('contact')->nullable();
            $table->string('full_name')->nullable()->comment('Nama lengkap / nama asli KOL');
            $table->string('email')->nullable()->comment('Email KOL');
            $table->string('wa_number')->nullable()->comment('Nomor WhatsApp KOL');
            $table->text('notes')->nullable();
            $table->date('terakhir_update')->nullable();
            $table->decimal('rate_card', 15, 2)->nullable()->comment('Published rate card per channel');

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('data_kols');
    }
};
