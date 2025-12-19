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
        Schema::create('master_margins', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique(); // e.g., 'Low', 'Medium', 'High'
            $table->decimal('min_amount', 15, 2); // Minimum subtotal amount
            $table->decimal('max_amount', 15, 2)->nullable(); // Maximum subtotal amount (null = unlimited)
            $table->decimal('margin_percent', 5, 2); // Margin percentage
            $table->integer('order')->default(0); // Display order
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('master_margins');
    }
};
