<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('form_briefs', function (Blueprint $table) {
            // Kolom budget tunggal (bigInteger agar cukup untuk nilai ratusan juta)
            $table->unsignedBigInteger('budget')->nullable()->after('sow');

            // Ubah deadline dari string ke date
            $table->date('deadline_date')->nullable()->after('budget');
        });

        // Migrasi data: konversi budget_main_kol ke budget (ambil nilai terbesar jika ada keduanya)
        if (DB::getDriverName() === 'mysql') {
            DB::statement("
                UPDATE form_briefs
                SET budget = GREATEST(
                    COALESCE(CAST(budget_main_kol AS UNSIGNED), 0),
                    COALESCE(CAST(budget_macro_kol AS UNSIGNED), 0)
                )
                WHERE budget_main_kol IS NOT NULL OR budget_macro_kol IS NOT NULL
            ");
        } else {
            DB::statement("
                UPDATE form_briefs
                SET budget = CASE
                    WHEN CAST(budget_main_kol AS INTEGER) > CAST(budget_macro_kol AS INTEGER)
                        THEN CAST(budget_main_kol AS INTEGER)
                    ELSE CAST(budget_macro_kol AS INTEGER)
                END
                WHERE budget_main_kol IS NOT NULL OR budget_macro_kol IS NOT NULL
            ");
        }

        Schema::table('form_briefs', function (Blueprint $table) {
            // Hapus kolom budget lama setelah migrasi data
            $table->dropColumn(['budget_main_kol', 'budget_macro_kol']);
        });
    }

    public function down(): void
    {
        Schema::table('form_briefs', function (Blueprint $table) {
            $table->string('budget_main_kol')->nullable();
            $table->string('budget_macro_kol')->nullable();
            $table->dropColumn(['budget', 'deadline_date']);
        });
    }
};
