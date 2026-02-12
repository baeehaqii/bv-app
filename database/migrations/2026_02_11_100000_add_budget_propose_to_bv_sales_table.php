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
        Schema::table('bv_sales', function (Blueprint $table) {
            $table->decimal('budget_propose', 15, 2)->default(0)->after('campaign_items');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('bv_sales', function (Blueprint $table) {
            $table->dropColumn('budget_propose');
        });
    }
};
