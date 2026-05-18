<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('bv_campaign_kols', function (Blueprint $table) {
            // Identitas KOL
            $table->string('kol_profile_url')->nullable()->after('username'); // Link profil TikTok/IG
            $table->string('tier')->nullable()->after('kol_profile_url');     // Macro, Mega, Micro, Nano

            // Brief Tracker — proses sebelum konten di-approve
            $table->string('brief_status')->default('draft')->after('status');
            // draft → waiting_review → revision → approved
            // approved = auto masuk KOL Performance

            $table->date('visit_date')->nullable()->after('brief_status');
            $table->string('visit_status')->nullable()->after('visit_date');  // done, scheduled, pending

            $table->string('content_drive_link')->nullable()->after('visit_status'); // Link Google Drive konten

            $table->text('feedback')->nullable()->after('content_drive_link');       // Feedback round 1
            $table->string('revision_link')->nullable()->after('feedback');          // Link konten setelah revisi
            $table->text('feedback_2')->nullable()->after('revision_link');          // Feedback round 2

            $table->date('posting_date')->nullable()->after('feedback_2');           // Tanggal posting planned
        });
    }

    public function down(): void
    {
        Schema::table('bv_campaign_kols', function (Blueprint $table) {
            $table->dropColumn([
                'kol_profile_url',
                'tier',
                'brief_status',
                'visit_date',
                'visit_status',
                'content_drive_link',
                'feedback',
                'revision_link',
                'feedback_2',
                'posting_date',
            ]);
        });
    }
};
