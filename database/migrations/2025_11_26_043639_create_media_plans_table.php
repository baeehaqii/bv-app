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
            $table->string("brand");
            $table->string("pic_client");
            $table->string("quotation_number");
            $table->string("campaign_type");
            $table->string("campaign_name");
            $table->string("campaign_period_start");
            $table->string("campaign_period_end");
            $table->string("platform");
            $table->string("domisili");
            $table->string("username");
            $table->string("link");
            $table->string("channel");
            $table->string("categories");
            $table->string("followers");
            $table->string("tier");
            $table->string("er");
            $table->string("avg_views");
            $table->string("engagement");
            $table->string("cpi_cpv");
            $table->string("cpe");
            $table->string("scopeofwork");
            $table->string("rate");
            $table->string("notes")->nullable();
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
