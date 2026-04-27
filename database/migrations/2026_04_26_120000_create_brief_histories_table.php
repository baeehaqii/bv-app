<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('brief_histories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('bv_sales_id')->constrained('bv_sales')->cascadeOnDelete();
            $table->string('type'); // 'file' | 'link'
            $table->string('file_path')->nullable();
            $table->string('link_url')->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('uploaded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['bv_sales_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('brief_histories');
    }
};
