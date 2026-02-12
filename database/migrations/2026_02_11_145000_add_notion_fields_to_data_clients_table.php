<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('data_clients', function (Blueprint $table) {
            $table->string('account_owner')->nullable();
            $table->string('agency_name')->nullable();
            $table->string('parent_brand')->nullable();
            $table->string('instagram')->nullable();
            $table->string('tiktok')->nullable();
            $table->integer('top')->nullable()->comment('Term of Payment in days');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('data_clients', function (Blueprint $table) {
            $table->dropColumn([
                'account_owner',
                'agency_name',
                'parent_brand',
                'instagram',
                'tiktok',
                'top',
            ]);
        });
    }
};
