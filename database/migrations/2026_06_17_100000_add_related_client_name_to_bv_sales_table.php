<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Menyimpan relasi pasangan client pada campaign:
     * - Jika client (company_name) bertipe agency  → brand yang dihandel agency tsb.
     * - Jika client (company_name) bertipe direct   → agency yang menghandle brand tsb.
     */
    public function up(): void
    {
        Schema::table('bv_sales', function (Blueprint $table) {
            $table->string('related_client_name')->nullable()->after('company_name');
        });
    }

    public function down(): void
    {
        Schema::table('bv_sales', function (Blueprint $table) {
            $table->dropColumn('related_client_name');
        });
    }
};
