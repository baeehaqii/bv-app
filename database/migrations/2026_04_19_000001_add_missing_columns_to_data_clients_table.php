<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('data_clients', function (Blueprint $table) {
            if (!Schema::hasColumn('data_clients', 'parent_brand')) {
                $table->string('parent_brand')->nullable()->after('agency_name');
            }
            if (!Schema::hasColumn('data_clients', 'instagram')) {
                $table->string('instagram')->nullable()->after('parent_brand');
            }
            if (!Schema::hasColumn('data_clients', 'tiktok')) {
                $table->string('tiktok')->nullable()->after('instagram');
            }
            if (!Schema::hasColumn('data_clients', 'youtube')) {
                $table->string('youtube')->nullable()->after('tiktok');
            }
            if (!Schema::hasColumn('data_clients', 'threads')) {
                $table->string('threads')->nullable()->after('youtube');
            }
            if (!Schema::hasColumn('data_clients', 'top')) {
                $table->integer('top')->nullable()->comment('Term of Payment in days')->after('threads');
            }
        });
    }

    public function down(): void
    {
        Schema::table('data_clients', function (Blueprint $table) {
            $table->dropColumn(array_filter([
                Schema::hasColumn('data_clients', 'parent_brand') ? 'parent_brand' : null,
                Schema::hasColumn('data_clients', 'instagram') ? 'instagram' : null,
                Schema::hasColumn('data_clients', 'tiktok') ? 'tiktok' : null,
                Schema::hasColumn('data_clients', 'youtube') ? 'youtube' : null,
                Schema::hasColumn('data_clients', 'threads') ? 'threads' : null,
                Schema::hasColumn('data_clients', 'top') ? 'top' : null,
            ]));
        });
    }
};
