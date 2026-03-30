<?php

namespace App\Services;

use App\Models\Leader;
use App\Models\NewsArticle;
use App\Models\NewsCategory;
use App\Models\Page;
use App\Models\Project;
use App\Models\Service;
use App\Models\ServiceCategory;
use App\Models\SiteSection;

class TranslatableModelRegistry
{
    private const MODELS = [
        'project' => [
            'class'      => Project::class,
            'label'      => 'Projects',
            'labelDe'    => 'Projekte',
            'titleField' => 'title',
            'hasSeo'     => true,
        ],
        'service' => [
            'class'      => Service::class,
            'label'      => 'Services',
            'labelDe'    => 'Leistungen',
            'titleField' => 'title',
            'hasSeo'     => true,
        ],
        'service_category' => [
            'class'      => ServiceCategory::class,
            'label'      => 'Service Categories',
            'labelDe'    => 'Service-Kategorien',
            'titleField' => 'name',
            'hasSeo'     => true,
        ],
        'news_article' => [
            'class'      => NewsArticle::class,
            'label'      => 'News Articles',
            'labelDe'    => 'News-Artikel',
            'titleField' => 'title',
            'hasSeo'     => true,
        ],
        'news_category' => [
            'class'      => NewsCategory::class,
            'label'      => 'News Categories',
            'labelDe'    => 'News-Kategorien',
            'titleField' => 'name',
            'hasSeo'     => true,
        ],
        'page' => [
            'class'      => Page::class,
            'label'      => 'Pages',
            'labelDe'    => 'Seiten',
            'titleField' => 'title',
            'hasSeo'     => true,
        ],
        'site_section' => [
            'class'      => SiteSection::class,
            'label'      => 'Site Sections',
            'labelDe'    => 'Seitenabschnitte',
            'titleField' => 'title',
            'hasSeo'     => false,
        ],
        'leader' => [
            'class'      => Leader::class,
            'label'      => 'Leaders',
            'labelDe'    => 'Führungspersonen',
            'titleField' => 'name',
            'hasSeo'     => false,
        ],
    ];

    public const SEO_FIELDS = ['seo_title', 'seo_description', 'seo_keywords', 'geo_text'];

    public function all(): array
    {
        return self::MODELS;
    }

    public function withSeo(): array
    {
        return array_filter(self::MODELS, fn ($m) => $m['hasSeo']);
    }

    public function resolve(string $type): ?array
    {
        return self::MODELS[$type] ?? null;
    }

    public function resolveModel(string $type, int $id)
    {
        $meta = $this->resolve($type);
        if (!$meta) return null;

        return $meta['class']::find($id);
    }

    public function label(string $type): string
    {
        $meta = self::MODELS[$type] ?? null;
        $locale = app()->getLocale();
        return $locale === 'de' ? ($meta['labelDe'] ?? $type) : ($meta['label'] ?? $type);
    }

    /**
     * Get all items from all models with SEO fields.
     */
    public function allSeoItems(): array
    {
        $items = [];

        foreach ($this->withSeo() as $type => $meta) {
            $records = $meta['class']::all();
            foreach ($records as $record) {
                $items[] = [
                    'type'       => $type,
                    'label'      => $meta['labelDe'],
                    'id'         => $record->id,
                    'title'      => $record->getTranslation($meta['titleField'], config('app.fallback_locale'), false) ?: '(ohne Titel)',
                    'model'      => $record,
                ];
            }
        }

        return $items;
    }
}
