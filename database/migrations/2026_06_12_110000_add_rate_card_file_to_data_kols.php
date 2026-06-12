<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('data_kols', function (Blueprint $table) {
            $table->string('rate_card_file')->nullable()->after('rate_card');
        });
    }

    public function down(): void
    {
        Schema::table('data_kols', function (Blueprint $table) {
            $table->dropColumn('rate_card_file');
        });
    }
};
