<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Klausul opsional & add-ons SPK.
     * JSON, bukan tabel sendiri: isinya selalu dibaca/ditulis utuh bersama SPK-nya
     * dan tidak pernah di-query per-klausul, jadi tabel relasi cuma menambah join.
     */
    public function up(): void
    {
        Schema::table('bv_s_p_k_s', function (Blueprint $table) {
            $table->json('clauses')->nullable()->after('termin_pembayaran_2');
            $table->json('addons')->nullable()->after('clauses');
        });
    }

    public function down(): void
    {
        Schema::table('bv_s_p_k_s', function (Blueprint $table) {
            $table->dropColumn(['clauses', 'addons']);
        });
    }
};
