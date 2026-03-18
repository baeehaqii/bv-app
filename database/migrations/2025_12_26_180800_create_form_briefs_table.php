<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('form_briefs', function (Blueprint $table) {
            $table->id();

            // Token unik untuk akses public (client portal)
            $table->string('token', 64)->unique()->index();

            // Relasi ke sales activity (auto-link saat status briefing)
            $table->foreignId('bv_sales_id')->nullable()->constrained('bv_sales')->nullOnDelete();

            // Relasi ke campaign (opsional, bisa dibuat dulu sebelum campaign)
            $table->unsignedBigInteger('campaign_id')->nullable()->index();

            // Relasi ke client
            $table->foreignId('client_id')->nullable()->constrained('data_clients')->nullOnDelete();

            // === KOL Needs Fields (sesuai format spreadsheet) ===

            // Info dasar
            $table->string('title');
            $table->string('brand')->nullable();           // Brand (nama brand dari client)
            $table->string('client_status')->nullable();   // Client Status (Direct / Agency / Another Agency)
            $table->string('pic')->nullable();             // PIC (Person In Charge dari client)
            $table->string('campaign_name')->nullable();   // Campaign Name
            $table->string('timeline')->nullable();        // Timeline (e.g. "January - February 2026")
            $table->text('campaign_objective')->nullable(); // Campaign Objective

            // Criteria of KOL (rich text, bisa ada Main KOL & Macro KOL dsb)
            $table->text('criteria_of_kol')->nullable();

            // SOW (Scope of Work)
            $table->text('sow')->nullable();

            // Budget per tier
            $table->string('budget_main_kol')->nullable();  // e.g. "1M - 1,5M"
            $table->string('budget_macro_kol')->nullable(); // e.g. "250JT - 300JT"

            // Deadline
            $table->string('deadline')->nullable();  // e.g. "January 2026"

            // Status
            $table->string('status')->default('draft'); // draft, submitted, reviewed, approved, revision

            // Sheet Links
            $table->string('sheet_link_internal')->nullable(); // Link spreadsheet internal
            $table->string('sheet_link_external')->nullable(); // Link spreadsheet external

            // Catatan tambahan
            $table->text('additional_notes')->nullable();

            // Attachments (JSON array of file paths)
            $table->json('attachments')->nullable();

            // Siapa yang submit (jika dari client portal)
            $table->string('submitted_by_name')->nullable();
            $table->string('submitted_by_email')->nullable();
            $table->timestamp('submitted_at')->nullable();

            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->text('review_notes')->nullable();

            $table->timestamps();
        });

        Schema::table('bv_sales', function (Blueprint $table) {
            $table->foreign('form_brief_id')->references('id')->on('form_briefs')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('bv_sales', function (Blueprint $table) {
            $table->dropForeign(['form_brief_id']);
        });

        Schema::dropIfExists('form_briefs');
    }
};
