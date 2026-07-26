<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Alur tanda tangan quotation: CEO → Business Development → Client.
 * Satu kolom JSON: { ceo: {name, role, image, at}, bd: {...}, client: {...} }.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('bv_quotations', function (Blueprint $table) {
            $table->json('signatures')->nullable()->after('signatories');
        });
    }

    public function down(): void
    {
        Schema::table('bv_quotations', function (Blueprint $table) {
            $table->dropColumn('signatures');
        });
    }
};
