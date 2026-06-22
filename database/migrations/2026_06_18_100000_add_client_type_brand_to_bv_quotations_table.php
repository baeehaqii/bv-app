<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Memperjelas identitas client pada quotation (dipakai juga untuk invoice):
     * - client_type  : tipe client (agency|direct) dari Database Client.
     * - client_brand : brand terkait (brand itu sendiri untuk direct, atau brand yang
     *                  dihandel agency untuk client bertipe agency).
     */
    public function up(): void
    {
        Schema::table('bv_quotations', function (Blueprint $table) {
            $table->string('client_type')->nullable()->after('client_name');
            $table->string('client_brand')->nullable()->after('client_type');
        });
    }

    public function down(): void
    {
        Schema::table('bv_quotations', function (Blueprint $table) {
            $table->dropColumn(['client_type', 'client_brand']);
        });
    }
};
