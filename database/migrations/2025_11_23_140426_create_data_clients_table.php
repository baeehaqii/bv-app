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
            $table->string('nama_brand')->nullable();
            $table->string('type')->default('direct');
            $table->boolean('has_agency')->default(false)->comment('Direct brand yang juga memiliki agency');
            $table->foreignId('agency_client_id')->nullable()->constrained('data_clients')->nullOnDelete()->comment('Referensi ke agency existing (self-ref)');
            $table->string('category')->nullable();
            $table->string('priority')->nullable();
            $table->string('website')->nullable();
            $table->foreignId('pic_internal_sales_id')->nullable()->constrained('bv_sales_lists')->nullOnDelete()->comment('PIC Internal (Sales) dari tim BV');
            $table->json('pics')->nullable();
            $table->json('agency_brands')->nullable()->comment('Daftar brand yang di-handle oleh agency (JSON array)');
            $table->json('pic_clients')->nullable()->comment('PIC client (berlaku untuk agency & direct brand, multiple entries)');
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
