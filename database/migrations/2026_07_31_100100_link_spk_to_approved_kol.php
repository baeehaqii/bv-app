<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Sambungkan SPK ke KOL yang di-approve client.
     * Kunci idempoten = (internal_budget_id, media_plan_kol_id): satu KOL satu SPK
     * per budget, walau KOL-nya punya beberapa SOW/item.
     */
    public function up(): void
    {
        Schema::table('bv_s_p_k_s', function (Blueprint $table) {
            $table->foreignId('internal_budget_id')->nullable()->after('form_brief_id')
                ->constrained('internal_budgets')->nullOnDelete();
            $table->foreignId('media_plan_kol_id')->nullable()->after('internal_budget_id')
                ->constrained('media_plan_kols')->nullOnDelete();
            $table->foreignId('data_kol_id')->nullable()->after('media_plan_kol_id')
                ->constrained('data_kols')->nullOnDelete();

            $table->unique(['internal_budget_id', 'media_plan_kol_id'], 'spk_budget_kol_unique');
        });
    }

    public function down(): void
    {
        Schema::table('bv_s_p_k_s', function (Blueprint $table) {
            $table->dropUnique('spk_budget_kol_unique');
            $table->dropConstrainedForeignId('internal_budget_id');
            $table->dropConstrainedForeignId('media_plan_kol_id');
            $table->dropConstrainedForeignId('data_kol_id');
        });
    }
};
