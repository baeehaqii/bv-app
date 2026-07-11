<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('internal_budget_items', function (Blueprint $table) {
            // Read by InternalBudgetItem::getGrossUpCoeff()/calculateMuPph()/getTaxValueDisplay()
            // and written by the ItemsRelationManager form Select. Default matches the
            // model's fallback coefficient ('Pribadi' => 0.975).
            $table->string('vendor_tax_type')->default('Pribadi')->after('master_pph_id');
        });
    }

    public function down(): void
    {
        Schema::table('internal_budget_items', function (Blueprint $table) {
            $table->dropColumn('vendor_tax_type');
        });
    }
};
