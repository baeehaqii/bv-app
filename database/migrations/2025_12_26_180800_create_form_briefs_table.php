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

            // Relasi ke campaign (opsional, bisa dibuat dulu sebelum campaign)
            $table->unsignedBigInteger('campaign_id')->nullable()->index();

            // Relasi ke client
            $table->foreignId('client_id')->nullable()->constrained('data_clients')->nullOnDelete();

            // Info dasar brief
            $table->string('title');
            $table->string('brand_name')->nullable();
            $table->string('product_name')->nullable();
            $table->text('campaign_objective')->nullable();
            $table->text('target_audience')->nullable();
            $table->text('key_message')->nullable();
            $table->text('mandatory_content')->nullable();
            $table->text('do_and_dont')->nullable();
            $table->text('reference_links')->nullable();
            $table->text('hashtags')->nullable();
            $table->text('mentions')->nullable();

            // Timeline
            $table->date('content_deadline')->nullable();
            $table->date('posting_date')->nullable();

            // Budget
            $table->decimal('budget', 18, 2)->default(0);
            $table->text('budget_notes')->nullable();

            // Attachments (JSON array of file paths)
            $table->json('attachments')->nullable();

            // Notes & tambahan
            $table->text('additional_notes')->nullable();

            // Status brief
            $table->string('status')->default('draft'); // draft, submitted, reviewed, approved, revision

            // Siapa yang submit (jika dari client portal)
            $table->string('submitted_by_name')->nullable();
            $table->string('submitted_by_email')->nullable();
            $table->timestamp('submitted_at')->nullable();

            // Internal review
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->text('review_notes')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('form_briefs');
    }
};
