<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Mirror every Media row that has a real consumer (model_type != User and not null) into
     * the new model_media pivot, preserving its original collection_name and order_column.
     *
     * After this runs, the application reads exclusively through the pivot, so existing pages
     * keep rendering their images without re-pick. User-owned media (Library) intentionally
     * gets no pivot row — those are unconsumed library items.
     */
    public function up(): void
    {
        $userClass = \App\Models\User::class;

        DB::table('media')
            ->whereNotNull('model_type')
            ->where('model_type', '!=', $userClass)
            ->orderBy('id')
            ->chunkById(500, function ($rows) {
                $insert = [];
                $now = now();
                foreach ($rows as $m) {
                    $insert[] = [
                        'media_id'        => $m->id,
                        'model_type'      => $m->model_type,
                        'model_id'        => $m->model_id,
                        'collection_name' => $m->collection_name,
                        'order_column'    => $m->order_column ?? 0,
                        'created_at'      => $now,
                        'updated_at'      => $now,
                    ];
                }
                if ($insert) {
                    DB::table('model_media')->insertOrIgnore($insert);
                }
            });
    }

    public function down(): void
    {
        DB::table('model_media')->truncate();
    }
};
