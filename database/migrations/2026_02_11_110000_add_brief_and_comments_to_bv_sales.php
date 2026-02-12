<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        // Add brief fields to bv_sales
        Schema::table('bv_sales', function (Blueprint $table) {
            $table->json('brief_files')->nullable()->after('detail');
            $table->string('brief_link')->nullable()->after('brief_files');
            $table->date('brief_submit_date')->nullable()->after('brief_link');
        });

        // Create comments table
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

    public function down(): void
    {
        Schema::dropIfExists('bv_sales_comments');

        Schema::table('bv_sales', function (Blueprint $table) {
            $table->dropColumn(['brief_files', 'brief_link', 'brief_submit_date']);
        });
    }
};
