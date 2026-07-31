<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Setiap arus kas melekat ke satu akun kas/bank supaya saldonya bisa dihitung. */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('bv_cashflows', function (Blueprint $table) {
            $table->foreignId('finance_account_id')
                ->nullable()
                ->after('payment_method')
                ->constrained('finance_accounts')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('bv_cashflows', function (Blueprint $table) {
            $table->dropForeign(['finance_account_id']);
            $table->dropColumn('finance_account_id');
        });
    }
};
