<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * E-sign SPK: link publik untuk KOL tanda tangan sendiri.
     * signed_ip & signed_at disimpan sebagai jejak audit — untuk tanda tangan
     * elektronik, "kapan & dari mana" itu bagian dari bukti, bukan hiasan.
     */
    public function up(): void
    {
        Schema::table('bv_s_p_k_s', function (Blueprint $table) {
            $table->string('public_token', 64)->nullable()->unique()->after('notes');
            $table->string('signature_path')->nullable()->after('public_token');
            $table->timestamp('signed_at')->nullable()->after('signature_path');
            $table->string('signed_ip', 45)->nullable()->after('signed_at');
        });
    }

    public function down(): void
    {
        Schema::table('bv_s_p_k_s', function (Blueprint $table) {
            $table->dropColumn(['public_token', 'signature_path', 'signed_at', 'signed_ip']);
        });
    }
};
