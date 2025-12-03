<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('data_clients', function (Blueprint $table) {
            $table->id();
            $table->string('nama_brand');
            $table->string('produk')->nullable();
            $table->string('category')->nullable();
            $table->string('priority')->nullable();
            $table->string('website')->nullable();
            $table->string('nama_pic')->nullable();
            $table->string('role_pic')->nullable();
            $table->string('email_pic')->nullable();
            $table->string('status')->nullable();
            $table->date('date_outreach')->nullable();
            $table->date('date_follow_up')->nullable();
            $table->text('notes')->nullable();
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
