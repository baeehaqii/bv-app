<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        // Bersihkan duplikat lama: simpan baris terbaru per (username, channel),
        // hapus sisanya agar unique index dapat dibuat.
        $duplicateIds = DB::table('data_kols')
            ->select('id')
            ->whereNotNull('username')
            ->whereNotIn('id', function ($query) {
                $query->selectRaw('MAX(id)')
                    ->from('data_kols')
                    ->whereNotNull('username')
                    ->groupBy('username', 'channel');
            })
            ->pluck('id');

        if ($duplicateIds->isNotEmpty()) {
            DB::table('data_kols')->whereIn('id', $duplicateIds)->delete();
        }

        Schema::table('data_kols', function (Blueprint $table) {
            $table->unique(['username', 'channel'], 'data_kols_username_channel_unique');
        });
    }

    public function down(): void
    {
        Schema::table('data_kols', function (Blueprint $table) {
            $table->dropUnique('data_kols_username_channel_unique');
        });
    }
};
