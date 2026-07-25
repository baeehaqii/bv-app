<?php

use App\Models\DataClient;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        // Backfill: agency_brands lama belum punya baris direct brand-nya
        // (mis. "Page by Page" di bawah We Thrive).
        DataClient::where('type', 'agency')
            ->whereNotNull('agency_brands')
            ->each(fn (DataClient $agency) => $agency->syncAgencyBrands());
    }

    public function down(): void
    {
        // Tidak dibalik: baris brand hasil sync bisa jadi sudah dipakai campaign.
    }
};
