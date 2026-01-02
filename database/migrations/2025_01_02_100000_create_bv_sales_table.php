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
            $table->foreignId('bv_sales_list_id')->nullable()->constrained('bv_sales_lists')->nullOnDelete();
            $table->string('event_name'); // Event/Campaign name - displayed as bold title in Kanban
            $table->string('company_name')->nullable();
            $table->json('campaign_items')->nullable(); // Multiple items stored as JSON
            $table->decimal('deal_value', 15, 2)->default(0);
            $table->decimal('margin', 10, 2)->default(0);
            $table->string('campaign_periode')->nullable();
            $table->year('campaign_year')->nullable();
            $table->date('close_date')->nullable();
            $table->text('comments')->nullable();
            $table->text('detail')->nullable();
            $table->string('status')->default('pitching');
            $table->flowforgePositionColumn('position');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bv_sales');
    }
};
