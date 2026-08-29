<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::table('master_margins')->exists()) {
            return;
        }

        DB::table('master_margins')->insert([
            'name' => 'Default (sheet KOL List)',
            'min_amount' => 0,
            'max_amount' => null,
            'margin_percent' => 50,
            'order' => 1,
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function down(): void
    {
        DB::table('master_margins')->where('name', 'Default (sheet KOL List)')->delete();
    }
};
