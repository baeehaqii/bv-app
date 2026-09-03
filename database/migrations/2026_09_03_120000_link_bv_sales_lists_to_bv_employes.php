<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    /**
     * Baris bv_sales_lists lama dibuat BvTeamSeeder dengan kunci nama depan
     * ("Gressita") dan user_id, sebelum tabel karyawan terisi. BvEmployeObserver
     * mencocokkan pakai bv_employe_id, jadi tanpa tautan ini satu orang bisa
     * punya dua baris PIC begitu sinkronisasi divisi dinyalakan.
     *
     * Pencocokannya lewat user_id — bukan nama — karena nama depan di sales list
     * tidak sama dengan nama lengkap di data karyawan.
     */
    public function up(): void
    {
        $employeByUser = DB::table('bv_employes')
            ->whereNotNull('user_id')
            ->pluck('id', 'user_id');

        DB::table('bv_sales_lists')
            ->whereNull('bv_employe_id')
            ->whereNotNull('user_id')
            ->get(['id', 'user_id'])
            ->each(function ($row) use ($employeByUser) {
                if (! isset($employeByUser[$row->user_id])) {
                    return;
                }

                DB::table('bv_sales_lists')
                    ->where('id', $row->id)
                    ->update(['bv_employe_id' => $employeByUser[$row->user_id]]);
            });
    }

    public function down(): void
    {
        // Hanya melepas tautan yang cocok user_id-nya; baris yang memang dibuat
        // observer (nama_sales = nama lengkap) tidak ikut disentuh.
        DB::table('bv_sales_lists as sl')
            ->join('bv_employes as e', 'e.id', '=', 'sl.bv_employe_id')
            ->whereColumn('e.user_id', 'sl.user_id')
            ->whereColumn('e.nama_lengkap', '!=', 'sl.nama_sales')
            ->update(['sl.bv_employe_id' => null]);
    }
};
