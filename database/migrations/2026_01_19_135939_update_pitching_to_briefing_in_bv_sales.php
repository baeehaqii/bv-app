<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     * Update all records with 'pitching' status to 'briefing'
     */
    public function up(): void
    {
        DB::table('bv_sales')
            ->where('status', 'pitching')
            ->update(['status' => 'briefing']);
    }

    /**
     * Reverse the migrations.
     * Revert 'briefing' back to 'pitching' (optional, for rollback)
     */
    public function down(): void
    {
        //
    }
};

