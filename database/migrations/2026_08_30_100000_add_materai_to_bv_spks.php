<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('bv_s_p_k_s', function (Blueprint $table) {
            // Wadah e-meterai: gambarnya ditempel manual oleh admin.
            // Belum ada integrasi Peruri, jadi cukup satu kolom path.
            $table->string('materai_path')->nullable()->after('signature_path');
        });
    }

    public function down(): void
    {
        Schema::table('bv_s_p_k_s', function (Blueprint $table) {
            $table->dropColumn('materai_path');
        });
    }
};
