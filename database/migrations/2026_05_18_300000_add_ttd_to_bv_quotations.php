<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('bv_quotations', function (Blueprint $table) {
            $table->string('ttd_pic_client')->nullable()->after('terms_conditions');
            $table->string('ttd_sales_bv')->nullable()->after('ttd_pic_client');
            $table->string('ttd_bd_sales')->nullable()->after('ttd_sales_bv');
        });
    }

    public function down(): void
    {
        Schema::table('bv_quotations', function (Blueprint $table) {
            $table->dropColumn(['ttd_pic_client', 'ttd_sales_bv', 'ttd_bd_sales']);
        });
    }
};
