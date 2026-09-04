<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Satu baris berisi seluruh angka rumus Media Plan Internal yang dulu hardcode:
 * langkah pembulatan (kolom AC), arah pembulatan, margin default (kolom AA),
 * batas margin, dan ambang Tier (kolom I). Diedit lewat halaman
 * "Masterdata Media Plan Internal".
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('media_plan_calc_settings', function (Blueprint $table) {
            $table->id();
            $table->decimal('rounding_step', 15, 2)->default(100000);
            $table->string('rounding_mode')->default('up');
            $table->decimal('default_margin_percent', 5, 2)->default(50);
            $table->decimal('max_margin_percent', 5, 2)->default(99);
            $table->json('tier_thresholds')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('media_plan_calc_settings');
    }
};
