<?php

namespace App\Models;

use App\Traits\HasImageProcessing;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Spatie\Translatable\HasTranslations;

class NewsArticle extends Model implements HasMedia
{
    use HasFactory, HasTranslations, SoftDeletes, InteractsWithMedia, HasImageProcessing;

    protected $fillable = [
        'title',
        'slug',
        'short_description',
        'description',

        'seo_title',
        'seo_description',
        'seo_keywords',
        'geo_text',

        'link_top_active',
        'link_top_text',
        'link_top_url',
        'link_bottom_active',
        'link_bottom_text',
        'link_bottom_url',

        'active',
        'sort',
    ];

    public array $translatable = [
        'title',
        'short_description',
        'description',

        'seo_title',
        'seo_description',
        'seo_keywords',
        'geo_text',

        'link_top_text',
        'link_bottom_text',
    ];

    protected function casts(): array
    {
        return [
            'title' => 'json',
            'short_description' => 'json',
            'description' => 'json',
            'seo_title' => 'json',
            'seo_description' => 'json',
            'seo_keywords' => 'json',
            'geo_text' => 'json',
            'link_top_text' => 'json',
            'link_top_active' => 'boolean',
            'link_bottom_text' => 'json',
            'link_bottom_active' => 'boolean',
            'active' => 'boolean',
        ];
    }

    public string $mediaDescription = 'description';
    public string $mediaLinkTopFile = 'link_top_file';
    public string $mediaLinkBottomFile = 'link_bottom_file';

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

    public function categories(): BelongsToMany {
        return $this->belongsToMany(NewsCategory::class, 'news_article_news_category');
    }

    public function getFirstCategoryAttribute(): ?NewsCategory {
        return $this->categories?->first();
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
