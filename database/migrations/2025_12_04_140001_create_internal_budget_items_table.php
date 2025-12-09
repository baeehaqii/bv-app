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
        Schema::create('internal_budget_items', function (Blueprint $table) {
            $table->id();

            // Relations
            $table->foreignId('internal_budget_id')->constrained('internal_budgets')->cascadeOnDelete();
            $table->foreignId('media_plan_kol_id')->nullable()->constrained('media_plan_kols')->nullOnDelete();

            // Scope of Work (Synced from Media Plan)
            $table->integer('qty')->default(1);
            $table->string('scope_item'); // e.g., "IG Reels", "TT Video", "Visit Alfa"

            // Rate & Cost Calculation
            $table->decimal('rate_base', 15, 2)->default(0); // USER INPUT - Modal/HPP ke Vendor
            $table->decimal('subtotal', 15, 2)->default(0); // Qty × Rate (Base)

            // PPh Calculation (Progressive based on subtotal amount)
            // Coefficient auto-calculated: 0.97 (0-60M) to 0.775 (>5B)
            $table->decimal('mu_pph', 15, 2)->default(0); // Real Cost = Subtotal / PPh Coefficient

            // Pricing Strategy
            // MU Target = MU PPh / 0.7 (fixed ~30% margin target)
            $table->decimal('mu_target', 15, 2)->default(0);
            $table->decimal('published_rate', 15, 2)->nullable(); // Can be manually adjusted
            $table->decimal('rounded', 15, 2)->default(0); // ROUNDUP to nearest 100k
            $table->decimal('actual_margin_percent', 8, 2)->default(0); // (Rounded - MU PPh) / Rounded

            // Notes per item
            $table->text('notes')->nullable();

            // Display Order
            $table->integer('sort_order')->default(0);

            $table->timestamps();

            // Indexes
            $table->index(['internal_budget_id', 'sort_order']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('internal_budget_items');
    }
};
