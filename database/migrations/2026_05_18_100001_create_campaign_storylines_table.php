<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('campaign_storylines', function (Blueprint $table) {
            $table->id();

            $table->foreignId('bv_campaign_id')
                ->constrained('bv_campaigns')
                ->cascadeOnDelete();

            $table->string('kol_name');
            $table->string('platform')->nullable();
            $table->string('sow')->nullable();
            $table->string('content_angle')->nullable();
            $table->text('caption_draft')->nullable();
            $table->text('key_message')->nullable();
            $table->date('posting_deadline')->nullable();
            $table->enum('status', [
                'draft',
                'waiting_approval',
                'revision',
                'approved',
                'posted',
            ])->default('draft');
            $table->text('notes')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('campaign_storylines');
    }
};
