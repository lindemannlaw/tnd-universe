<?php

namespace App\Services;

use Illuminate\Http\Request;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class MediaPickerService
{
    /**
     * Resolve the image input for `$field` on a model that uses Spatie media collections.
     *
     * Order of precedence per request:
     *   1. `{$field}_media_id` hidden input → copy the picked Media into `$collection`
     *   2. uploaded file under `$field` → addMediaFromRequest as before
     *   3. nothing → leave the existing collection untouched
     *
     * Returns the Media that was attached/copied, or null if nothing happened.
     */
    public function applyToCollection(
        HasMedia $model,
        string $collection,
        Request $request,
        string $field,
        bool $clearCollectionFirst = true,
    ): ?Media {
        $mediaIdInput = $field . '_media_id';

        if ($request->filled($mediaIdInput)) {
            $sourceMedia = Media::find((int) $request->input($mediaIdInput));
            if (! $sourceMedia) {
                return null;
            }

            if ($clearCollectionFirst) {
                $model->clearMediaCollection($collection);
            }

            return $sourceMedia->copy($model, $collection);
        }

        if ($request->hasFile($field)) {
            if ($clearCollectionFirst) {
                $model->clearMediaCollection($collection);
            }

            return $model->addMediaFromRequest($field)->toMediaCollection($collection);
        }

        return null;
    }
}
