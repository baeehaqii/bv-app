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
        Schema::table('media_plan_kols', function (Blueprint $table) {
            $table->foreignId('tipe_pajak_kol')->nullable()->after('domisili')->constrained('master_pphs');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('media_plan_kols', function (Blueprint $table) {
            $table->dropForeign(['tipe_pajak_kol']);
            $table->dropColumn('tipe_pajak_kol');
        });
    }
};
