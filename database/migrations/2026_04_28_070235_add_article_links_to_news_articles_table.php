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
        Schema::table('news_articles', function (Blueprint $table) {
            $table->boolean('link_top_active')->default(false)->after('geo_text');
            $table->json('link_top_text')->nullable()->after('link_top_active');
            $table->string('link_top_url', 2048)->nullable()->after('link_top_text');

            $table->boolean('link_bottom_active')->default(false)->after('link_top_url');
            $table->json('link_bottom_text')->nullable()->after('link_bottom_active');
            $table->string('link_bottom_url', 2048)->nullable()->after('link_bottom_text');
        });
    }

    public function down(): void
    {
        Schema::table('news_articles', function (Blueprint $table) {
            $table->dropColumn([
                'link_top_active',
                'link_top_text',
                'link_top_url',
                'link_bottom_active',
                'link_bottom_text',
                'link_bottom_url',
            ]);
        });
    }
};
