<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('media_plans', function (Blueprint $table) {
            // Rename existing pic_campaign_id → pic_sales_bd_id
            $table->renameColumn('pic_campaign_id', 'pic_sales_bd_id');

            // PIC Leads Project (Manager) — single
            $table->unsignedBigInteger('pic_leads_project_id')->nullable()->after('pic_sales_bd_id');

            // PIC Project Internal (KOL Specialist) — multiple, stored as JSON array of user IDs
            $table->json('pic_project_internal_ids')->nullable()->after('pic_leads_project_id');

            // PIC Account Manager — single
            $table->unsignedBigInteger('pic_am_id')->nullable()->after('pic_project_internal_ids');
        });
    }

    public function down(): void
    {
        Schema::table('media_plans', function (Blueprint $table) {
            $table->dropColumn(['pic_leads_project_id', 'pic_project_internal_ids', 'pic_am_id']);
            $table->renameColumn('pic_sales_bd_id', 'pic_campaign_id');
        });
    }
};
