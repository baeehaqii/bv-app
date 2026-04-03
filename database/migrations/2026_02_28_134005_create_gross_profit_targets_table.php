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
        Schema::create('gross_profit_targets', function (Blueprint $table) {
            $table->id();
            $table->unsignedSmallInteger('year')->comment('Tahun target, e.g. 2026');
            $table->unsignedTinyInteger('month')->comment('Bulan 1-12');
            $table->decimal('target_deal_revenue', 18, 2)->default(0)->comment('Target total deal revenue (omset) perusahaan per bulan');
            $table->decimal('target_amount', 18, 2)->default(0)->comment('Nominal target gross profit bulanan');
            $table->text('notes')->nullable()->comment('Catatan opsional');
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['year', 'month'], 'unique_year_month');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('gross_profit_targets');
    }
};
