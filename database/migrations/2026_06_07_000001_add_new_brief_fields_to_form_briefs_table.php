<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('form_briefs', function (Blueprint $table) {
            $table->string('product')->nullable()->after('campaign_name');
            $table->date('date_issued')->nullable()->after('product');
            $table->date('delivery_date')->nullable()->after('date_issued');
            $table->string('supporting_doc')->nullable()->after('delivery_date');
            $table->text('background')->nullable()->after('campaign_objective');
            $table->text('cta')->nullable()->after('background');
            $table->text('target_audience')->nullable()->after('cta');
            $table->text('key_messages')->nullable()->after('target_audience');
            $table->text('request_kol')->nullable()->after('key_messages');
            $table->text('persona_kol')->nullable()->after('request_kol');
            $table->text('brief_do')->nullable()->after('persona_kol');
            $table->text('brief_dont')->nullable()->after('brief_do');
            $table->text('kpi')->nullable()->after('brief_dont');
            $table->string('prepared_by')->nullable()->after('additional_notes');
            $table->string('account_name')->nullable()->after('prepared_by');
        });
    }

    public function down(): void
    {
        Schema::table('form_briefs', function (Blueprint $table) {
            $table->dropColumn([
                'product',
                'date_issued',
                'delivery_date',
                'supporting_doc',
                'background',
                'cta',
                'target_audience',
                'key_messages',
                'request_kol',
                'persona_kol',
                'brief_do',
                'brief_dont',
                'kpi',
                'prepared_by',
                'account_name',
            ]);
        });
    }
};
