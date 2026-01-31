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
        Schema::create('media_plan_kols', function (Blueprint $table) {
            $table->id();
            $table->foreignId('media_plan_id')->constrained('media_plans')->cascadeOnDelete();

            // Selection State (for Shortlist/Quotation)
            $table->boolean('is_selected')->default(false);
            $table->integer('row_number')->default(0); // Auto-increment per media plan

            // KOL Information
            $table->foreignId('data_kol_id')->nullable()->constrained('data_kols')->nullOnDelete();
            $table->string('pic')->nullable();
            $table->string('status')->default('New List'); // New List, Approaching, Locked, Canceled
            $table->string('name'); // KOL Name (username)
            $table->string('domisili')->nullable(); // Added via refactor

            // Multiple Links Support (JSON array)
            $table->json('links')->nullable(); // Array of URLs - supports multiple links per KOL
            $table->string('channel'); // Instagram, Tiktok, Youtube Channels, Youtube Shorts
            $table->string('categories')->nullable(); // Added via refactor

            // Metrics
            $table->bigInteger('followers')->default(0);
            $table->string('tier')->nullable(); // Nano, Micro, Macro, Mega
            $table->decimal('er_percent', 8, 4)->default(0); // Engagement Rate %
            $table->bigInteger('impression')->default(0); // Avg Views / Impression
            $table->bigInteger('engagement')->default(0); // Followers * ER

            // Pricing (calculated from Internal Budget)
            $table->decimal('cpi_cpv', 15, 2)->nullable(); // Rate Rounded / Impression
            $table->decimal('cpe', 15, 2)->nullable(); // Rate Rounded / Engagement

            $table->foreignId('tipe_pajak_kol')->nullable()->constrained('master_pphs'); // Added via refactor

            // Scope of Work (JSON array of items)
            $table->json('scope_items')->nullable(); // Array of scope items (e.g., ["IG Reels", "IG Story"])

            // Rate (READ ONLY - from Internal Budget Rounded)
            $table->decimal('rate', 15, 2)->default(0);

            // Notes
            $table->text('notes')->nullable();

            $table->timestamps();

            // Index for better performance
            $table->index(['media_plan_id', 'is_selected']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('media_plan_kols');
    }
};
