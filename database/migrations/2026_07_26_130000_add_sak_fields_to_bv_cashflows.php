<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Cashflow disajikan mengikuti Standar Akuntansi Keuangan:
 * - `activity`  : klasifikasi arus kas PSAK 2 (operasi / investasi / pendanaan)
 * - `category`  : kode pos akun SAK (lihat BvCashflow::ACCOUNTS)
 * - `source_*`  : asal baris auto-posting, sekaligus kunci idempoten agar
 *                 satu transaksi sumber tidak pernah dobel dicatat.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('bv_cashflows', function (Blueprint $table) {
            $table->string('activity', 20)->default('operasi')->after('type')->index();
            $table->string('source_type')->nullable()->after('attachment');
            $table->unsignedBigInteger('source_id')->nullable()->after('source_type');

            $table->unique(['source_type', 'source_id', 'category'], 'cashflow_source_account_unique');
        });
    }

    public function down(): void
    {
        Schema::table('bv_cashflows', function (Blueprint $table) {
            $table->dropUnique('cashflow_source_account_unique');
            $table->dropIndex(['activity']);
            $table->dropColumn(['activity', 'source_type', 'source_id']);
        });
    }
};
