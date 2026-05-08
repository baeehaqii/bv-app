<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('kol_rate_cards', function (Blueprint $table) {
            $table->id();
            $table->foreignId('data_kol_id')->constrained('data_kols')->cascadeOnDelete();
            $table->string('channel');
            $table->string('sow')->nullable();
            $table->decimal('rate', 15, 2)->nullable();
            $table->string('file_path')->nullable();
            $table->date('valid_from')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('kol_rate_cards');
    }
};
