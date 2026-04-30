<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('news_articles', function (Blueprint $table) {
            $table->foreignId('link_top_media_id')->nullable()->after('link_top_url')
                ->constrained('media')->nullOnDelete();
            $table->foreignId('link_bottom_media_id')->nullable()->after('link_bottom_url')
                ->constrained('media')->nullOnDelete();
        });

        // Backfill: existing Spatie-collection rows → set FK on owning article.
        // Idempotent via whereNull guard. Ownership is left untouched so legacy
        // reader code keeps working until the cutover lands.
        DB::table('media')
            ->where('model_type', \App\Models\NewsArticle::class)
            ->whereIn('collection_name', ['link_top_file', 'link_bottom_file'])
            ->orderBy('id')
            ->each(function ($m) {
                $col = $m->collection_name === 'link_top_file'
                    ? 'link_top_media_id'
                    : 'link_bottom_media_id';

                DB::table('news_articles')
                    ->where('id', $m->model_id)
                    ->whereNull($col)
                    ->update([$col => $m->id]);
            });
    }

    public function down(): void
    {
        Schema::table('news_articles', function (Blueprint $table) {
            $table->dropForeign(['link_top_media_id']);
            $table->dropForeign(['link_bottom_media_id']);
            $table->dropColumn(['link_top_media_id', 'link_bottom_media_id']);
        });
    }
};
