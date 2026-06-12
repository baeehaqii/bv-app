<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('data_kols', function (Blueprint $table) {
            $table->foreignId('tipe_pajak_kol')
                ->nullable()
                ->after('rate_card_file')
                ->constrained('master_pphs')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('data_kols', function (Blueprint $table) {
            $table->dropForeign(['tipe_pajak_kol']);
            $table->dropColumn('tipe_pajak_kol');
        });
    }
};
