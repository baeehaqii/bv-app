<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Redesign status Media Plan External (internal_budgets):
 *   lama : draft | pending | approved | rejected   (enum + CHECK constraint)
 *   baru : draft | review_client | approve_client | approve_am | rejected
 *
 * Kolom diubah dari ENUM menjadi VARCHAR (string) agar fleksibel & lepas dari
 * CHECK constraint. Pakai schema builder ->change() agar lintas-driver
 * (MySQL produksi & sqlite testing). Data lama dipetakan:
 * pending→review_client, approved→approve_am. draft & rejected tetap.
 */
return new class extends Migration {
    public function up(): void
    {
        Schema::table('internal_budgets', function (Blueprint $table) {
            $table->string('status', 32)->default('draft')->change();
        });

        DB::table('internal_budgets')->where('status', 'pending')->update(['status' => 'review_client']);
        DB::table('internal_budgets')->where('status', 'approved')->update(['status' => 'approve_am']);
    }

    public function down(): void
    {
        // Balikkan data ke nilai lama sebisa mungkin sebelum mengetatkan kembali.
        DB::table('internal_budgets')->where('status', 'review_client')->update(['status' => 'pending']);
        DB::table('internal_budgets')->whereIn('status', ['approve_client', 'approve_am'])->update(['status' => 'approved']);

        Schema::table('internal_budgets', function (Blueprint $table) {
            $table->enum('status', ['draft', 'pending', 'approved', 'rejected'])->default('draft')->change();
        });
    }
};
