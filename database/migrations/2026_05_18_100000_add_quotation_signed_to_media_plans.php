<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('media_plans', function (Blueprint $table) {
            $table->string('quotation_signed_path')->nullable()->after('pic_am_id');
            $table->timestamp('quotation_signed_at')->nullable()->after('quotation_signed_path');
        });
    }

    public function down(): void
    {
        Schema::table('media_plans', function (Blueprint $table) {
            $table->dropColumn(['quotation_signed_path', 'quotation_signed_at']);
        });
    }
};
