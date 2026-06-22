<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Margin % per KOL (editable di KOL List). Bila null → pakai margin otomatis
     * (MasterMargin berdasarkan subtotal). Diterapkan ke semua SOW milik KOL saat
     * generate InternalBudgetItem.
     */
    public function up(): void
    {
        Schema::table('media_plan_kols', function (Blueprint $table) {
            $table->decimal('margin_percent', 8, 2)->nullable()->after('rate');
        });
    }

    public function down(): void
    {
        Schema::table('media_plan_kols', function (Blueprint $table) {
            $table->dropColumn('margin_percent');
        });
    }
};
