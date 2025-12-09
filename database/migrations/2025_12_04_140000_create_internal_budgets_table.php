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
        Schema::create('internal_budgets', function (Blueprint $table) {
            $table->id();

            // 1:1 Relation to Media Plan
            $table->foreignId('media_plan_id')->unique()->constrained('media_plans')->cascadeOnDelete();

            // Budget Summary (Auto-calculated from items)
            $table->decimal('total_rate', 15, 2)->default(0);
            $table->decimal('total_subtotal', 15, 2)->default(0);
            $table->decimal('total_mu_pph', 15, 2)->default(0);
            $table->decimal('total_published_rate', 15, 2)->default(0);
            $table->decimal('total_rounded', 15, 2)->default(0);
            $table->decimal('average_margin_percent', 8, 2)->default(0);

            // Notes & Warnings
            $table->text('notes')->nullable();
            $table->text('warnings')->nullable(); // Auto-generated warnings (margin < 30%, etc)

            // Status
            $table->enum('status', ['draft', 'pending', 'approved', 'rejected'])->default('draft');

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('internal_budgets');
    }
};
