<?php

namespace App\Models;

use App\Traits\HasImageProcessing;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Spatie\Translatable\HasTranslations;

class Project extends Model implements HasMedia
{
    use HasFactory, HasTranslations, SoftDeletes, InteractsWithMedia, HasImageProcessing;

    protected $fillable = [
        'title',
        'slug',
        'short_description',
        'description',
        'description_blocks',

        'seo_title',
        'seo_description',
        'seo_keywords',
        'geo_text',

        'property_details',
        'location',
        'tags',
        'info',
        'area',

        'text_timestamps',

        'active',
        'inquiry_button_active',
        'sort',
    ];

    public array $translatable = [
        'title',
        'short_description',
        'description',
        'description_blocks',

        'seo_title',
        'seo_description',
        'seo_keywords',
        'geo_text',

        'property_details',
        'location',
        'tags',
        'info',
    ];

    protected function casts(): array
    {
        return [
            'active' => 'boolean',
            'inquiry_button_active' => 'boolean',
            'text_timestamps' => 'array',
        ];
    }

    public string $mediaHero = 'hero';
    public string $mediaDescription = 'description';
    public string $mediaFiles = 'files';
    public string $mediaGallery = 'gallery';

    public array $mediaSizes = [
        'xl' => 3840,
        'hd' => 2560,
        'lg' => 1920,
        'md' => 900,
        'sm' => 450,
    ];

    public static function boot()
    {
        parent::boot();

        static::saving(function ($model) {
            if (filled($model->slug)) {
                $model->slug = (string) Str::of((string) $model->slug)->slug('-');
            }

            if (empty($model->getOriginal('slug')) && is_null($model->slug)) {
                $title = $model->getTranslation('title', config('app.fallback_locale'));
                $base  = (string) Str::of($title)->slug('-');
                if ($base === '') $base = 'project';

                $slug = $base;
                $i    = 2;
                while (
                    static::withTrashed()
                        ->where('slug', $slug)
                        ->where('id', '!=', $model->id)
                        ->exists()
                ) {
                    $slug = $base . '-' . $i++;
                }

                $model->slug = $slug;
            }
        });

        static::updated(function ($model) {
            if (!$model->wasChanged('slug')) {
                return;
            }

            $oldSlug = trim((string) ($model->getOriginal('slug') ?? ''), '/');
            $newSlug = trim((string) ($model->slug ?? ''), '/');

            if ($oldSlug === '' || $oldSlug === $newSlug) {
                return;
            }

            $model->slugRedirects()->updateOrCreate(
                ['old_slug' => $oldSlug],
                ['project_id' => $model->id]
            );

            if ($newSlug !== '') {
                ProjectSlugRedirect::query()->where('old_slug', $newSlug)->delete();
            }
        });
    }

    public function slugRedirects(): HasMany
    {
        return $this->hasMany(ProjectSlugRedirect::class);
    }

    public function hasAnyPropertyDetail(): bool
    {
        $locale = app()->getLocale();

        $details = $this->property_details;

        if (!is_array($details)) {
            return false;
        }

        $filtered = array_filter($details, function ($value) {
            return !is_null($value) && trim($value) !== '';
        });

        return count($filtered) > 0;
    }

    public function getSortedDetails()
    {
        $details = $this->property_details;

        if (!is_array($details)) return [];

        // Exclude non-display fields
        unset($details['inquiry_button_text']);

        $order = ['property_type', 'status', 'year_built'];
        $sorted = array_merge(array_flip($order), $details);

        return array_intersect_key($sorted, $details);
    }

    public function registerMediaConversions(Media $media = null): void
    {
        if (!$media) {
            return;
        }

        if ($media->extension === 'svg') {
            return;
        }

        $collectionName = $media->collection_name;

        foreach ($this->mediaSizes as $size => $value) {
            $this
                ->addMediaConversion($size.'-webp')
                ->format('webp')
                ->width($value)
                ->nonQueued()
                ->performOnCollections($collectionName);
        }
    }
}
