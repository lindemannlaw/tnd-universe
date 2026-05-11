<?php

namespace App\Models;

use App\Models\Concerns\HasModelMedia;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Spatie\Translatable\HasTranslations;

class Service extends Model implements HasMedia
{
    use HasFactory, HasTranslations, SoftDeletes, InteractsWithMedia, HasModelMedia;

    protected $fillable = [
        'title',
        'inner_title',
        'slug',
        'short_description',
        'description',

        'seo_title',
        'seo_description',
        'seo_keywords',
        'geo_text',
        'lat',
        'lon',
        'geo_region',

        'details',
        'service_category_id',

        'active',
        'sort',
    ];

    public array $translatable = [
        'title',
        'inner_title',
        'short_description',
        'description',

        'seo_title',
        'seo_description',
        'seo_keywords',
        'geo_text',

        'details',
    ];

    protected function casts(): array
    {
        return [
            'title' => 'json',
            'inner_title' => 'json',
            'short_description' => 'json',
            'description' => 'json',
            'seo_title' => 'json',
            'seo_description' => 'json',
            'seo_keywords' => 'json',
            'geo_text' => 'json',
            'details' => 'json',
            'service_category_id' => 'integer',
            'active' => 'boolean',
        ];
    }

    public string $mediaHero = 'hero-images';
    public string $mediaInfo = 'info-images';

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
                $base  = (string) Str::of($title)->slug('-');
                if ($base === '') $base = 'service';

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
    }

    public function category(): BelongsTo {
        return $this->belongsTo(ServiceCategory::class, 'service_category_id');
    }

    public function registerMediaConversions(Media $media = null): void
    {
        if ($media && $media->extension === 'svg') {
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
