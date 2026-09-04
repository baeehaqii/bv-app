<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Kolom "Plan COGS" dan "Projected Nett Margin" (rupiah) dari sheet PIPELINE BD.
 *
 * Dua angka lain di blok yang sama sudah punya kolom: "Budget Plan from Clients"
 * = budget_propose, dan "Projected Nett Margin %" = margin.
 */
return new class extends Migration {
    public function up(): void
    {
        Schema::table('bv_sales', function (Blueprint $table) {
            $table->decimal('plan_cogs', 15, 2)->nullable()->after('deal_value')
                ->comment('Rencana biaya pokok campaign (Plan COGS di sheet BD)');
            $table->decimal('projected_nett_margin', 15, 2)->nullable()->after('plan_cogs')
                ->comment('Proyeksi margin bersih dalam rupiah = budget_propose - plan_cogs');
        });
    }

    public function down(): void
    {
        Schema::table('bv_sales', function (Blueprint $table) {
            $table->dropColumn(['plan_cogs', 'projected_nett_margin']);
        });
    }
};
