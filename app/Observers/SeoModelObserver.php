<?php

namespace App\Observers;

use App\Services\GoogleIndexingApiService;
use App\Services\IndexNowService;
use App\Services\SitemapGeneratorService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class SeoModelObserver
{
    public function __construct(
        private readonly SitemapGeneratorService $generator,
        private readonly IndexNowService $indexNow,
        private readonly GoogleIndexingApiService $googleIndexing,
    ) {}

    public function saved(Model $model): void
    {
        $this->flushSitemapCache($model);

        $urls = $this->generator->urlsForModel($model);
        if (empty($urls)) {
            return;
        }

        dispatch(function () use ($urls): void {
            try {
                $this->indexNow->submit($urls);
            } catch (\Throwable $e) {
                Log::warning('[SeoModelObserver] IndexNow submit failed', ['message' => $e->getMessage()]);
            }

            try {
                $this->googleIndexing->submit($urls, 'URL_UPDATED');
            } catch (\Throwable $e) {
                Log::warning('[SeoModelObserver] Google Indexing API submit failed', ['message' => $e->getMessage()]);
            }
        })->afterResponse();
    }

    public function deleted(Model $model): void
    {
        $this->flushSitemapCache($model);
    }

    private function flushSitemapCache(Model $model): void
    {
        Cache::forget('sitemap:index');

        $type = match (true) {
            $model instanceof \App\Models\Project => 'projects',
            $model instanceof \App\Models\Service => 'services',
            $model instanceof \App\Models\NewsArticle => 'news',
            $model instanceof \App\Models\Page => 'pages',
            default => null,
        };

        if ($type !== null) {
            Cache::forget("sitemap:{$type}");
        }
    }
}
