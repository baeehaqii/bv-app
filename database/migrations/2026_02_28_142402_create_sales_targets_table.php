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
        Schema::create('sales_targets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('bv_sales_list_id')->constrained('bv_sales_lists')->cascadeOnDelete();
            $table->unsignedSmallInteger('year')->comment('Tahun target, e.g. 2026');
            $table->unsignedTinyInteger('month')->comment('Bulan 1-12');
            $table->decimal('target_amount', 18, 2)->default(0)->comment('Nominal target deal value bulanan untuk sales ini');
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['bv_sales_list_id', 'year', 'month'], 'unique_sales_year_month');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sales_targets');
    }
};
