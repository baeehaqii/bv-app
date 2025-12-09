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
        Schema::create('media_plans', function (Blueprint $table) {
            $table->id();

            // Campaign Information
            $table->string('brand');
            $table->string('pic_client');
            $table->string('quotation_number')->unique();
            $table->string('campaign_type')->nullable();
            $table->string('campaign_name');
            $table->string('campaign_period_start')->nullable();
            $table->string('campaign_period_end')->nullable();
            $table->string('platform')->nullable();
            $table->string('domisili')->nullable();

            // Metadata
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('media_plans');
    }
};
