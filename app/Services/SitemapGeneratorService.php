<?php

namespace App\Services;

use App\Models\NewsArticle;
use App\Models\Page;
use App\Models\Project;
use App\Models\Service;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

class SitemapGeneratorService
{
    public const TYPES = ['pages', 'projects', 'services', 'news'];

    private const STATIC_PAGE_SLUGS = [
        'home',
        'about',
        'services',
        'portfolio',
        'news',
        'contacts',
        'imprint',
        'privacy-notice',
        'terms-of-use',
    ];

    public function index(): string
    {
        $base = $this->baseUrl();
        $now  = Carbon::now()->toAtomString();

        $xml  = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
        $xml .= '<sitemapindex xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";

        foreach (self::TYPES as $type) {
            $xml .= "  <sitemap>\n";
            $xml .= '    <loc>' . htmlspecialchars("{$base}/sitemap-{$type}.xml", ENT_XML1) . "</loc>\n";
            $xml .= "    <lastmod>{$now}</lastmod>\n";
            $xml .= "  </sitemap>\n";
        }

        $xml .= '</sitemapindex>' . "\n";

        return $xml;
    }

    public function sitemap(string $type): ?string
    {
        return match ($type) {
            'pages'    => $this->buildUrlSet($this->staticPageUrls()),
            'projects' => $this->buildUrlSet($this->modelUrls(Project::query()->where('active', true)->get(), '/portfolio/')),
            'services' => $this->buildUrlSet($this->modelUrls(Service::query()->where('active', true)->get(), '/services/')),
            'news'     => $this->buildUrlSet($this->modelUrls(NewsArticle::query()->where('active', true)->get(), '/news/')),
            default    => null,
        };
    }

    /**
     * @return array<int, array{path: string, lastmod: ?Carbon}>
     */
    private function staticPageUrls(): array
    {
        $entries = [];
        $pages   = Page::all()->keyBy('slug');

        foreach (self::STATIC_PAGE_SLUGS as $slug) {
            $page = $pages->get($slug);
            $path = $this->staticPagePath($slug);
            $entries[] = [
                'path'    => $path,
                'lastmod' => $page?->updated_at,
            ];
        }

        return $entries;
    }

    /**
     * @param  iterable<Model>  $models
     * @return array<int, array{path: string, lastmod: ?Carbon}>
     */
    private function modelUrls(iterable $models, string $prefix): array
    {
        $entries = [];

        foreach ($models as $model) {
            $slug = $model->slug ?? null;
            if (!$slug) {
                continue;
            }

            $entries[] = [
                'path'    => $prefix . $slug,
                'lastmod' => $model->updated_at,
            ];
        }

        return $entries;
    }

    /**
     * @param  array<int, array{path: string, lastmod: ?Carbon}>  $entries
     */
    private function buildUrlSet(array $entries): string
    {
        $locales       = $this->locales();
        $defaultLocale = config('app.fallback_locale', 'en');
        $base          = $this->baseUrl();

        $xml  = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
        $xml .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9" '
              . 'xmlns:xhtml="http://www.w3.org/1999/xhtml">' . "\n";

        foreach ($entries as $entry) {
            $path    = $entry['path'];
            $lastmod = $entry['lastmod'] instanceof Carbon
                ? $entry['lastmod']->toAtomString()
                : Carbon::now()->toAtomString();

            foreach ($locales as $locale) {
                $loc = $this->localizedUrl($base, $locale, $defaultLocale, $path);

                $xml .= "  <url>\n";
                $xml .= '    <loc>' . htmlspecialchars($loc, ENT_XML1) . "</loc>\n";
                $xml .= "    <lastmod>{$lastmod}</lastmod>\n";

                foreach ($locales as $alt) {
                    $altUrl = $this->localizedUrl($base, $alt, $defaultLocale, $path);
                    $xml .= '    <xhtml:link rel="alternate" hreflang="' . $alt . '" href="'
                          . htmlspecialchars($altUrl, ENT_XML1) . '" />' . "\n";
                }
                $defaultUrl = $this->localizedUrl($base, $defaultLocale, $defaultLocale, $path);
                $xml .= '    <xhtml:link rel="alternate" hreflang="x-default" href="'
                      . htmlspecialchars($defaultUrl, ENT_XML1) . '" />' . "\n";

                $xml .= "  </url>\n";
            }
        }

        $xml .= '</urlset>' . "\n";

        return $xml;
    }

    /**
     * Public URLs an Observer needs for IndexNow when a model is saved.
     * Returns one URL per published locale.
     *
     * @return array<int, string>
     */
    public function urlsForModel(Model $model): array
    {
        $type   = $this->resolveType($model);
        if (!$type) {
            return [];
        }

        $path = match ($type) {
            'project'      => '/portfolio/' . $model->slug,
            'service'      => '/services/' . $model->slug,
            'news_article' => '/news/' . $model->slug,
            'page'         => in_array($model->slug, self::STATIC_PAGE_SLUGS, true) ? $this->staticPagePath($model->slug) : null,
            default        => null,
        };

        if ($path === null) {
            return [];
        }

        $base          = $this->baseUrl();
        $defaultLocale = config('app.fallback_locale', 'en');
        $urls          = [];

        foreach ($this->locales() as $locale) {
            $urls[] = $this->localizedUrl($base, $locale, $defaultLocale, $path);
        }

        return $urls;
    }

    /**
     * @return array<int, string>
     */
    public function allPublicUrls(): array
    {
        $base          = $this->baseUrl();
        $defaultLocale = config('app.fallback_locale', 'en');
        $locales       = $this->locales();

        $entries = array_merge(
            $this->staticPageUrls(),
            $this->modelUrls(Project::query()->where('active', true)->get(), '/portfolio/'),
            $this->modelUrls(Service::query()->where('active', true)->get(), '/services/'),
            $this->modelUrls(NewsArticle::query()->where('active', true)->get(), '/news/'),
        );

        $urls = [];
        foreach ($entries as $entry) {
            foreach ($locales as $locale) {
                $urls[] = $this->localizedUrl($base, $locale, $defaultLocale, $entry['path']);
            }
        }

        return array_values(array_unique($urls));
    }

    private function resolveType(Model $model): ?string
    {
        return match (true) {
            $model instanceof Project     => 'project',
            $model instanceof Service     => 'service',
            $model instanceof NewsArticle => 'news_article',
            $model instanceof Page        => 'page',
            default                       => null,
        };
    }

    /**
     * @return array<int, string>
     */
    private function locales(): array
    {
        return published_languages_keys() ?: [config('app.fallback_locale', 'en')];
    }

    private function baseUrl(): string
    {
        return rtrim(config('app.url'), '/');
    }

    private function staticPagePath(string $slug): string
    {
        if (in_array($slug, static_page_editable_slugs(), true)) {
            return static_page_url($slug);
        }

        return match ($slug) {
            'home' => '/',
            default => '/' . $slug,
        };
    }

    private function localizedUrl(string $base, string $locale, string $defaultLocale, string $path): string
    {
        $hideDefault = (bool) config('laravellocalization.hideDefaultLocaleInURL', false);

        if ($locale === $defaultLocale && $hideDefault) {
            return $path === '/' ? $base . '/' : $base . $path;
        }

        return $path === '/' ? $base . '/' . $locale : $base . '/' . $locale . $path;
    }
}
