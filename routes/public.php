<?php

use Illuminate\Support\Facades\Route;

Route::get('/', [App\Http\Controllers\Public\HomePageController::class, 'index'])->name('public.home');
Route::get('/about', [App\Http\Controllers\Public\AboutPageController::class, 'index'])->name('public.about');

Route::group(['prefix' => 'services'], function () {

    Route::get('/', [App\Http\Controllers\Public\Services\ServicesPageController::class, 'index'])->name('public.services');
    Route::get('/{service:slug}', [App\Http\Controllers\Public\Services\ServicePageController::class, 'index'])->name('public.services.post');

});

Route::group(['prefix' => 'portfolio'], function () {

    Route::get('/', [App\Http\Controllers\Public\Portfolio\PortfolioPageController::class, 'index'])->name('public.portfolio');
    Route::get('/{project:slug}', [App\Http\Controllers\Public\Portfolio\ProjectPageController::class, 'index'])->name('public.portfolio.project');

});

Route::group(['prefix' => 'news'], function () {

    Route::get('/', [App\Http\Controllers\Public\News\NewsPageController::class, 'index'])->name('public.news');
    Route::get('/{newsArticle:slug}', [App\Http\Controllers\Public\News\ArticlePageController::class, 'index'])->name('public.news.article');

});

Route::get('/contacts', [App\Http\Controllers\Public\ContactsPageController::class, 'index'])->name('public.contacts');

Route::get('/imprint', [App\Http\Controllers\Public\ImprintPageController::class, 'index'])->name('public.imprint');
Route::get('/privacy-notice', [App\Http\Controllers\Public\PrivacyNoticePageController::class, 'index'])->name('public.privacy-notice');
Route::get('/terms-of-use', [App\Http\Controllers\Public\TermsOfUsePageController::class, 'index'])->name('public.terms-of-use');
Route::get('/{portfolioAlias}/{project:slug}', [App\Http\Controllers\Public\Portfolio\ProjectPageController::class, 'indexByAlias'])
    ->where('portfolioAlias', '^(?!services$|portfolio$|news$|admin$|storage$|api$|sitemap\.xml$|sitemap-pages\.xml$|sitemap-projects\.xml$|sitemap-services\.xml$|sitemap-news\.xml$)[a-z0-9-]+$')
    ->name('public.portfolio.project.alias');
Route::get('/{pageAlias}', '\App\Http\Controllers\Public\StaticPageAliasController')
    ->where('pageAlias', '^(?!services$|portfolio$|news$|admin$|storage$|api$|sitemap\.xml$|sitemap-pages\.xml$|sitemap-projects\.xml$|sitemap-services\.xml$|sitemap-news\.xml$)[a-z0-9-]+$');

Route::group([
    'middleware' => [App\Http\Middleware\ThrottleEmails::class],
], function () {

    Route::post('/send-contact-form', [App\Http\Controllers\Public\SendContactFormController::class, 'store'])->name('public.send-contact-form');

});
