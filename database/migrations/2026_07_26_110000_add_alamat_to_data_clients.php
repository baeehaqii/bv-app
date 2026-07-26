<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Alamat client — dipakai auto-fill "Alamat Client" di Quotation. */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('data_clients', function (Blueprint $table) {
            $table->text('alamat')->nullable()->after('website');
        });
    }

    public function down(): void
    {
        Schema::table('data_clients', function (Blueprint $table) {
            $table->dropColumn('alamat');
        });
    }
};
