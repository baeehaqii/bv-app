<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Sheet "Tracker" punya kolom "Event Attendance" (boolean TRUE / –) sebagai item SOW
 * terpisah dari konten. Sistem sebelumnya hanya punya visit_date + visit_status
 * (tanggal & status kunjungan), bukan flag kehadiran event. Tambah boolean eksplisit.
 */
return new class extends Migration {
    public function up(): void
    {
        Schema::table('bv_campaign_kols', function (Blueprint $table) {
            $table->boolean('event_attendance')->default(false)->after('visit_status');
        });
    }

    public function down(): void
    {
        Schema::table('bv_campaign_kols', function (Blueprint $table) {
            $table->dropColumn('event_attendance');
        });
    }
};
