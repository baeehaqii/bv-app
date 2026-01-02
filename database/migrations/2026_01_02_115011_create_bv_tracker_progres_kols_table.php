<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('bv_tracker_progres_kols', function (Blueprint $table) {
            $table->id();

            // Foreign key ke media_plan_kols
            $table->foreignId('media_plan_kol_id')->constrained('media_plan_kols')->cascadeOnDelete();

            $table->string('nama_brand')->nullable();
            $table->string('timeline')->nullable();
            $table->string('kol_approve')->nullable();
            $table->string('username')->nullable();
            $table->string('link')->nullable();
            $table->string('status')->nullable();

            // Draft Storyline
            $table->string('draft_storyline')->nullable(); // Status/file draft storyline
            $table->text('feedback_draft_storyline')->nullable(); // Feedback client untuk draft storyline

            // Draft Video
            $table->string('draft_video')->nullable(); // Status/file draft video
            $table->text('feedback_draft_video')->nullable(); // Feedback client untuk draft video

            // Draft Revisi 1
            $table->string('draft_revisi_1')->nullable(); // Status/file draft revisi 1
            $table->text('feedback_draft_revisi_1')->nullable(); // Feedback client untuk draft revisi 1

            // Draft Revisi 2
            $table->string('draft_revisi_2')->nullable(); // Status/file draft revisi 2
            $table->text('feedback_draft_revisi_2')->nullable(); // Feedback client untuk draft revisi 2

            // Caption
            $table->text('caption')->nullable(); // Caption untuk posting
            $table->text('feedback_caption')->nullable(); // Feedback client untuk caption

            // Posting
            $table->string('link_post')->nullable(); // Link posting yang sudah live
            $table->date('tanggal_posting')->nullable(); // Tanggal posting

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bv_tracker_progres_kols');
    }
};
