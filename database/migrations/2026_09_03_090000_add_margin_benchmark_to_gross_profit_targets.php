<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('gross_profit_targets', function (Blueprint $table) {
            $table->decimal('margin_benchmark_percent', 5, 2)
                ->default(31)
                ->after('target_deal_revenue')
                ->comment('Benchmark margin (%) — target_amount = target_deal_revenue x persen ini');
        });
    }

    public function down(): void
    {
        Schema::table('gross_profit_targets', function (Blueprint $table) {
            $table->dropColumn('margin_benchmark_percent');
        });
    }
};
