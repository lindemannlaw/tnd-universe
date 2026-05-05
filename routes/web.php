<?php

/**
 * Web-Routen: Sitemaps (ohne Locale), lokalisierter Haupt-Block (auth, admin, public),
 * danach externe Tool-Proxies (Visitenkarten o. Ä. — nicht Teil des TND-Site-Kerns).
 */
use App\Http\Controllers\Public\SitemapController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Mcamara\LaravelLocalization\Facades\LaravelLocalization;

Route::get('/sitemap.xml', [SitemapController::class, 'index'])
    ->name('public.sitemap');
Route::get('/sitemap-{type}.xml', [SitemapController::class, 'type'])
    ->where('type', '[a-z_]+')
    ->name('public.sitemap.type');

Route::group([
    'prefix' => LaravelLocalization::setLocale(),
    'middleware' => [ 'localizationRedirect', 'localeViewPath']
], function () {

    require __DIR__.'/auth.php';

    Route::group(['middleware' => ['auth'], 'namespace' => 'Admin', 'prefix' => 'admin'], function () {
        require __DIR__ . '/admin.php';
    });

    require __DIR__.'/public.php';

});

// --- Externe Proxies (z. B. Visitenkarten); CSRF-Ausnahme in bootstrap/app.php ---
Route::post('/tools/proxy/claude', function (Request $request) {
    $response = \Illuminate\Support\Facades\Http::timeout(60)
        ->withHeaders([
            'x-api-key'         => env('ANTHROPIC_API_KEY'),
            'anthropic-version' => '2023-06-01',
        ])
        ->post('https://api.anthropic.com/v1/messages', $request->all());

    return response($response->body(), $response->status())
        ->header('Content-Type', 'application/json');
});

Route::post('/tools/proxy/brevo', function (Request $request) {
    $response = \Illuminate\Support\Facades\Http::timeout(30)
        ->withHeaders([
            'api-key' => env('BREVO_API_KEY'),
        ])
        ->post('https://api.brevo.com/v3/contacts', $request->all());

    return response($response->body(), $response->status())
        ->header('Content-Type', 'application/json');
});