<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    /**
     * pic_project_internal_ids sebelumnya menyimpan ID bv_sales_lists.
     * Sekarang field KOL Specialist mengambil dari data karyawan (bv_employes),
     * jadi data lama dikonversi via bv_sales_lists.bv_employe_id.
     */
    public function up(): void
    {
        $this->convert(
            DB::table('bv_sales_lists')
                ->whereNotNull('bv_employe_id')
                ->pluck('bv_employe_id', 'id')
        );
    }

    public function down(): void
    {
        $this->convert(
            DB::table('bv_sales_lists')
                ->whereNotNull('bv_employe_id')
                ->pluck('id', 'bv_employe_id')
        );
    }

    private function convert(\Illuminate\Support\Collection $map): void
    {
        DB::table('media_plans')
            ->whereNotNull('pic_project_internal_ids')
            ->get(['id', 'pic_project_internal_ids'])
            ->each(function ($plan) use ($map) {
                $ids = json_decode($plan->pic_project_internal_ids, true);
                if (!is_array($ids)) {
                    return;
                }

                // ID tanpa pasangan dibuang — tidak valid pada skema tujuan
                $converted = collect($ids)
                    ->map(fn($id) => $map[$id] ?? null)
                    ->filter()
                    ->values()
                    ->all();

                DB::table('media_plans')
                    ->where('id', $plan->id)
                    ->update(['pic_project_internal_ids' => json_encode($converted)]);
            });
    }
};
