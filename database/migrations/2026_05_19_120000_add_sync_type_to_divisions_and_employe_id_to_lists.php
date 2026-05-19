<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('divisions', function (Blueprint $table) {
            $table->string('sync_type')->nullable()->after('is_active')
                ->comment('sales | business_director — menentukan ke list mana karyawan di-sync otomatis');
        });

        Schema::table('bv_sales_lists', function (Blueprint $table) {
            $table->foreignId('bv_employe_id')
                ->nullable()
                ->unique()
                ->constrained('bv_employes')
                ->nullOnDelete()
                ->after('id')
                ->comment('Referensi ke karyawan yang di-sync otomatis dari data karyawan');
        });

        Schema::table('bv_bussines_directors', function (Blueprint $table) {
            $table->foreignId('bv_employe_id')
                ->nullable()
                ->unique()
                ->constrained('bv_employes')
                ->nullOnDelete()
                ->after('id')
                ->comment('Referensi ke karyawan yang di-sync otomatis dari data karyawan');
        });
    }

    public function down(): void
    {
        Schema::table('bv_bussines_directors', function (Blueprint $table) {
            $table->dropForeign(['bv_employe_id']);
            $table->dropColumn('bv_employe_id');
        });

        Schema::table('bv_sales_lists', function (Blueprint $table) {
            $table->dropForeign(['bv_employe_id']);
            $table->dropColumn('bv_employe_id');
        });

        Schema::table('divisions', function (Blueprint $table) {
            $table->dropColumn('sync_type');
        });
    }
};
