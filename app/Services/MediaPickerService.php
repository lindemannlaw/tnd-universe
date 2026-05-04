<?php

namespace App\Services;

use App\Models\Concerns\HasModelMedia;
use Illuminate\Http\Request;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class MediaPickerService
{
    /**
     * Resolve the image input for `$field` on a model that uses the model_media pivot
     * (via the HasModelMedia trait alongside Spatie's InteractsWithMedia).
     *
     * Order of precedence per request:
     *   1. `{$field}_media_id` hidden input → attach the picked Media via the pivot only.
     *      The source Media row is shared, never copied.
     *   2. uploaded file under `$field` → addMediaFromRequest as before (Spatie owns the
     *      new row + runs conversions registered on $model), then mirror the attachment
     *      into the pivot.
     *   3. nothing → leave the existing collection untouched.
     *
     * Both branches first detach any existing pivot rows for this (model, collection) and
     * (only on upload) clear the Spatie-native collection so $model never accumulates
     * stale rows it owns directly.
     *
     * Returns the Media that was attached, or null if nothing happened.
     */
    public function applyToCollection(
        HasMedia $model,
        string $collection,
        Request $request,
        string $field,
        bool $clearCollectionFirst = true,
    ): ?Media {
        $this->assertHasModelMedia($model);

        $mediaIdInput = $field . '_media_id';

        if ($request->filled($mediaIdInput)) {
            $sourceMedia = Media::find((int) $request->input($mediaIdInput));
            if (! $sourceMedia) {
                return null;
            }

            if ($clearCollectionFirst) {
                $model->detachAttachedCollection($collection);
            }
            $model->attachMedia($sourceMedia->id, $collection);

            return $sourceMedia;
        }

        if ($request->hasFile($field)) {
            if ($clearCollectionFirst) {
                $model->detachAttachedCollection($collection);
                $model->clearMediaCollection($collection);
            }

            $newMedia = $model->addMediaFromRequest($field)->toMediaCollection($collection);
            $model->attachMedia($newMedia->id, $collection);

            return $newMedia;
        }

        return null;
    }

    private function assertHasModelMedia(HasMedia $model): void
    {
        if (! in_array(HasModelMedia::class, class_uses_recursive($model), true)) {
            throw new \LogicException(sprintf(
                '%s must use the HasModelMedia trait for MediaPickerService::applyToCollection',
                $model::class,
            ));
        }
    }
}
