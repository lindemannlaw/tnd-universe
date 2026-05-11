<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Models\Concerns\HasModelMedia;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Spatie\Translatable\HasTranslations;

class Page extends Model implements HasMedia
{
    use HasFactory, InteractsWithMedia, HasTranslations, HasModelMedia;

    protected static function booted(): void
    {
        static::saving(function (self $page) {
            if (!in_array($page->slug, static_page_editable_slugs(), true)) {
                return;
            }

            $normalized = trim((string) ($page->public_slug ?? ''), '/');
            $page->public_slug = $normalized !== '' ? $normalized : null;
        });

        static::updated(function (self $page) {
            if (!in_array($page->slug, static_page_editable_slugs(), true)) {
                return;
            }

            if (!$page->wasChanged('public_slug')) {
                return;
            }

            $oldSlug = trim((string) ($page->getOriginal('public_slug') ?? ''), '/');
            $newSlug = trim((string) ($page->public_slug ?? ''), '/');

            if ($oldSlug === '' || $oldSlug === $newSlug) {
                return;
            }

            $page->slugRedirects()->updateOrCreate(
                ['old_slug' => $oldSlug],
                ['page_id' => $page->id]
            );

            if ($newSlug !== '') {
                PageSlugRedirect::query()->where('old_slug', $newSlug)->delete();
            }
        });
    }

    public string $mediaCollection = 'pages';

    public array $mediaSizes = [
        'xl' => 3840,
        'hd' => 2560,
        'lg' => 1920,
        'md' => 900,
        'sm' => 450,
    ];

    protected $fillable = [
        'title',
        'public_slug',
        'description',

        'seo_title',
        'seo_description',
        'seo_keywords',
        'geo_text',
        'lat',
        'lon',
        'geo_region',

        'content_data',
    ];

    public array $translatable = [
        'title',
        'description',

        'seo_title',
        'seo_description',
        'seo_keywords',
        'geo_text',

        'content_data',
    ];

    protected function casts(): array
    {
        return [
            'title' => 'json',
            'description' => 'json',
            'seo_title' => 'json',
            'seo_description' => 'json',
            'seo_keywords' => 'json',
            'geo_text' => 'json',
            'content_data' => 'json',
        ];
    }

    public function slugRedirects(): HasMany
    {
        return $this->hasMany(PageSlugRedirect::class);
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
