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
            $table->foreignId('master_pph_id')->nullable()->after('scope_item')->constrained('master_pphs')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('internal_budget_items', function (Blueprint $table) {
            $table->dropForeign(['master_pph_id']);
            $table->dropColumn('master_pph_id');
        });
    }
};
