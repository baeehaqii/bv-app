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
        Schema::create('bv_campign_upcomings', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('client_id')->nullable()->index();
            $table->string('campaign_name');
            $table->string('campaign_image')->nullable();

            // Metrics / Forecasting
            $table->decimal('budget_allocated', 15, 2)->default(0);
            $table->decimal('pot_cpv', 15, 2)->nullable();
            $table->decimal('pot_cpe', 15, 2)->nullable();
            $table->bigInteger('pot_views')->nullable();

            $table->string('status')->default('forecasted');

            // Additional fields to mirror bv_campaigns if needed for 'finished' campaigns
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();
            $table->json('media_platforms')->nullable();
            $table->text('description')->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bv_campign_upcomings');
    }
};
