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
        Schema::table('media_plans', function (Blueprint $table) {
            // Margin Setting Fields (moved from Internal Budget Item level)
            $table->enum('margin_type', ['auto', 'custom'])->default('auto')->after('notes');
            $table->decimal('margin_percent', 8, 2)->nullable()->after('margin_type');
            $table->boolean('use_global_margin')->default(true)->after('margin_percent');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('media_plans', function (Blueprint $table) {
            $table->dropColumn(['margin_type', 'margin_percent', 'use_global_margin']);
        });
    }
};
