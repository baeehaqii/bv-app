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
        Schema::table('media_plan_kols', function (Blueprint $table) {
            $table->string('categories')->nullable()->after('channel');
            $table->string('domisili')->nullable()->after('name');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('media_plan_kols', function (Blueprint $table) {
            $table->dropColumn(['categories', 'domisili']);
        });
    }
};
