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
            // Add toggle for flexible tax rate (similar to use_flexible_margin)
            $table->boolean('use_flexible_tax')->default(false)
                ->after('tax_rate_percent')
                ->comment('If true, use tax_rate_percent override instead of auto calculation');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('internal_budget_items', function (Blueprint $table) {
            $table->dropColumn('use_flexible_tax');
        });
    }
};
