<?php

use Illuminate\Support\Facades\Route;

/* UPLOAD FILE */
Route::post('/upload-image', [\App\Http\Controllers\Admin\ImageUploadController::class, 'store'])->name('admin.image.upload');

/* MAIN */
Route::get('/', [\App\Http\Controllers\Admin\MainController::class, 'index'])->name('admin.main');

/* UI */
Route::get('/ui', [\App\Http\Controllers\Admin\UIController::class, 'index'])->name('admin.ui');

/* PROFILE */
Route::group([
    'middleware' => [\Spatie\Permission\Middleware\RoleOrPermissionMiddleware::using([\App\Enums\RolesEnum::SUPERADMIN->value, \App\Enums\RolesEnum::ADMIN->value])],
    'prefix' => 'profile'
], function () {

    Route::get('/', [\App\Http\Controllers\Admin\ProfileController::class, 'index'])->name('admin.profile');
    Route::get('/password', [\App\Http\Controllers\Admin\ProfileController::class, 'password'])->name('admin.password');

});

/* MANAGERS */
Route::group([
    'middleware' => [\Spatie\Permission\Middleware\RoleOrPermissionMiddleware::using([\App\Enums\RolesEnum::SUPERADMIN->value, \App\Enums\RolesEnum::ADMIN->value])],
    'prefix' => 'managers'
], function () {

    Route::get('/', [\App\Http\Controllers\Admin\ManagerController::class, 'index'])->name('admin.manager');

    Route::get('/create', [\App\Http\Controllers\Admin\ManagerController::class, 'create'])->name('admin.manager.create');
    Route::post('/store', [\App\Http\Controllers\Admin\ManagerController::class, 'store'])->name('admin.manager.store');
    Route::get('/{user}/edit', [\App\Http\Controllers\Admin\ManagerController::class, 'edit'])->name('admin.manager.edit');
    Route::patch('/{user}/update', [\App\Http\Controllers\Admin\ManagerController::class, 'update'])->name('admin.manager.update');
    Route::delete('/{user}/delete', [\App\Http\Controllers\Admin\ManagerController::class, 'delete'])->name('admin.manager.delete');

});

Route::group([
    'middleware' => [\Spatie\Permission\Middleware\RoleOrPermissionMiddleware::using([\App\Enums\PermissionsEnum::CANALL->value])]
], function () {

    /* HOME */
    Route::group(['prefix' => 'home'], function () {

        Route::get('/', [\App\Http\Controllers\Admin\HomePageController::class, 'index'])->name('admin.home.page');
        Route::patch('/{page}/update', [\App\Http\Controllers\Admin\HomePageController::class, 'update'])->name('admin.home.page.update');

    });

    /* ABOUT */
    Route::group(['prefix' => 'about'], function () {

        Route::get('/', [\App\Http\Controllers\Admin\AboutPageController::class, 'index'])->name('admin.about.page');
        Route::patch('/{page}/update', [\App\Http\Controllers\Admin\AboutPageController::class, 'update'])->name('admin.about.page.update');

        Route::group(['prefix' => 'leaders'], function () {

            Route::get('/', [\App\Http\Controllers\Admin\LeaderController::class, 'index'])->name('admin.about.leaders');

            Route::get('/create', [\App\Http\Controllers\Admin\LeaderController::class, 'create'])->name('admin.about.leader.create');
            Route::post('/store', [\App\Http\Controllers\Admin\LeaderController::class, 'store'])->name('admin.about.leader.store');
            Route::get('/{leader}/edit', [\App\Http\Controllers\Admin\LeaderController::class, 'edit'])->name('admin.about.leader.edit');
            Route::patch('/{leader}/update', [\App\Http\Controllers\Admin\LeaderController::class, 'update'])->name('admin.about.leader.update');
            Route::delete('/{leader}/delete', [\App\Http\Controllers\Admin\LeaderController::class, 'delete'])->name('admin.about.leader.delete');

        });

    });

    /* NEWS */
    Route::group(['prefix' => 'news'], function () {

        Route::get('/', [\App\Http\Controllers\Admin\News\PageController::class, 'index'])->name('admin.news.page');
        Route::patch('/{page}/update', [\App\Http\Controllers\Admin\News\PageController::class, 'update'])->name('admin.news.page.update');

        Route::group(['prefix' => 'categories'], function () {

            Route::get('/', [\App\Http\Controllers\Admin\News\CategoryController::class, 'index'])->name('admin.news.categories');

            Route::get('/create', [\App\Http\Controllers\Admin\News\CategoryController::class, 'create'])->name('admin.news.category.create');
            Route::post('/store', [\App\Http\Controllers\Admin\News\CategoryController::class, 'store'])->name('admin.news.category.store');
            Route::get('/{newsCategory}/edit', [\App\Http\Controllers\Admin\News\CategoryController::class, 'edit'])->name('admin.news.category.edit');
            Route::patch('/{newsCategory}/update', [\App\Http\Controllers\Admin\News\CategoryController::class, 'update'])->name('admin.news.category.update');
            Route::delete('/{newsCategory}/delete', [\App\Http\Controllers\Admin\News\CategoryController::class, 'delete'])->name('admin.news.category.delete');

        });

        Route::group(['prefix' => 'articles'], function () {

            Route::get('/', [\App\Http\Controllers\Admin\News\ArticleController::class, 'index'])->name('admin.news.articles');

            Route::get('/create', [\App\Http\Controllers\Admin\News\ArticleController::class, 'create'])->name('admin.news.article.create');
            Route::post('/store', [\App\Http\Controllers\Admin\News\ArticleController::class, 'store'])->name('admin.news.article.store');
            Route::get('/{newsArticle}/edit', [\App\Http\Controllers\Admin\News\ArticleController::class, 'edit'])->name('admin.news.article.edit');
            Route::patch('/{newsArticle}/update', [\App\Http\Controllers\Admin\News\ArticleController::class, 'update'])->name('admin.news.article.update');
            Route::delete('/{newsArticle}/delete', [\App\Http\Controllers\Admin\News\ArticleController::class, 'delete'])->name('admin.news.article.delete');

        });

    });

    /* SERVICES */
    Route::group(['prefix' => 'services'], function () {

        Route::get('/', [\App\Http\Controllers\Admin\Services\PageController::class, 'index'])->name('admin.services.page');
        Route::patch('/{page}/update', [\App\Http\Controllers\Admin\Services\PageController::class, 'update'])->name('admin.services.page.update');

        Route::group(['prefix' => 'categories'], function () {

            Route::get('/', [\App\Http\Controllers\Admin\Services\CategoryController::class, 'index'])->name('admin.services.categories');

            Route::get('/create', [\App\Http\Controllers\Admin\Services\CategoryController::class, 'create'])->name('admin.services.category.create');
            Route::post('/store', [\App\Http\Controllers\Admin\Services\CategoryController::class, 'store'])->name('admin.services.category.store');
            Route::get('/{serviceCategory}/edit', [\App\Http\Controllers\Admin\Services\CategoryController::class, 'edit'])->name('admin.services.category.edit');
            Route::patch('/{serviceCategory}/update', [\App\Http\Controllers\Admin\Services\CategoryController::class, 'update'])->name('admin.services.category.update');
            Route::delete('/{serviceCategory}/delete', [\App\Http\Controllers\Admin\Services\CategoryController::class, 'delete'])->name('admin.services.category.delete');

        });

        Route::group(['prefix' => 'services'], function () {

            Route::get('/', [\App\Http\Controllers\Admin\Services\ServiceController::class, 'index'])->name('admin.services.services');

            Route::get('/create', [\App\Http\Controllers\Admin\Services\ServiceController::class, 'create'])->name('admin.services.service.create');
            Route::post('/store', [\App\Http\Controllers\Admin\Services\ServiceController::class, 'store'])->name('admin.services.service.store');
            Route::get('/{service}/edit', [\App\Http\Controllers\Admin\Services\ServiceController::class, 'edit'])->name('admin.services.service.edit');
            Route::patch('/{service}/update', [\App\Http\Controllers\Admin\Services\ServiceController::class, 'update'])->name('admin.services.service.update');
            Route::delete('/{service}/delete', [\App\Http\Controllers\Admin\Services\ServiceController::class, 'delete'])->name('admin.services.service.delete');

        });

    });

    /* PORTFOLIO */
    Route::group(['prefix' => 'portfolio'], function () {

        Route::get('/', [\App\Http\Controllers\Admin\Portfolio\PageController::class, 'index'])->name('admin.portfolio.page');
        Route::patch('/{page}/update', [\App\Http\Controllers\Admin\Portfolio\PageController::class, 'update'])->name('admin.portfolio.page.update');

        Route::group(['prefix' => 'project'], function () {

            Route::get('/', [\App\Http\Controllers\Admin\Portfolio\ProjectController::class, 'index'])->name('admin.portfolio.projects');

            Route::get('/create', [\App\Http\Controllers\Admin\Portfolio\ProjectController::class, 'create'])->name('admin.portfolio.project.create');
            Route::post('/store', [\App\Http\Controllers\Admin\Portfolio\ProjectController::class, 'store'])->name('admin.portfolio.project.store');
            Route::get('/{project}/edit', [\App\Http\Controllers\Admin\Portfolio\ProjectController::class, 'edit'])->name('admin.portfolio.project.edit');
            Route::patch('/{project}/update', [\App\Http\Controllers\Admin\Portfolio\ProjectController::class, 'update'])->name('admin.portfolio.project.update');
            Route::post('/{project}/update-timestamps', [\App\Http\Controllers\Admin\Portfolio\ProjectController::class, 'updateTextTimestamps'])->name('admin.portfolio.project.update-timestamps');
            Route::post('/{project}/apply-translations', [\App\Http\Controllers\Admin\Portfolio\ProjectController::class, 'applyTranslations'])->name('admin.portfolio.project.apply-translations');
            Route::post('/{project}/clone', [\App\Http\Controllers\Admin\Portfolio\ProjectController::class, 'clone'])->name('admin.portfolio.project.clone');
            Route::delete('/{project}/delete', [\App\Http\Controllers\Admin\Portfolio\ProjectController::class, 'delete'])->name('admin.portfolio.project.delete');
            Route::delete('/{media}/delete-file', [\App\Http\Controllers\Admin\Portfolio\ProjectController::class, 'deleteFile'])->name('admin.portfolio.project.delete.file');

        });

    });

    /* CONTACTS */
    Route::group(['prefix' => 'contacts'], function () {

        Route::get('/', [\App\Http\Controllers\Admin\ContactsPageController::class, 'index'])->name('admin.contacts.page');
        Route::patch('/{page}/update', [\App\Http\Controllers\Admin\ContactsPageController::class, 'update'])->name('admin.contacts.page.update');

    });

    /* SITE SECTIONS */
    Route::group(['prefix' => 'site-sections'], function () {

        Route::group(['prefix' => 'who-we-are'], function () {

            Route::get('/', [\App\Http\Controllers\Admin\SiteSections\WhoWeAreSectionController::class, 'index'])->name('admin.site-sections.who-we-are');
            Route::patch('/{siteSection}/update', [\App\Http\Controllers\Admin\SiteSections\WhoWeAreSectionController::class, 'update'])->name('admin.site-sections.who-we-are.update');

        });


        Route::group(['prefix' => 'contact-us'], function () {

            Route::get('/', [\App\Http\Controllers\Admin\SiteSections\ContactUsSectionController::class, 'index'])->name('admin.site-sections.contact-us');
            Route::patch('/{siteSection}/update', [\App\Http\Controllers\Admin\SiteSections\ContactUsSectionController::class, 'update'])->name('admin.site-sections.contact-us.update');

        });
    });

    /* IMPRINT */
    Route::group(['prefix' => 'imprint'], function () {

        Route::get('/', [\App\Http\Controllers\Admin\ImprintPageController::class, 'index'])->name('admin.imprint.page');
        Route::patch('/{page}/update', [\App\Http\Controllers\Admin\ImprintPageController::class, 'update'])->name('admin.imprint.page.update');

    });

    /* PRIVACY NOTICE */
    Route::group(['prefix' => 'privacy-notice'], function () {

        Route::get('/', [\App\Http\Controllers\Admin\PrivacyNoticePageController::class, 'index'])->name('admin.privacy-notice.page');
        Route::patch('/{page}/update', [\App\Http\Controllers\Admin\PrivacyNoticePageController::class, 'update'])->name('admin.privacy-notice.page.update');

    });

    /* TERMS OF USE */
    Route::group(['prefix' => 'terms-of-use'], function () {

        Route::get('/', [\App\Http\Controllers\Admin\TermsOfUsePageController::class, 'index'])->name('admin.terms-of-use.page');
        Route::patch('/{page}/update', [\App\Http\Controllers\Admin\TermsOfUsePageController::class, 'update'])->name('admin.terms-of-use.page.update');

    });

    /* DELETE */
    Route::group(['prefix' => 'delete'], function () {

        Route::get('/', [\App\Http\Controllers\Admin\DeleteModalController::class, 'show'])->name('admin.confirm-delete-modal');

    });

    /* TRANSLATION */
    Route::post('/translate', [\App\Http\Controllers\Admin\TranslationController::class, 'translate'])->name('admin.translate');

    /* SEO GENERATION */
    Route::post('/generate-seo', [\App\Http\Controllers\Admin\SeoController::class, 'generate'])->name('admin.generate-seo');

    /* SEO/GEO OVERVIEW */
    Route::group(['prefix' => 'seo-geo'], function () {
        Route::get('/', [\App\Http\Controllers\Admin\SeoGeoController::class, 'index'])->name('admin.seo-geo.index');
        Route::get('/{type}/{id}', [\App\Http\Controllers\Admin\SeoGeoController::class, 'show'])->name('admin.seo-geo.show');
        Route::get('/{type}/{id}/live-preview', [\App\Http\Controllers\Admin\SeoGeoController::class, 'livePreview'])->name('admin.seo-geo.live-preview');
        Route::post('/generate', [\App\Http\Controllers\Admin\SeoGeoController::class, 'generate'])->name('admin.seo-geo.generate');
        Route::post('/apply', [\App\Http\Controllers\Admin\SeoGeoController::class, 'apply'])->name('admin.seo-geo.apply');
        Route::post('/save-field', [\App\Http\Controllers\Admin\SeoGeoController::class, 'saveField'])->name('admin.seo-geo.save-field');
        Route::post('/trigger-crawl', [\App\Http\Controllers\Admin\SeoGeoController::class, 'triggerCrawl'])->name('admin.seo-geo.trigger-crawl');
    });

    /* TRANSLATION CHECK */
    Route::group(['prefix' => 'translations'], function () {
        Route::get('/', [\App\Http\Controllers\Admin\TranslationCheckController::class, 'index'])->name('admin.translations.index');
        Route::post('/translate', [\App\Http\Controllers\Admin\TranslationCheckController::class, 'translate'])->name('admin.translations.translate');
        Route::post('/apply', [\App\Http\Controllers\Admin\TranslationCheckController::class, 'apply'])->name('admin.translations.apply');
    });

    /* MEDIA */
    Route::group(['prefix' => 'media'], function () {
        Route::get('/',                  [\App\Http\Controllers\Admin\MediaController::class, 'index'])->name('admin.media.index');
        Route::get('/picker',            [\App\Http\Controllers\Admin\MediaController::class, 'picker'])->name('admin.media.picker');
        Route::get('/picker/list',       [\App\Http\Controllers\Admin\MediaController::class, 'pickerList'])->name('admin.media.picker.list');
        Route::post('/upload',           [\App\Http\Controllers\Admin\MediaController::class, 'upload'])->name('admin.media.upload');
        Route::get('/{media}',           [\App\Http\Controllers\Admin\MediaController::class, 'show'])->name('admin.media.show');
        Route::get('/{media}/download',  [\App\Http\Controllers\Admin\MediaController::class, 'download'])->name('admin.media.download');
        Route::post('/{media}/replace',  [\App\Http\Controllers\Admin\MediaController::class, 'replace'])->name('admin.media.replace');
        Route::delete('/{media}/delete', [\App\Http\Controllers\Admin\MediaController::class, 'destroy'])->name('admin.media.delete');
    });

    /* LANGUAGE SETTINGS */
    Route::post('/language-settings/{locale}/toggle', [\App\Http\Controllers\Admin\LanguageSettingController::class, 'toggle'])
        ->name('admin.language-settings.toggle');

});
