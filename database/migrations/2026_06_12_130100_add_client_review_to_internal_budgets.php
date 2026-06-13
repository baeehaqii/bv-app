<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Link Review Client untuk Media Plan External.
 *
 * internal_budgets        : token publik + flag + waktu submit client
 * internal_budget_items   : keputusan & feedback dari client (terpisah dari
 *                           status/rejection_notes/nego_notes internal BV)
 */
return new class extends Migration {
    public function up(): void
    {
        Schema::table('internal_budgets', function (Blueprint $table) {
            $table->string('review_token', 64)->nullable()->unique()->after('rejection_notes');
            $table->boolean('review_is_public')->default(false)->after('review_token');
            $table->timestamp('review_submitted_at')->nullable()->after('review_is_public');
        });

        Schema::table('internal_budget_items', function (Blueprint $table) {
            // Keputusan client: 'approved' (pakai) | 'rejected' (tidak) | null (belum)
            $table->string('client_choice', 16)->nullable()->after('nego_notes');
            $table->text('client_feedback')->nullable()->after('client_choice');
        });
    }

    public function down(): void
    {
        Schema::table('internal_budgets', function (Blueprint $table) {
            $table->dropColumn(['review_token', 'review_is_public', 'review_submitted_at']);
        });

        Schema::table('internal_budget_items', function (Blueprint $table) {
            $table->dropColumn(['client_choice', 'client_feedback']);
        });
    }
};
