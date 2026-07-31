<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Akun kas & bank. Tanpa ini saldo tidak bisa dijawab: arus kas hanya tahu
 * uang masuk/keluar, bukan berapa yang tersisa di mana.
 * `opening_balance` = saldo awal saat akun mulai dicatat di sistem.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('finance_accounts', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('type', 20)->default('bank'); // kas | bank | e-wallet
            $table->string('bank_name')->nullable();
            $table->string('account_number')->nullable();
            $table->string('account_holder')->nullable();
            $table->decimal('opening_balance', 15, 2)->default(0);
            $table->date('opening_date')->nullable();
            $table->boolean('is_default')->default(false);
            $table->boolean('is_active')->default(true);
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('finance_accounts');
    }
};
