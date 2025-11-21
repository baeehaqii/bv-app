<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
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
            $table->string('category')->nullable();
            $table->string('followers')->nullable();
            $table->string('tier')->nullable();
            $table->string('engagement_rate')->nullable();
            $table->string('impressions')->nullable();
            $table->string('engagements')->nullable();
            $table->string('contact')->nullable();
            $table->text('notes')->nullable();
            $table->date('terakhir_update')->nullable();

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
