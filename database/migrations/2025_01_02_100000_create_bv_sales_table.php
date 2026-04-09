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
        Schema::create('bv_sales', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('form_brief_id')->nullable()->index();
            $table->foreignId('bv_sales_list_id')->nullable()->constrained('bv_sales_lists')->nullOnDelete();
            $table->string('event_name');
            $table->string('company_name')->nullable();
            $table->json('campaign_items')->nullable();
            $table->decimal('budget_propose', 15, 2)->default(0);
            $table->decimal('deal_value', 15, 2)->default(0);
            $table->decimal('margin', 10, 2)->default(0);
            $table->string('campaign_periode')->nullable();
            $table->unsignedTinyInteger('campaign_month')->nullable();
            $table->date('campaign_date')->nullable();
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();
            $table->year('campaign_year')->nullable();
            $table->date('close_date')->nullable();
            $table->text('comments')->nullable();
            $table->text('detail')->nullable();
            $table->string('pic_media_plan')->nullable();
            $table->json('brief_files')->nullable();
            $table->string('brief_link')->nullable();
            $table->date('brief_submit_date')->nullable();
            $table->string('status')->default('not_started');
            $table->text('meeting_notes')->nullable();
            $table->json('quotation_sign')->nullable();
            $table->flowforgePositionColumn('position');
            $table->timestamps();
        });

        Schema::create('bv_sales_comments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('bv_sales_id')->constrained('bv_sales')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('parent_id')->nullable()->constrained('bv_sales_comments')->cascadeOnDelete();
            $table->text('body');
            $table->json('attachments')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bv_sales_comments');
        Schema::dropIfExists('bv_sales');
    }
};
