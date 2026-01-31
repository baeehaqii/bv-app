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

            // Margin Setting Fields (moved from Internal Budget Item level)
            $table->enum('margin_type', ['auto', 'custom'])->default('auto');
            $table->decimal('margin_percent', 8, 2)->nullable();
            $table->boolean('use_global_margin')->default(true);

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
