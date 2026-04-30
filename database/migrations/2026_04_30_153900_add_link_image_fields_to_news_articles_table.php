<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('news_articles', function (Blueprint $table) {
            $table->boolean('link_top_show_image')->default(false)->after('link_top_media_id');
            $table->string('link_top_image_source', 16)->nullable()->after('link_top_show_image');
            $table->foreignId('link_top_image_media_id')->nullable()->after('link_top_image_source')
                ->constrained('media')->nullOnDelete();

            $table->boolean('link_bottom_show_image')->default(false)->after('link_bottom_media_id');
            $table->string('link_bottom_image_source', 16)->nullable()->after('link_bottom_show_image');
            $table->foreignId('link_bottom_image_media_id')->nullable()->after('link_bottom_image_source')
                ->constrained('media')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('news_articles', function (Blueprint $table) {
            $table->dropForeign(['link_top_image_media_id']);
            $table->dropForeign(['link_bottom_image_media_id']);
            $table->dropColumn([
                'link_top_show_image',
                'link_top_image_source',
                'link_top_image_media_id',
                'link_bottom_show_image',
                'link_bottom_image_source',
                'link_bottom_image_media_id',
            ]);
        });
    }
};
