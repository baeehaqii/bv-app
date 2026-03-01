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
        Schema::table('bv_campaigns', function (Blueprint $table) {
            $table->foreignId('bv_sales_id')
                ->nullable()
                ->after('id')
                ->constrained('bv_sales')
                ->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('bv_campaigns', function (Blueprint $table) {
            $table->dropForeign(['bv_sales_id']);
            $table->dropColumn('bv_sales_id');
        });
    }
};
