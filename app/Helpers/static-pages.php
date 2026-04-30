<?php

use App\Models\Page;
use Illuminate\Http\RedirectResponse;

if (!function_exists('static_page_editable_slugs')) {
    function static_page_editable_slugs(): array
    {
        return ['home', 'about', 'portfolio', 'contacts', 'imprint', 'privacy-notice', 'terms-of-use'];
    }
}

if (!function_exists('static_page_default_path')) {
    function static_page_default_path(string $slug): string
    {
        return match ($slug) {
            'home' => '',
            default => $slug,
        };
    }
}

if (!function_exists('static_page_reserved_paths')) {
    function static_page_reserved_paths(): array
    {
        return ['admin', 'services', 'news', 'storage', 'api', 'sitemap.xml', 'sitemap-pages.xml', 'sitemap-projects.xml', 'sitemap-services.xml', 'sitemap-news.xml'];
    }
}

if (!function_exists('static_page_path')) {
    function static_page_path(string $slug): string
    {
        static $paths = null;

        if (!in_array($slug, static_page_editable_slugs(), true)) {
            return static_page_default_path($slug);
        }

        if ($paths === null) {
            $paths = [];
            try {
                $paths = Page::query()
                    ->whereIn('slug', static_page_editable_slugs())
                    ->pluck('public_slug', 'slug')
                    ->map(fn ($value) => trim((string) $value, '/'))
                    ->all();
            } catch (\Throwable $e) {
                $paths = [];
            }
        }

        $path = $paths[$slug] ?? null;

        if ($path === null || $path === '') {
            return static_page_default_path($slug);
        }

        return $path;
    }
}

if (!function_exists('static_page_url')) {
    function static_page_url(string $slug): string
    {
        $path = static_page_path($slug);
        return $path === '' ? '/' : '/' . $path;
    }
}

if (!function_exists('portfolio_url')) {
    function portfolio_url(): string
    {
        return static_page_url('portfolio');
    }
}

if (!function_exists('portfolio_project_url')) {
    function portfolio_project_url(\App\Models\Project|string $project): string
    {
        $slug = $project instanceof \App\Models\Project ? (string) $project->slug : (string) $project;
        return rtrim(portfolio_url(), '/') . '/' . $slug;
    }
}

if (!function_exists('static_page_is_active')) {
    function static_page_is_active(string $slug): bool
    {
        $currentPath = trim(request()->path(), '/');
        return $currentPath === static_page_path($slug);
    }
}

if (!function_exists('portfolio_is_active')) {
    function portfolio_is_active(): bool
    {
        $currentPath = trim(request()->path(), '/');
        $portfolioPath = static_page_path('portfolio');
        return $currentPath === $portfolioPath || str_starts_with($currentPath, $portfolioPath . '/');
    }
}

if (!function_exists('static_page_canonical_redirect')) {
    function static_page_canonical_redirect(string $slug): ?RedirectResponse
    {
        $currentPath = trim(request()->path(), '/');
        $canonical = static_page_path($slug);

        if ($currentPath === $canonical) {
            return null;
        }

        return redirect(static_page_url($slug), 301);
    }
}
