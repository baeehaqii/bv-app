<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Sheet "Issues To Discuss" dari dokumen Weekly Meeting.
 *
 * Satu baris = satu isu yang dibawa ke rapat, lengkap dengan PIC, resolusi, dan
 * statusnya. Tanggal rapat disimpan di barisnya sendiri — tanpa tabel meetings
 * terpisah, karena selain daftar isu, sheet-nya tidak menyimpan apa pun tentang
 * rapatnya (agendanya sama setiap minggu, dan ratingnya tidak pernah dicatat).
 *
 * priority_score diisi tangan. Di sheet angkanya rata-rata dari kolom suara tiap
 * orang, dan kolom itu kosong sampai #DIV/0! — mekanisme voting-nya belum pernah
 * benar-benar dipakai, jadi tidak ikut dipindahkan.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('meeting_issues', function (Blueprint $table) {
            $table->id();

            $table->date('meeting_date');
            $table->decimal('priority_score', 4, 2)->nullable();
            $table->string('pic')->nullable();
            $table->text('issue');
            $table->text('resolution')->nullable();
            $table->string('status')->default('open');

            $table->timestamps();

            $table->index('meeting_date');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('meeting_issues');
    }
};
