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
        Schema::create('data_clients', function (Blueprint $table) {
            $table->id();
            $table->flowforgePositionColumn('position');
            $table->string('nama_brand');
            $table->string('type')->default('direct');
            $table->string('category')->nullable();
            $table->string('priority')->nullable();
            $table->string('website')->nullable();
            $table->foreignId('pic_internal_sales_id')->nullable()->constrained('bv_sales_lists')->nullOnDelete()->comment('PIC Internal (Sales) dari tim BV');
            $table->string('nama_pic')->nullable();
            $table->string('role_pic')->nullable();
            $table->string('email_pic')->nullable();
            $table->json('pics')->nullable();
            $table->string('status')->nullable();
            $table->date('date_outreach')->nullable();
            $table->date('date_follow_up')->nullable();
            $table->string('status_client')->nullable();
            $table->text('notes')->nullable();
            $table->string('account_owner')->nullable();
            $table->json('agency_name')->nullable()->comment('Nama-nama agensi (JSON array, untuk tipe agency)');
            $table->string('parent_brand')->nullable();
            $table->string('instagram')->nullable();
            $table->string('tiktok')->nullable();
            $table->string('youtube')->nullable();
            $table->string('threads')->nullable();
            $table->string('nama_pic_2')->nullable()->comment('PIC ke-2: Nama');
            $table->string('role_pic_2')->nullable()->comment('PIC ke-2: Role/Jabatan');
            $table->string('email_pic_2')->nullable()->comment('PIC ke-2: Email');
            $table->integer('top')->nullable()->comment('Term of Payment in days');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('data_clients');
    }
};
