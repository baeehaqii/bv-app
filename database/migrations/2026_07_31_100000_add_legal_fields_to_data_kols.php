<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Data legal & rekening KOL — sumber untuk PIHAK KEDUA di SPK.
     * Sebelum ini NIK/alamat/rekening harus diisi manual tiap terbitkan SPK.
     */
    public function up(): void
    {
        Schema::table('data_kols', function (Blueprint $table) {
            $table->string('nik', 32)->nullable()->after('wa_number');
            $table->text('address')->nullable()->after('nik');
            $table->string('bank_account_name')->nullable()->after('address');
            $table->string('bank_account_number', 64)->nullable()->after('bank_account_name');
            $table->string('bank_name')->nullable()->after('bank_account_number');
            $table->string('bank_branch')->nullable()->after('bank_name');
        });
    }

    public function down(): void
    {
        Schema::table('data_kols', function (Blueprint $table) {
            $table->dropColumn([
                'nik',
                'address',
                'bank_account_name',
                'bank_account_number',
                'bank_name',
                'bank_branch',
            ]);
        });
    }
};
