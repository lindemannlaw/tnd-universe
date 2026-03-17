<?php

namespace App\Models;

use App\Traits\HasImageProcessing;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
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

        'property_details',
        'location',
        'tags',
        'info',
        'area',

        'active',
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

        'property_details',
        'location',
        'tags',
        'info',
    ];

    protected function casts(): array
    {
        return [
            'title' => 'json',
            'short_description' => 'json',
            'description' => 'json',
            'description_blocks' => 'json',
            'seo_title' => 'json',
            'seo_description' => 'json',
            'seo_keywords' => 'json',
            'property_details' => 'json',
            'location' => 'json',
            'tags' => 'json',
            'info' => 'json',
            'active' => 'boolean',
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
            if (empty($model->getOriginal('slug')) && is_null($model->slug)) {
                $title = $model->getTranslation('title', config('app.fallback_locale'));
                $model->slug = Str::of($title)->slug('-');
            }
        });
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
