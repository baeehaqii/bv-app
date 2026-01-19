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
        Schema::table('bv_campaigns', function (Blueprint $table) {
            // Add client_id foreign key (for brand)
            $table->foreignId('client_id')->nullable()->after('id')->constrained('data_clients')->nullOnDelete();

            // Add campaign description
            $table->text('campaign_description')->nullable()->after('campaign_name');

            // Add campaign banner/image
            $table->string('campaign_image')->nullable()->after('campaign_description');

            // Add media platforms (JSON array for multiple platforms)
            $table->json('media_platforms')->nullable()->after('campaign_image');

            // Add campaign type
            $table->string('campaign_type')->default('regular')->after('media_platforms');

            // Add total cost
            $table->decimal('total_cost', 15, 2)->default(0)->after('campaign_type');

            // Add retrieve option and template
            $table->string('retrieve_option')->nullable()->after('total_cost');
            $table->string('retrieve_template')->nullable()->after('retrieve_option');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('bv_campaigns', function (Blueprint $table) {
            $table->dropForeign(['client_id']);
            $table->dropColumn([
                'client_id',
                'campaign_description',
                'campaign_image',
                'media_platforms',
                'campaign_type',
                'total_cost',
                'retrieve_option',
                'retrieve_template',
            ]);
        });
    }
};
