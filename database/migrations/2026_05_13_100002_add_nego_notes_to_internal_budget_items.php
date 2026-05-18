<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('internal_budget_items', function (Blueprint $table) {
            // Extend status enum to include 'nego'
            // Kolom status sudah ada sebagai string default('pending')
            // Hanya perlu tambah kolom nego_notes

            $table->text('nego_notes')->nullable()->after('rejection_notes');
        });
    }

    public function down(): void
    {
        Schema::table('internal_budget_items', function (Blueprint $table) {
            $table->dropColumn('nego_notes');
        });
    }
};
