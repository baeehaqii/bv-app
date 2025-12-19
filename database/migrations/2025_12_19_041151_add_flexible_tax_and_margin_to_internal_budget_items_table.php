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
        Schema::table('internal_budget_items', function (Blueprint $table) {
            // Add flexible tax rate (can be overridden per item)
            $table->decimal('tax_rate_percent', 5, 2)->nullable()->comment('Override tax rate percentage - if null, use auto calculation');

            // Add flexible margin percent (replaces fixed 30% margin target)
            $table->boolean('use_flexible_margin')->default(false)->comment('If true, use margin_percent_override instead of auto calculation');
            $table->decimal('margin_percent_override', 5, 2)->nullable()->comment('Custom margin percentage when use_flexible_margin is true');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('internal_budget_items', function (Blueprint $table) {
            $table->dropColumn(['tax_rate_percent', 'use_flexible_margin', 'margin_percent_override']);
        });
    }
};
