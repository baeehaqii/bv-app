<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('bv_quotations', function (Blueprint $table) {
            $table->foreignId('internal_budget_id')
                ->nullable()
                ->after('id')
                ->constrained('internal_budgets')
                ->nullOnDelete()
                ->comment('Link ke Internal Budget (Media Plan External)');
        });
    }

    public function down(): void
    {
        Schema::table('bv_quotations', function (Blueprint $table) {
            $table->dropForeign(['internal_budget_id']);
            $table->dropColumn('internal_budget_id');
        });
    }
};
