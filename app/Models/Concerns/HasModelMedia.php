<?php

namespace App\Models\Concerns;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Relations\MorphToMany;
use Illuminate\Support\Facades\DB;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

/**
 * Adds cross-cutting media reuse via the model_media pivot. A single Media row can be
 * attached to many (model, collection) pairs without being duplicated.
 *
 * Sits alongside Spatie's InteractsWithMedia: that trait stays responsible for the
 * Media row's storage lifecycle (upload, conversions, file delete on cascade), while
 * this trait owns "which model uses which media in which collection".
 */
trait HasModelMedia
{
    public function modelMedia(): MorphToMany
    {
        return $this->morphToMany(Media::class, 'model', 'model_media')
            ->withPivot(['collection_name', 'order_column'])
            ->withTimestamps()
            ->orderBy('model_media.order_column');
    }

    public function attachedMedia(string $collection): Collection
    {
        return $this->modelMedia()
            ->wherePivot('collection_name', $collection)
            ->get();
    }

    public function firstAttachedMedia(string $collection): ?Media
    {
        return $this->modelMedia()
            ->wherePivot('collection_name', $collection)
            ->first();
    }

    public function hasAttachedMedia(string $collection): bool
    {
        return $this->modelMedia()
            ->wherePivot('collection_name', $collection)
            ->exists();
    }

    public function firstAttachedMediaUrl(string $collection, string $conversion = ''): string
    {
        $media = $this->firstAttachedMedia($collection);
        if (! $media) {
            return '';
        }

        if ($conversion === '') {
            return $media->getUrl();
        }

        try {
            return $media->getUrl($conversion);
        } catch (\Throwable $e) {
            // The pivot lets a Media row be attached to (model, collection) pairs other
            // than its native owner — and conversions are registered on the native
            // owner's collection, not the pivot's. If the requested conversion isn't
            // registered for this Media (e.g. picked from a User-owned Library item
            // whose registerMediaConversions only generates an avatar size), fall back
            // to the original URL rather than 500ing the page.
            return $media->getUrl();
        }
    }

    public function attachMedia(int $mediaId, string $collection, ?int $order = null): void
    {
        $type = $this->getMorphClass();
        $id   = $this->getKey();

        $existing = DB::table('model_media')
            ->where('media_id', $mediaId)
            ->where('model_type', $type)
            ->where('model_id', $id)
            ->where('collection_name', $collection)
            ->exists();

        if ($existing) {
            if ($order !== null) {
                DB::table('model_media')
                    ->where('media_id', $mediaId)
                    ->where('model_type', $type)
                    ->where('model_id', $id)
                    ->where('collection_name', $collection)
                    ->update(['order_column' => $order, 'updated_at' => now()]);
            }
            return;
        }

        $order ??= 1 + (int) DB::table('model_media')
            ->where('model_type', $type)
            ->where('model_id', $id)
            ->where('collection_name', $collection)
            ->max('order_column');

        DB::table('model_media')->insert([
            'media_id'        => $mediaId,
            'model_type'      => $type,
            'model_id'        => $id,
            'collection_name' => $collection,
            'order_column'    => $order,
            'created_at'      => now(),
            'updated_at'      => now(),
        ]);
    }

    public function detachAttachedCollection(string $collection): void
    {
        DB::table('model_media')
            ->where('model_type', $this->getMorphClass())
            ->where('model_id', $this->getKey())
            ->where('collection_name', $collection)
            ->delete();
    }

    public function syncAttachedMedia(array $mediaIds, string $collection): void
    {
        $this->detachAttachedCollection($collection);
        foreach (array_values($mediaIds) as $i => $mediaId) {
            $this->attachMedia((int) $mediaId, $collection, $i + 1);
        }
    }
}
