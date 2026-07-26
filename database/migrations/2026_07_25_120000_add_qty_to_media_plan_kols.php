<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Qty = berapa kali SOW baris ini di-request (5x IG Reels → rate × 5). */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('media_plan_kols', function (Blueprint $table) {
            $table->unsignedInteger('qty')->default(1)->after('scope_items');
        });
    }

    public function down(): void
    {
        Schema::table('media_plan_kols', function (Blueprint $table) {
            $table->dropColumn('qty');
        });
    }
};
