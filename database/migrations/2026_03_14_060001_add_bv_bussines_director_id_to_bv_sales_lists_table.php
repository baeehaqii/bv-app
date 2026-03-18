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
        Schema::table('bv_sales_lists', function (Blueprint $table) {
            $table->foreignId('bv_bussines_director_id')
                ->nullable()
                ->after('id')
                ->constrained('bv_bussines_directors')
                ->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('bv_sales_lists', function (Blueprint $table) {
            $table->dropConstrainedForeignId('bv_bussines_director_id');
        });
    }
};
