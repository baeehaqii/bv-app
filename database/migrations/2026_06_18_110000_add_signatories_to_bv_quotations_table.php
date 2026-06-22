<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('bv_quotations', function (Blueprint $table) {
            // Daftar penanda tangan fleksibel: [{ name, role, signature }, ...]
            // Kolom ttd_pic_client / ttd_sales_bv / ttd_bd_sales lama tetap dipertahankan
            // demi kompatibilitas dengan record yang sudah ada.
            $table->json('signatories')->nullable()->after('ttd_bd_sales');
        });
    }

    public function down(): void
    {
        Schema::table('bv_quotations', function (Blueprint $table) {
            $table->dropColumn('signatories');
        });
    }
};
