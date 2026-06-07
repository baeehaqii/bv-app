<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('bv_cashflows', function (Blueprint $table) {
            $table->foreignId('data_client_id')
                ->nullable()
                ->after('reference_no')
                ->constrained('data_clients')
                ->nullOnDelete()
                ->comment('Client terkait transaksi (untuk atribusi pembayaran ke client di keuangan)');
        });
    }

    public function down(): void
    {
        Schema::table('bv_cashflows', function (Blueprint $table) {
            $table->dropForeign(['data_client_id']);
            $table->dropColumn('data_client_id');
        });
    }
};
