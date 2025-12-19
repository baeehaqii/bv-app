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
        Schema::create('master_pphs', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique(); // e.g., 'Pribadi', 'PT Non PKP', 'PT PKP', 'CV'
            $table->string('entity_type'); // 'Pribadi', 'PT', 'CV'
            $table->decimal('coefficient', 5, 3); // PPh Coefficient (e.g., 0.975, 0.98, 0.995)
            $table->boolean('include_ppn')->default(false); // For PT PKP (PPN 11%)
            $table->decimal('ppn_percent', 5, 2)->nullable(); // PPN percentage if applicable
            $table->text('description')->nullable();
            $table->integer('order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('master_pphs');
    }
};
