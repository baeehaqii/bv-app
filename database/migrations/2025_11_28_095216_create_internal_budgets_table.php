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
            $table->unsignedBigInteger('media_plan_id')->unique();
            $table->string('scopeofwork_item')->nullable();
            $table->integer('qty')->default(1);
            $table->decimal('rate', 15, 2)->nullable(); // Rate (Base) - Modal/HPP
            $table->decimal('subtotal', 15, 2)->nullable(); // Qty * Rate
            $table->decimal('gross_up_coeff', 8, 2)->default(0.97);
            $table->decimal('tax', 8, 4)->default(0.05); // Reference only
            $table->decimal('mu_pph', 15, 2)->nullable(); // Real Cost
            $table->decimal('mu_target', 15, 2)->nullable(); // Target margin guideline
            $table->decimal('published_rate', 15, 2)->nullable(); // Manual input harga jual
            $table->decimal('rounded', 15, 2)->nullable(); // Rounded price
            $table->decimal('margin_percent', 8, 2)->nullable(); // Margin %
            $table->timestamps();

            $table->foreign('media_plan_id')
                ->references('id')
                ->on('media_plans')
                ->onDelete('cascade');
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
