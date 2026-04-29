<?php

namespace App\Providers;

use App\Models\NewsArticle;
use App\Models\Page;
use App\Models\Project;
use App\Models\Service;
use App\Observers\SeoModelObserver;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Project::observe(SeoModelObserver::class);
        Service::observe(SeoModelObserver::class);
        NewsArticle::observe(SeoModelObserver::class);
        Page::observe(SeoModelObserver::class);
    }
}
