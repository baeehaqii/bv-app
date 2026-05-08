<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('bv_employes', function (Blueprint $table) {
            $table->foreignId('position_id')->nullable()->constrained()->nullOnDelete()->after('divis');
            $table->foreignId('reports_to_id')->nullable()->after('position_id')
                ->references('id')->on('bv_employes')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('bv_employes', function (Blueprint $table) {
            $table->dropForeign(['position_id']);
            $table->dropForeign(['reports_to_id']);
            $table->dropColumn(['position_id', 'reports_to_id']);
        });
    }
};
