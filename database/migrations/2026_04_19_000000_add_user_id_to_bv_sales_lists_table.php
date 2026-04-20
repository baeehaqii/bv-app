<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('bv_sales_lists', function (Blueprint $table) {
            if (!Schema::hasColumn('bv_sales_lists', 'user_id')) {
                $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete()
                    ->comment('Akun user yang terhubung ke sales person ini (untuk akses personal target)')
                    ->after('id');
            }
        });
    }

    public function down(): void
    {
        Schema::table('bv_sales_lists', function (Blueprint $table) {
            $table->dropForeign(['user_id']);
            $table->dropColumn('user_id');
        });
    }
};
