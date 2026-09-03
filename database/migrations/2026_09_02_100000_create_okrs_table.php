<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * OKR tim, meniru bentuk sheet "BV 2026 - Weekly Meetings - OKR".
 *
 * Satu baris = satu Objective milik satu orang di satu periode, persis seperti
 * satu baris di sheet: Status, Objective, Key Results, Results.
 *
 * owner_name berdiri sendiri dan wajib, user_id boleh kosong. Dari lima orang
 * di sheet cuma tiga yang punya akun (Gerry, Gressita, Aliy) — memaksakan FK
 * ke users berarti OKR Andhini & Riyadh tidak bisa dicatat sama sekali.
 *
 * month boleh kosong: sheet memakai dua satuan sekaligus — ada baris "May" dan
 * "June", ada juga "Q4 Oct - Dec" yang memang tidak menunjuk satu bulan.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('okrs', function (Blueprint $table) {
            $table->id();

            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('owner_name');

            $table->unsignedSmallInteger('year');
            $table->unsignedTinyInteger('quarter');
            $table->unsignedTinyInteger('month')->nullable();

            $table->text('objective');
            $table->text('key_results');
            $table->text('results')->nullable();
            $table->string('status')->default('to_do');

            $table->timestamps();

            $table->index(['year', 'quarter']);
            $table->index('owner_name');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('okrs');
    }
};
