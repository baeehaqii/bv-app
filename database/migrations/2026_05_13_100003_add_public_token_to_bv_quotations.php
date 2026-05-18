<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('bv_quotations', function (Blueprint $table) {
            $table->string('public_token', 64)->nullable()->unique()->after('status');
            $table->boolean('is_public')->default(false)->after('public_token');

            // Link ke MediaPlan untuk ambil data campaign & brand
            $table->foreignId('media_plan_id')
                ->nullable()
                ->after('internal_budget_id')
                ->constrained('media_plans')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('bv_quotations', function (Blueprint $table) {
            $table->dropForeign(['media_plan_id']);
            $table->dropColumn(['public_token', 'is_public', 'media_plan_id']);
        });
    }
};
